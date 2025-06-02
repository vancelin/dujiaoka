<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\BaseController;
use App\Models\Cart;
use App\Models\Goods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends BaseController
{
    /**
     * 購物車服務層
     */
    private $cartService;

    public function __construct()
    {
        $this->cartService = app('Service\CartService');
    }

    /**
     * 加入購物車
     */
    public function addToCart(Request $request)
    {
        try {
            $goodsId = $request->input('goods_id');
            $quantity = $request->input('quantity', 1);
            
            $this->cartService->addToCart($goodsId, $quantity);
            
            return response()->json([
                'success' => true,
                'message' => __('cart.item_added_to_cart'),
                'cart_count' => $this->cartService->getCartCount()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 購物車頁面
     */
    public function index()
    {
        $cartItems = $this->cartService->getCartItems();
        $totalPrice = $this->cartService->getTotalPrice();
        
        return $this->render('static_pages/cart', [
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice
        ], __('cart.cart'));
    }

    /**
     * 獲取購物車數量
     */
    public function getCartCount()
    {
        try {
            $count = $this->cartService->getCartCount();
            
            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'count' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新購物車商品數量
     */
    public function updateQuantity(Request $request)
    {
        try {
            $cartId = $request->input('cart_id');
            $quantity = $request->input('quantity');
            
            $this->cartService->updateQuantity($cartId, $quantity);
            
            return response()->json([
                'success' => true,
                'message' => __('cart.quantity_updated'),
                'total_price' => $this->cartService->getTotalPrice()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 從購物車移除商品
     */
    public function removeFromCart(Request $request)
    {
        try {
            $cartId = $request->input('cart_id');
            
            $this->cartService->removeFromCart($cartId);
            
            return response()->json([
                'success' => true,
                'message' => __('cart.item_removed_from_cart'),
                'cart_count' => $this->cartService->getCartCount()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清空購物車
     */
    public function clearCart()
    {
        try {
            $this->cartService->clearCart();
            
            return response()->json([
                'success' => true,
                'message' => __('cart.cart_cleared')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 購物車結算
     */
    public function checkout()
    {
        $cartItems = $this->cartService->getCartItems();
        
        if (empty($cartItems) || count($cartItems) == 0) {
            return $this->err(__('cart.cart_is_empty'));
        }
        
        $totalPrice = $this->cartService->getTotalPrice();
        
        // 獲取支付方式 - 直接實例化避免服務容器緩存問題
        $payService = new \App\Service\PayService();
        $payways = $payService->payList();
        
        return $this->render('static_pages/checkout', [
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
            'payways' => $payways
        ], __('cart.checkout'));
    }
} 