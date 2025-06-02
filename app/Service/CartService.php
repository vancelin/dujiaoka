<?php

namespace App\Service;

use App\Models\Cart;
use App\Models\Goods;
use App\Exceptions\RuleValidationException;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * 獲取會話ID
     */
    private function getSessionId()
    {
        if (!Session::has('cart_session_id')) {
            Session::put('cart_session_id', Session::getId());
        }
        return Session::get('cart_session_id');
    }

    /**
     * 加入購物車
     */
    public function addToCart($goodsId, $quantity = 1)
    {
        // 驗證商品並載入卡密計數
        $goods = Goods::withCount(['carmis' => function($query) {
            $query->where('status', \App\Models\Carmis::STATUS_UNSOLD);
        }])->find($goodsId);
        
        if (!$goods || $goods->is_open != Goods::STATUS_OPEN) {
            throw new RuleValidationException(__('cart.product_not_available'));
        }

        // 檢查庫存
        if ($quantity > $goods->in_stock) {
            throw new RuleValidationException(__('cart.insufficient_stock'));
        }

        $sessionId = $this->getSessionId();

        // 檢查是否已存在
        $cartItem = Cart::where('session_id', $sessionId)
                       ->where('goods_id', $goodsId)
                       ->first();

        if ($cartItem) {
            // 更新數量
            $newQuantity = $cartItem->quantity + $quantity;
            
            // 檢查限購
            if ($goods->buy_limit_num > 0 && $newQuantity > $goods->buy_limit_num) {
                throw new RuleValidationException(__('cart.exceeds_purchase_limit'));
            }
            
            // 檢查庫存
            if ($newQuantity > $goods->in_stock) {
                throw new RuleValidationException(__('cart.insufficient_stock'));
            }
            
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            // 新增購物車項目
            if ($goods->buy_limit_num > 0 && $quantity > $goods->buy_limit_num) {
                throw new RuleValidationException(__('cart.exceeds_purchase_limit'));
            }
            
            Cart::create([
                'session_id' => $sessionId,
                'goods_id' => $goodsId,
                'quantity' => $quantity
            ]);
        }
    }

    /**
     * 獲取購物車商品
     */
    public function getCartItems()
    {
        $sessionId = $this->getSessionId();
        
        return Cart::with(['goods' => function($query) {
            $query->withCount(['carmis' => function($query) {
                $query->where('status', \App\Models\Carmis::STATUS_UNSOLD);
            }]);
        }])
        ->where('session_id', $sessionId)
        ->get();
    }

    /**
     * 獲取購物車商品數量
     */
    public function getCartCount()
    {
        $sessionId = $this->getSessionId();
        
        return Cart::where('session_id', $sessionId)->sum('quantity');
    }

    /**
     * 獲取總價格
     */
    public function getTotalPrice()
    {
        $cartItems = $this->getCartItems();
        $total = 0;
        
        foreach ($cartItems as $item) {
            $total += $item->goods->actual_price * $item->quantity;
        }
        
        return $total;
    }

    /**
     * 更新數量
     */
    public function updateQuantity($cartId, $quantity)
    {
        $sessionId = $this->getSessionId();
        
        $cartItem = Cart::where('id', $cartId)
                       ->where('session_id', $sessionId)
                       ->first();
        
        if (!$cartItem) {
            throw new RuleValidationException(__('cart.cart_item_not_found'));
        }
        
        // 重新載入商品並包含卡密計數
        $goods = Goods::withCount(['carmis' => function($query) {
            $query->where('status', \App\Models\Carmis::STATUS_UNSOLD);
        }])->find($cartItem->goods_id);
        
        // 檢查庫存
        if ($quantity > $goods->in_stock) {
            throw new RuleValidationException(__('cart.insufficient_stock'));
        }
        
        // 檢查限購
        if ($goods->buy_limit_num > 0 && $quantity > $goods->buy_limit_num) {
            throw new RuleValidationException(__('cart.exceeds_purchase_limit'));
        }
        
        if ($quantity <= 0) {
            $cartItem->delete();
        } else {
            $cartItem->quantity = $quantity;
            $cartItem->save();
        }
    }

    /**
     * 移除商品
     */
    public function removeFromCart($cartId)
    {
        $sessionId = $this->getSessionId();
        
        Cart::where('id', $cartId)
            ->where('session_id', $sessionId)
            ->delete();
    }

    /**
     * 清空購物車
     */
    public function clearCart()
    {
        $sessionId = $this->getSessionId();
        
        Cart::where('session_id', $sessionId)->delete();
    }
} 