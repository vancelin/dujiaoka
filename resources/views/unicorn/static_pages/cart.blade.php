@extends('unicorn.layouts.seo')

@section('content')
<div class="container-fluid cart-page">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">{{ __('cart.cart') }}</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if(count($cartItems) > 0)
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('cart.product') }}</th>
                                        <th>{{ __('cart.price') }}</th>
                                        <th>{{ __('cart.quantity') }}</th>
                                        <th>{{ __('cart.subtotal') }}</th>
                                        <th>{{ __('cart.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cartItems as $item)
                                    <tr data-cart-id="{{ $item->id }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ picture_ulr($item->goods->picture) }}" 
                                                     alt="{{ $item->goods->gd_name }}" 
                                                     class="me-3 cart-item-image">
                                                <div>
                                                    <h6 class="mb-0">{{ $item->goods->gd_name }}</h6>
                                                    <small class="text-muted">{{ __('goods.fields.in_stock') }}: {{ $item->goods->in_stock }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="price">${{ number_format($item->goods->actual_price, 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="input-group quantity-control" style="width: 140px;">
                                                <button class="btn quantity-minus" type="button" title="{{ __('cart.decrease_quantity') }}">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control quantity-input" 
                                                       value="{{ $item->quantity }}" min="1" max="{{ $item->goods->in_stock }}"
                                                       title="{{ __('cart.quantity') }}">
                                                <button class="btn quantity-plus" type="button" title="{{ __('cart.increase_quantity') }}">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="subtotal">${{ number_format($item->goods->actual_price * $item->quantity, 2) }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger btn-sm remove-item">
                                                <i class="ali-icon">&#xe74b;</i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8">
                        <button class="btn btn-outline-danger" id="clear-cart">
                            <i class="ali-icon">&#xe74b;</i> {{ __('cart.clear_cart') }}
                        </button>
                    </div>
                    <div class="col-md-4">
                        <div class="card cart-summary">
                            <div class="card-body">
                                <h5 class="card-title">{{ __('cart.order_summary') }}</h5>
                                <div class="d-flex justify-content-between">
                                    <span>{{ __('cart.total') }}:</span>
                                    <span class="total-price" id="total-price">${{ number_format($totalPrice, 2) }}</span>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ url('cart/checkout') }}" class="btn btn-success btn-lg w-100">
                                        <i class="ali-icon">&#xe703;</i> {{ __('cart.checkout') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center cart-empty">
                        <i class="ali-icon">&#xe7d8;</i>
                        <h4 class="mt-3">{{ __('cart.cart_is_empty') }}</h4>
                        <p class="text-muted">{{ __('cart.no_items_in_cart') }}</p>
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="ali-icon">&#xe77a;</i> {{ __('cart.continue_shopping') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
// 安全的 jQuery 初始化函數
function initCartPage() {
    if (typeof $ === 'undefined') {
        // 如果 jQuery 還沒加載，等待 100ms 後重試
        setTimeout(initCartPage, 100);
        return;
    }
    
    $(document).ready(function() {
        // 數量增加
        $('.quantity-plus').click(function() {
            var input = $(this).siblings('.quantity-input');
            var currentVal = parseInt(input.val());
            var maxVal = parseInt(input.attr('max'));
            if (currentVal < maxVal) {
                input.val(currentVal + 1);
                updateQuantity($(this).closest('tr'));
            }
        });
        
        // 數量減少
        $('.quantity-minus').click(function() {
            var input = $(this).siblings('.quantity-input');
            var currentVal = parseInt(input.val());
            if (currentVal > 1) {
                input.val(currentVal - 1);
                updateQuantity($(this).closest('tr'));
            }
        });
        
        // 直接輸入數量
        $('.quantity-input').change(function() {
            updateQuantity($(this).closest('tr'));
        });
        
        // 移除商品
        $('.remove-item').click(function() {
            var cartItem = $(this).closest('tr');
            var cartId = cartItem.data('cart-id');
            
            if (confirm('{{ __("cart.confirm_remove_item") }}')) {
                $.ajax({
                    url: '{{ url("cart/remove") }}',
                    method: 'POST',
                    data: {
                        cart_id: cartId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            cartItem.remove();
                            updateTotalPrice();
                            // 觸發購物車更新事件
                            if (window.cart) {
                                window.cart.triggerUpdate();
                            }
                            // 如果購物車空了，刷新頁面
                            if ($('tbody tr').length === 0) {
                                location.reload();
                            }
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('{{ __("cart.remove_failed") }}');
                    }
                });
            }
        });
        
        // 清空購物車
        $('#clear-cart').click(function() {
            if (confirm('{{ __("cart.confirm_clear_cart") }}')) {
                $.ajax({
                    url: '{{ url("cart/clear") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('{{ __("cart.clear_failed") }}');
                    }
                });
            }
        });
        
        function updateQuantity(cartItem) {
            var cartId = cartItem.data('cart-id');
            var quantity = cartItem.find('.quantity-input').val();
            
            $.ajax({
                url: '{{ url("cart/update") }}',
                method: 'POST',
                data: {
                    cart_id: cartId,
                    quantity: quantity,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#total-price').text('$' + parseFloat(response.total_price).toFixed(2));
                        // 更新小計
                        var price = parseFloat(cartItem.find('.price').text().replace('$', ''));
                        var subtotal = price * quantity;
                        cartItem.find('.subtotal').text('$' + subtotal.toFixed(2));
                        // 觸發購物車更新事件
                        if (window.cart) {
                            window.cart.triggerUpdate();
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('{{ __("cart.update_failed") }}');
                }
            });
        }
        
        function updateTotalPrice() {
            var total = 0;
            $('tbody tr').each(function() {
                var subtotal = parseFloat($(this).find('.subtotal').text().replace('$', ''));
                total += subtotal;
            });
            $('#total-price').text('$' + total.toFixed(2));
        }
    });
}

// 開始初始化
initCartPage();
</script>
@endsection 