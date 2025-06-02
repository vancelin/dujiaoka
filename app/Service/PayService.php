<?php
/**
 * The file was created by Assimon.
 *
 * @author    assimon<ashang@utf8.hk>
 * @copyright assimon<ashang@utf8.hk>
 * @link      http://utf8.hk/
 */

namespace App\Service;


use App\Models\Pay;

class PayService
{

    /**
     * 加载支付网关
     *
     * @param string|int $payClient 支付场景客户端
     * @return array|null
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function pays(string $payClient = Pay::PAY_CLIENT_PC): ?array
    {
        $payGateway = Pay::query()
            ->whereIn('pay_client', [$payClient, Pay::PAY_CLIENT_ALL])
            ->where('is_open', Pay::STATUS_OPEN)
            ->get();
        return $payGateway ? $payGateway->toArray() : null;
    }

    /**
     * 通过支付标识获得支付配置
     *
     * @param string $check 支付标识
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function detailByCheck(string $check)
    {
        $gateway = Pay::query()
            ->where('pay_check', $check)
            ->where('is_open', Pay::STATUS_OPEN)
            ->first();
        return $gateway;
    }

    /**
     * 通过id查询支付网关
     *
     * @param int $id 支付网关id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function detail(int $id)
    {
        $gateway = Pay::query()
            ->where('id', $id)
            ->where('is_open', Pay::STATUS_OPEN)
            ->first();
        return $gateway;
    }

    /**
     * 获取支付方式列表（用于购物车结算）
     *
     * @return array|null
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function payList(): ?array
    {
        // 根据设备类型选择支付客户端
        $client = Pay::PAY_CLIENT_PC;
        
        try {
            // 嘗試使用註冊的 Agent 服務
            if (app()->bound('Jenssegers\Agent')) {
                $agent = app('Jenssegers\Agent');
            } else {
                // 直接實例化 Agent
                $agent = new \Jenssegers\Agent\Agent();
            }
            
            if ($agent->isMobile()) {
                $client = Pay::PAY_CLIENT_MOBILE;
            }
        } catch (\Exception $e) {
            // 如果 Agent 不可用，默認使用 PC 客戶端
            $client = Pay::PAY_CLIENT_PC;
        }
        
        return $this->pays($client);
    }

}
