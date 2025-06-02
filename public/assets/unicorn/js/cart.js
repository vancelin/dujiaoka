// 購物車相關功能
class Cart {
    constructor() {
        this.initRetryCount = 0;
        this.maxRetries = 50; // 最多重試 5 秒
        this.init();
    }

    init() {
        if (typeof $ === 'undefined') {
            this.initRetryCount++;
            if (this.initRetryCount < this.maxRetries) {
                console.log('jQuery not loaded yet, retrying... (' + this.initRetryCount + '/' + this.maxRetries + ')');
                setTimeout(() => this.init(), 100);
                return;
            } else {
                console.error('jQuery failed to load after maximum retries');
                return;
            }
        }
        
        console.log('Cart initialized with jQuery');
        this.updateCartCount();
        this.bindEvents();
    }

    // 更新購物車數量顯示
    updateCartCount() {
        if (typeof $ === 'undefined') {
            console.warn('jQuery not loaded, skipping cart count update');
            return;
        }
        
        $.ajax({
            url: '/cart/count',
            method: 'GET',
            success: (response) => {
                if (response.success) {
                    $('.cart-count').text(response.count);
                    // 如果數量為0，隱藏徽章
                    if (response.count === 0) {
                        $('.cart-count').hide();
                    } else {
                        $('.cart-count').show();
                    }
                }
            },
            error: (xhr, status, error) => {
                console.log('無法獲取購物車數量:', error);
            }
        });
    }

    // 綁定事件
    bindEvents() {
        if (typeof $ === 'undefined') {
            console.warn('jQuery not loaded, skipping event binding');
            return;
        }
        
        // 監聽購物車更新事件
        $(document).on('cartUpdated', () => {
            this.updateCartCount();
        });
    }

    // 添加商品到購物車
    addToCart(goodsId, quantity = 1) {
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded, cannot add to cart');
            return Promise.reject('jQuery not loaded');
        }
        
        return $.ajax({
            url: '/cart/add',
            method: 'POST',
            data: {
                goods_id: goodsId,
                quantity: quantity,
                _token: $('meta[name="csrf-token"]').attr('content')
            }
        });
    }

    // 觸發購物車更新事件
    triggerUpdate() {
        if (typeof $ === 'undefined') {
            console.warn('jQuery not loaded, cannot trigger update');
            return;
        }
        
        $(document).trigger('cartUpdated');
    }
}

// 安全的初始化購物車
function initCart() {
    if (typeof window.cart !== 'undefined') {
        console.log('Cart already initialized');
        return;
    }
    
    try {
        window.cart = new Cart();
    } catch (error) {
        console.error('Failed to initialize cart:', error);
        // 如果初始化失敗，等待一下再試
        setTimeout(initCart, 1000);
    }
}

// 確保在 DOM 加載完成後初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCart);
} else {
    initCart();
} 