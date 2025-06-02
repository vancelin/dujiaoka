@extends('unicorn.layouts.seo')
@section('content')
    <!-- main start -->
    <section class="main-container">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">{{ $gd_name }}</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="{{ picture_ulr($picture) }}" class="img-fluid" alt="{{ $gd_name }}">
                                </div>
                                <div class="col-md-8">
                                    <h3>{{ $gd_name }}</h3>
                                    <p class="text-muted">{{ $gd_description }}</p>
                                    
                                    <div class="mb-3">
                                        @if($type == \App\Models\Goods::AUTOMATIC_DELIVERY)
                                            <span class="badge bg-success">{{ __('goods.fields.automatic_delivery') }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ __('goods.fields.manual_processing') }}</span>
                                        @endif
                                        <span class="badge bg-info">{{ __('goods.fields.in_stock') }}({{ $in_stock }})</span>
                                        @if($buy_limit_num > 0)
                                            <span class="badge bg-danger">{{ __('dujiaoka.purchase_limit') }}({{ $buy_limit_num }})</span>
                                        @endif
                                    </div>
                                    
                                    <h4 class="text-success">{{ __('dujiaoka.money_symbol') }} {{ $actual_price }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($description)
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('dujiaoka.product_description') }}</h5>
                            {!! $description !!}
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ url('create-order') }}" method="post" id="buy-form">
                                {{ csrf_field() }}
                                <input type="hidden" name="gid" value="{{ $id }}">
                                

                                <div class="mb-3">
                                    <label for="by_amount" class="form-label">{{ __('dujiaoka.by_amount') }} *</label>
                                    <input type="number" class="form-control" name="by_amount" id="by_amount" 
                                           value="1" min="1" max="{{ $in_stock }}" required>
                                </div>
                                
                                @if(isset($open_coupon))
                                <div class="mb-3">
                                    <label for="coupon_code" class="form-label">{{ __('dujiaoka.coupon_code') }}</label>
                                    <input type="text" class="form-control" name="coupon_code" id="coupon_code">
                                </div>
                                @endif
                                
                                <div class="mb-3">
                                    <label class="form-label">{{ __('dujiaoka.payment_method') }} *</label>
                                    <input type="hidden" name="payway" value="{{ $payways[0]['id'] ?? 0 }}">
                                    @foreach($payways as $key => $way)
                                    <div class="form-check">
                                        <input class="form-check-input payway-radio" type="radio" name="payway_display" 
                                               id="payway{{ $way['id'] }}" value="{{ $way['id'] }}" 
                                               {{ $key == 0 ? 'checked' : '' }}>
                                        <label class="form-check-label" for="payway{{ $way['id'] }}">
                                            {{ $way['pay_name'] }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" id="add-to-cart">
                                        <i class="ali-icon">&#xe7d8;</i> {{ __('cart.add_to_cart') }}
                                    </button>
                                    <button type="submit" class="btn btn-success" id="buy-now">
                                        <i class="ali-icon">&#xe703;</i> {{ __('cart.buy_now') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- main end -->
@stop

@section('js')
    <script>
    $(document).ready(function() {
        // 支付方式選擇
        $('.payway-radio').change(function() {
            $('input[name="payway"]').val($(this).val());
        });
        
        // 加入購物車
        $('#add-to-cart').click(function() {
            var quantity = $('#by_amount').val();
            var goodsId = $('input[name="gid"]').val();
            
            if (!quantity || quantity < 1) {
                alert('{{ __("cart.please_enter_correct_quantity") }}');
                return;
            }
            
            if (quantity > {{ $in_stock }}) {
                alert('{{ __("cart.quantity_exceeds_stock") }}');
                return;
            }
            
            @if($buy_limit_num > 0)
            if (quantity > {{ $buy_limit_num }}) {
                alert('{{ __("cart.quantity_exceeds_limit") }}');
                return;
            }
            @endif
            
            // 使用全局購物車對象
            if (window.cart) {
                window.cart.addToCart(goodsId, quantity)
                    .done(function(response) {
                        if (response.success) {
                            alert(response.message);
                            // 觸發購物車更新事件
                            window.cart.triggerUpdate();
                        } else {
                            alert(response.message);
                        }
                    })
                    .fail(function(xhr) {
                        if (xhr.status === 404) {
                            alert('{{ __("cart.cart_feature_not_available") }}');
                        } else {
                            alert('{{ __("cart.add_to_cart_failed") }}');
                        }
                    });
            } else {
                // 備用方案
                $.ajax({
                    url: '{{ url("cart/add") }}',
                    method: 'POST',
                    data: {
                        goods_id: goodsId,
                        quantity: quantity,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            // 更新購物車數量顯示
                            if ($('.cart-count').length) {
                                $('.cart-count').text(response.cart_count);
                                $('.cart-count').show();
                            }
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 404) {
                            alert('{{ __("cart.cart_feature_not_available") }}');
                        } else {
                            alert('{{ __("cart.add_to_cart_failed") }}');
                        }
                    }
                });
            }
        });
        
        // 立即購買驗證
        $('#buy-now').click(function(e) {
            var quantity = $('#by_amount').val();
            
            if (!quantity || quantity < 1) {
                e.preventDefault();
                alert('{{ __("cart.please_enter_correct_quantity") }}');
                return;
            }
            
            if (quantity > {{ $in_stock }}) {
                e.preventDefault();
                alert('{{ __("cart.quantity_exceeds_stock") }}');
                return;
            }
            
            @if($buy_limit_num > 0)
            if (quantity > {{ $buy_limit_num }}) {
                e.preventDefault();
                alert('{{ __("cart.quantity_exceeds_limit") }}');
                return;
            }
            @endif
        });
    });
    </script>
@endsection
