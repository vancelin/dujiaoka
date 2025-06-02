<?php

namespace App\Http\Controllers\Home;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\BaseController;
use App\Models\Order;
use App\Service\OrderProcessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;


/**
 * 订单控制器
 *
 * Class OrderController
 * @package App\Http\Controllers\Home
 * @author: Assimon
 * @email: Ashang@utf8.hk
 * @blog: https://utf8.hk
 * Date: 2021/5/30
 */
class OrderController extends BaseController
{


    /**
     * 订单服务层
     * @var \App\Service\OrderService
     */
    private $orderService;

    /**
     * 订单处理层.
     * @var OrderProcessService
     */
    private $orderProcessService;

    /**
     * 商品服务层
     * @var \App\Service\GoodsService
     */
    private $goodsService;

    public function __construct()
    {
        $this->orderService = app('Service\OrderService');
        $this->orderProcessService = app('Service\OrderProcessService');
        $this->goodsService = app('Service\GoodsService');
    }

    /**
     * 创建订单
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Validation\ValidationException
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function createOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            // 檢查是否為購物車結帳
            $isCartCheckout = $request->has('cart_checkout');
            
            if ($isCartCheckout) {
                // 購物車結帳流程
                $this->orderService->validatorCartCheckout($request);
                $cartService = app('Service\CartService');
                $cartItems = $cartService->getCartItems();
                
                if (empty($cartItems)) {
                    throw new RuleValidationException(__('cart.cart_is_empty'));
                }
                
                // 創建購物車訂單
                $cartOrder = $this->createCartOrder($cartItems, $request);
                
                // 清空購物車
                $cartService->clearCart();
                
                DB::commit();
                
                // 重定向到訂單支付頁面
                return redirect(url('/bill', ['orderSN' => $cartOrder->order_sn]));
                
            } else {
                // 原有的立即購買流程，但不需要 email
                $this->orderService->validatorCreateOrderWithoutEmail($request);
                $goods = $this->orderService->validatorGoods($request);
                $this->orderService->validatorLoopCarmis($request);
                // 设置商品
                $this->orderProcessService->setGoods($goods);
                // 优惠码
                $coupon = $this->orderService->validatorCoupon($request);
                // 设置优惠码
                $this->orderProcessService->setCoupon($coupon);
                $otherIpt = $this->orderService->validatorChargeInput($goods, $request);
                $this->orderProcessService->setOtherIpt($otherIpt);
                // 数量
                $this->orderProcessService->setBuyAmount($request->input('by_amount'));
                // 支付方式
                $this->orderProcessService->setPayID($request->input('payway'));
                // 下单邮箱 - 立即購買時不需要 email，在支付頁面輸入
                $this->orderProcessService->setEmail('');
                // 下单姓名
                $this->orderProcessService->setName($request->input('name'));
                // 購買電話
                $this->orderProcessService->setPhone($request->input('phone'));
                // ip地址
                $this->orderProcessService->setBuyIP($request->getClientIp());
                // 查询密码
                $this->orderProcessService->setSearchPwd($request->input('search_pwd', ''));
                // 创建订单
                $order = $this->orderProcessService->createOrder();
                DB::commit();
                // 设置订单cookie
                $this->queueCookie($order->order_sn);
                return redirect(url('/bill', ['orderSN' => $order->order_sn]));
            }
        } catch (RuleValidationException $exception) {
            DB::rollBack();
            return $this->err($exception->getMessage());
        }
    }

    /**
     * 創建購物車訂單
     * 
     * @param array $cartItems
     * @param Request $request
     * @return \App\Models\Order
     */
    private function createCartOrder($cartItems, $request)
    {
        // 計算總價和構建訂單標題
        $totalPrice = 0;
        $orderTitles = [];
        $orderInfo = [];
        $firstGoods = null;
        
        foreach ($cartItems as $cartItem) {
            $goods = $cartItem->goods;
            
            // 验证商品状态
            $this->goodsService->validatorGoodsStatus($goods);
            
            // 库存验证
            if ($cartItem->quantity > $goods->in_stock) {
                throw new RuleValidationException(__('dujiaoka.prompt.inventory_shortage') . ': ' . $goods->gd_name);
            }
            
            // 限购验证
            if ($goods->buy_limit_num > 0 && $cartItem->quantity > $goods->buy_limit_num) {
                throw new RuleValidationException(__('dujiaoka.prompt.purchase_limit_exceeded') . ': ' . $goods->gd_name);
            }
            
            // 記錄第一個商品（用於訂單基本信息）
            if ($firstGoods === null) {
                $firstGoods = $goods;
            }
            
            // 計算商品價格
            $itemPrice = $goods->actual_price * $cartItem->quantity;
            $totalPrice += $itemPrice;
            
            // 構建訂單標題和信息
            $orderTitles[] = $goods->gd_name . ' x ' . $cartItem->quantity;
            $orderInfo[] = sprintf(
                "%s: %s%s x %d = %s%s",
                $goods->gd_name,
                dujiaoka_config_get('global_currency', '$'),
                $goods->actual_price,
                $cartItem->quantity,
                dujiaoka_config_get('global_currency', '$'),
                $itemPrice
            );
        }
        
        // 創建訂單
        $order = new Order();
        $order->order_sn = date('YmdHis') . rand(100000, 999999);
        $order->title = implode(' + ', $orderTitles);
        $order->goods_id = $firstGoods->id; // 使用第一個商品的ID
        $order->goods_price = $firstGoods->actual_price;
        $order->buy_amount = array_sum(array_column($cartItems->toArray(), 'quantity'));
        $order->total_price = $totalPrice;
        $order->actual_price = $totalPrice;
        $order->email = $request->input('email', '');
        $order->info = "購物車訂單:\n" . implode("\n", $orderInfo);
        $order->pay_id = $request->input('payway');
        $order->buy_ip = $request->getClientIp();
        $order->search_pwd = $request->input('search_pwd', '');
        $order->status = Order::STATUS_WAIT_PAY;
        $order->type = Order::AUTOMATIC_DELIVERY; // 假設都是自動發貨
        $order->save();
        
        // 設置訂單cookie
        $this->queueCookie($order->order_sn);
        
        return $order;
    }

