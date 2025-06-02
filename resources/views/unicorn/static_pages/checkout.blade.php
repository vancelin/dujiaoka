@extends('unicorn.layouts.seo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">{{ __('cart.checkout') }}</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ url('create-order') }}" method="post" id="checkout-form">
                        {{ csrf_field() }}
                        <input type="hidden" name="cart_checkout" value="1">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('dujiaoka.email') }} *</label>
                            <input type="email" class="form-control" name="email" id="email" required>
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
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="ali-icon">&#xe703;</i> {{ __('cart.submit_order') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('cart.order_summary') }}</h5>
                    
                    @foreach($cartItems as $item)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <small>{{ $item->goods->gd_name }}</small>
                            <br>
                            <small class="text-muted">{{ __('cart.quantity') }}: {{ $item->quantity }}</small>
                        </div>
                        <span>${{ number_format($item->goods->actual_price * $item->quantity, 2) }}</span>
                    </div>
                    @endforeach
                    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>{{ __('cart.total') }}:</strong>
                        <strong>${{ number_format($totalPrice, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
// 安全的 jQuery 初始化函數
function initCheckoutPage() {
    if (typeof $ === 'undefined') {
        // 如果 jQuery 還沒加載，等待 100ms 後重試
        setTimeout(initCheckoutPage, 100);
        return;
    }
    
    $(document).ready(function() {
        // 支付方式選擇
        $('.payway-radio').change(function() {
            $('input[name="payway"]').val($(this).val());
        });
    });
}

// 開始初始化
initCheckoutPage();
</script>
@endsection 