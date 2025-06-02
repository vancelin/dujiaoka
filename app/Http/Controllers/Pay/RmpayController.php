<?php

namespace App\Http\Controllers\Pay;

use App\Http\Controllers\PayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Service\OrderProcessService;
use App\Service\PayService;

class RmpayController extends PayController
{
    protected $orderProcessService;
    protected $order;
    protected $payInfo;
    protected $merchantId;
    protected $merchantKey;
    protected $apiUrl;
    protected $dev = true;
    protected $payService;

    public function __construct(OrderProcessService $orderProcessService, PayService $payService)
    {
        parent::__construct($orderProcessService);
        $this->orderProcessService = $orderProcessService;
        $this->payService = $payService;
    }

    public function loadGateWay($orderSN, $payway)
    {
        parent::loadGateWay($orderSN, $payway);

        // **從父類獲取的支付網關配置中獲取商戶ID和密鑰**
        if (!$this->payGateway || !$this->payGateway->merchant_id || !$this->payGateway->merchant_key) {
            throw new \Exception('支付配置格式錯誤，缺少商戶ID或密鑰');
        }

        $this->merchantId = $this->payGateway->merchant_id;
        $this->merchantKey = $this->payGateway->merchant_key;

        Log::info('RMPay 從資料庫獲取商戶配置', [
            'merchant_id' => $this->merchantId,
            'merchant_key' => '***' . substr($this->merchantKey, -4) // 日誌中部分隱藏密鑰
        ]);

        // 根據環境設置API地址
        $this->apiUrl = $this->dev
            ? 'https://b.rmpay.supply/pay' // 測試環境API地址
            : 'https://b.rmpay.supply/pay'; // 正式環境API地址

        Log::info('RMPay 支付網關加載成功', [
            'orderSN' => $orderSN,
            'payway' => $payway,
            'merchantId' => $this->merchantId,
            'apiUrl' => $this->apiUrl,
            'dev_mode' => $this->dev
        ]);
    }