    /**
     * 设置订单cookie.
     * @param string $orderSN 订单号.
     */
    private function queueCookie(string $orderSN) : void
    {
        // 设置订单cookie
        $cookies = Cookie::get('dujiaoka_orders');
        if (empty($cookies)) {
            Cookie::queue('dujiaoka_orders', json_encode([$orderSN]));
        } else {
            $cookies = json_decode($cookies, true);
            array_push($cookies, $orderSN);
            Cookie::queue('dujiaoka_orders', json_encode($cookies));
        }
    }

    /**
     * 结账
     *
     * @param string $orderSN
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function bill(string $orderSN)
    {
        $order = $this->orderService->detailOrderSN($orderSN);
        if (empty($order)) {
            return $this->err(__('dujiaoka.prompt.order_does_not_exist'));
        }
        if ($order->status == Order::STATUS_EXPIRED) {
            return $this->err(__('dujiaoka.prompt.order_is_expired'));
        }
        return $this->render('static_pages/bill', $order, __('dujiaoka.page-title.bill'));
    }


    /**
     * 订单状态监测
     *
     * @param string $orderSN 订单号
     * @return \Illuminate\Http\JsonResponse
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function checkOrderStatus(string $orderSN)
    {
        $order = $this->orderService->detailOrderSN($orderSN);
        // 订单不存在或者已经过期
        if (!$order || $order->status == Order::STATUS_EXPIRED) {
            return response()->json(['msg' => 'expired', 'code' => 400001]);
        }
        // 订单已经支付
        if ($order->status == Order::STATUS_WAIT_PAY) {
            return response()->json(['msg' => 'wait....', 'code' => 400000]);
        }
        // 成功
        if ($order->status > Order::STATUS_WAIT_PAY) {
            return response()->json(['msg' => 'success', 'code' => 200]);
        }
    }

    /**
     * 通过订单号展示订单详情
     *
     * @param string $orderSN 订单号.
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function detailOrderSN(string $orderSN)
    {
        $order = $this->orderService->detailOrderSN($orderSN);
        // 订单不存在或者已经过期
        if (!$order) {
            return $this->err(__('dujiaoka.prompt.order_does_not_exist'));
        }
        return $this->render('static_pages/orderinfo', ['orders' => [$order]], __('dujiaoka.page-title.order-detail'));
    }

    /**
     * 订单号查询
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function searchOrderBySN(Request $request)
    {
        return $this->detailOrderSN($request->input('order_sn'));
    }

    /**
     * 通过邮箱查询
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function searchOrderByEmail(Request $request)
    {
        if (
            !$request->has('email') ||
            (
                dujiaoka_config_get('is_open_search_pwd', \App\Models\BaseModel::STATUS_CLOSE) == \App\Models\BaseModel::STATUS_OPEN &&
                !$request->has('search_pwd')
            )
        ) {
            return $this->err(__('dujiaoka.prompt.server_illegal_request'));
        }
        $orders = $this->orderService->withEmailAndPassword($request->input('email'), $request->input('search_pwd',''));
        if (!$orders) {
            return $this->err(__('dujiaoka.prompt.no_related_order_found'));
        }
        return $this->render('static_pages/orderinfo', ['orders' => $orders], __('dujiaoka.page-title.order-detail'));
    }

    /**
     * 通过浏览器缓存查询
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function searchOrderByBrowser(Request $request)
    {
        $cookies = Cookie::get('dujiaoka_orders');
        if (empty($cookies)) {
            return $this->err(__('dujiaoka.prompt.no_related_order_found_for_cache'));
        }
        $orderSNS = json_decode($cookies, true);
        $orders = $this->orderService->byOrderSNS($orderSNS);
        return $this->render('static_pages/orderinfo', ['orders' => $orders], __('dujiaoka.page-title.order-detail'));
    }

    /**
     * 更新訂單 email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrderEmail(Request $request)
    {
        try {
            $orderSN = $request->input('order_sn');
            $email = $request->input('email');
            
            // 驗證 email 格式
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => __('dujiaoka.prompt.email_format_error')
                ]);
            }
            
            // 查找訂單
            $order = Order::where('order_sn', $orderSN)->first();
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => __('dujiaoka.prompt.order_does_not_exist')
                ]);
            }
            
            // 更新 email
            $order->email = $email;
            $order->save();
            
            return response()->json([
                'success' => true,
                'message' => __('dujiaoka.email_updated_successfully')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('dujiaoka.update_email_failed')
            ]);
        }
    }

    /**
     * 订单查询页
     *
     * @param Request $request
     * @return mixed
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function orderSearch(Request $request)
    {
        return $this->render('static_pages/searchOrder', [], __('dujiaoka.page-title.order-search'));
    }

}