    public function gateway($payway, $orderSN)
    {
        try {
            $this->loadGateWay($orderSN, $payway);
            
            if (!$this->order) {
                Log::error('RMPay 訂單加載失敗', ['orderSN' => $orderSN]);
                throw new \Exception('訂單加載失敗，無法繼續支付');
            }

            $requestData = [
                'uid'        => $this->merchantId,
                'orderid'    => $orderSN,
                'channel'    => $this->getChannelByPayway($payway),
                'notify_url' => url('/pay/rmpay/notify_url'),
                'return_url' => url('/pay/rmpay/return_url'),
                'amount'     => number_format($this->order->actual_price, 2, '.', ''),
                'userip'     => request()->ip(),
                'timestamp'  => time(),
                'custom'     => $orderSN,
            ];

            $requestData['sign'] = $this->generateSign($requestData);

            Log::info('RMPay 支付請求發起', ['url' => $this->apiUrl, 'data' => $requestData]);

            $response = $this->sendRequest($this->apiUrl, $requestData);

            Log::info('RMPay 支付請求響應', ['response' => $response]);

            if (isset($response['status']) && $response['status'] == 10000 && isset($response['result']['payurl'])) {
                return redirect()->away($response['result']['payurl']);
            } else {
                $errorMessage = $response['msg'] ?? '未知錯誤';
                Log::error('RMPay 支付請求失敗', ['orderSN' => $orderSN, 'response' => $response]);
                throw new \Exception("支付請求失敗: " . $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('RMPay Gateway 處理異常', [
                'orderSN' => $orderSN,
                'message' => $e->getMessage(),
            ]);
            return $this->err('支付發起失敗：' . $e->getMessage());
        }
    }

    public function notifyUrl(Request $request)
    {
        $notificationData = $request->all();
        Log::info('RMPay 接收到異步回調通知 (原始數據)', $notificationData);

        try {
            // 1. 基本驗證：檢查簽名、狀態和 result 字段是否存在於頂層
            if (!isset($notificationData['status']) || empty($notificationData['sign']) || !isset($notificationData['result'])) {
                throw new \Exception('回調參數不完整 (缺少顶层 status, sign, 或 result)');
            }
            
            // 2. 驗證簽名
            $receivedSign = $notificationData['sign'];
            $dataToVerify = $notificationData; // 複製一份用於驗簽，避免修改原始數據影響日誌
            unset($dataToVerify['sign']); // 簽名本身不參與簽名計算

            // **從資料庫獲取商戶密鑰進行簽名驗證**
            // 這裡需要重新加載支付配置，因為在回調中可能沒有之前的上下文
            $payInfo = $this->payService->detailByCheck('rmpay');
            if (!$payInfo || !$payInfo->merchant_key) {
                throw new \Exception('無法獲取支付配置進行簽名驗證');
            }
            $this->merchantKey = $payInfo->merchant_key;

            if ($this->generateSign($dataToVerify) !== $receivedSign) {
                // 注意：generateSign 應能處理 $dataToVerify 中 result 字段是字符串的情況
                Log::error('RMPay 簽名驗證失敗', [
                    'received_sign' => $receivedSign,
                    'calculated_sign_data' => $dataToVerify,
                    'key_used_for_calc' => '***' . substr($this->merchantKey, -4)
                ]);
                throw new \Exception('簽名驗證失敗');
            }

            Log::info('RMPay 簽名驗證成功');

            // 3. 解析 result 字段中的 JSON 字符串
            if (!is_string($notificationData['result'])) {
                throw new \Exception('回調 result 字段類型非字符串');
            }
            $resultData = json_decode($notificationData['result'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($resultData)) {
                Log::error('RMPay 回調 result 字段JSON解析失敗', ['result_string' => $notificationData['result'], 'error' => json_last_error_msg()]);
                throw new \Exception('回調 result 字段JSON解析失敗: ' . json_last_error_msg());
            }

            Log::info('RMPay 解析後的 result 數據', $resultData);

            // 4. 從解析後的 $resultData 中提取業務所需參數
            $orderSN = $resultData['orderid'] ?? null;
            $transactionId = $resultData['transactionid'] ?? null;
            $paidAmount = isset($resultData['amount']) ? (float)$resultData['amount'] : null;
            // $realAmount = isset($resultData['real_amount']) ? (float)$resultData['real_amount'] : null; // 如果需要用到實際到賬金額

            // 檢查從 result 中解析出的關鍵業務數據是否完整
            if (empty($orderSN) || $paidAmount === null) {
                throw new \Exception('解析後的 result 數據不完整 (缺少 orderid 或 amount)');
            }

            // 5. 根據頂層 status 處理業務邏輯
            if ($notificationData['status'] == 10000) { // 支付成功狀態
                // 調用訂單處理服務完成訂單
                // 注意：completedOrder 方法內部應有防止重複處理的機制
                $this->orderProcessService->completedOrder(
                    $orderSN,
                    $paidAmount,
                    $transactionId ?? ('RMPAY_' . $orderSN) // 如果沒有 transactionid，可以生成一個
                );

                Log::info('RMPay 訂單支付成功並處理完成', [
                    'orderSN' => $orderSN,
                    'paid_amount' => $paidAmount,
                    'trade_no' => $transactionId
                ]);
            } else {
                // 處理其他狀態 (例如支付失敗或處理中)
                Log::warning('RMPay 回調通知狀態非成功', [
                    'orderSN' => $orderSN,
                    'top_level_status' => $notificationData['status'],
                    'result_data' => $resultData
                ]);
                // 根據業務需求決定是否需要更新訂單狀態或做其他處理
            }

            // 向上游支付平台返回 'success'，表示通知已成功處理
            return 'success';

        } catch (\Exception $e) {
            Log::error('RMPay NotifyUrl 處理異常', [
                'message' => $e->getMessage(),
                'raw_notification_data' => $notificationData, // 記錄原始通知數據以便排查
                // 'trace' => $e->getTraceAsString() // 生產環境中可以考慮記錄完整堆棧信息
            ]);
            // 返回 'fail'，支付平台可能會根據其策略重試通知
            return 'fail';
        }
    }

    public function returnUrl(Request $request)
    {
        // 嘗試從 'orderid' 參數獲取，如果沒有，則嘗試從 'custom' 參數獲取
        $orderSN = trim($request->input('orderid', $request->input('custom', '')));
        Log::info('RMPay 同步跳轉', ['orderSN' => $orderSN, 'params' => $request->all()]);

        if (empty($orderSN)) {
            Log::warning('RMPay returnUrl 未獲取到訂單號');
            return redirect('/')->with('error', '訂單號缺失，無法完成支付確認');
        }

        // 跳轉到訂單詳情頁面或支付成功頁面
        return redirect('/order/detail/' . $orderSN)->with('success', '支付處理中，請稍候查看訂單狀態');
    }

    private function generateSign($data)
    {
        // 按照 ASCII 碼從小到大排序
        ksort($data);

        $signString = '';
        foreach ($data as $key => $value) {
            // 所有參數都要參與簽名，包括空值
            $signString .= $key . '=' . $value . '&';
        }
        
        // 拼接密鑰
        $signString .= 'key=' . $this->merchantKey;

        // MD5 簽名並轉為大寫
        $sign = strtoupper(md5($signString));

        Log::info('RMPay 簽名生成', [
            'sign_string' => $signString,
            'sign' => $sign
        ]);

        return $sign;
    }

    private function getChannelByPayway($payway)
    {
        // 根據支付方式返回對應的channel值，只支援908和909
        $channels = [
            'bank_transfer' => 908,  // 银行转帐
            'qr_code'      => 909,  // 扫码支付
        ];

        return $channels[$payway] ?? 908; // 默認使用银行转帐
    }

    private function sendRequest($url, $data, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (compatible; RMPay-Client/1.0)'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('RMPay HTTP 請求錯誤', ['error' => $error, 'url' => $url]);
            throw new \Exception('HTTP 請求失敗: ' . $error);
        }

        if ($httpCode !== 200) {
            Log::error('RMPay HTTP 狀態碼錯誤', ['http_code' => $httpCode, 'response' => $response]);
            throw new \Exception('HTTP 請求失敗，狀態碼: ' . $httpCode);
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('RMPay 響應JSON解析失敗', ['response' => $response, 'error' => json_last_error_msg()]);
            throw new \Exception('響應JSON解析失敗: ' . json_last_error_msg());
        }

        return $decodedResponse;
    }
} 