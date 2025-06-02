@extends('unicorn.layouts.default')
@section('content')
    <!-- main start -->
    <section class="main-container">
        <div class="container">
            <div class="good-card">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card m-3">
                            <div class="card-body p-2 text-center">
                                <h3 class="card-title text-primary ali-icon">&#xe832;{{ __('dujiaoka.confirm_order') }}</h3>
                            </div>
                            <div class="card card-body p-3 border-0">
                                <div class="mx-auto">
                                    <h5>
                                        <small class="text-muted">{{ __('order.fields.order_sn') }}：{{ $order_sn }}</small>
                                    </h5>
                                    
                                    @php
                                        // 檢查是否為購物車訂單
                                        $isCartOrder = strpos($info ?? '', '購物車訂單:') === 0;
                                    @endphp
                                    
                                    @if($isCartOrder)
                                        <!-- 購物車訂單顯示 -->
                                        <div class="mb-3">
                                            <h6 class="text-primary">{{ __('cart.order_items') }}</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>{{ __('cart.product') }}</th>
                                                            <th>{{ __('cart.price') }}</th>
                                                            <th>{{ __('cart.quantity') }}</th>
                                                            <th>{{ __('cart.subtotal') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $cartInfo = $info ?? '';
                                                            $lines = explode("\n", $cartInfo);
                                                            array_shift($lines); // 移除第一行 "購物車訂單:"
                                                        @endphp
                                                        @foreach($lines as $line)
                                                            @if(trim($line))
                                                                @php
                                                                    // 解析格式: "商品名: $價格 x 數量 = $小計"
                                                                    preg_match('/^(.+?):\s*(.+?)\s*x\s*(\d+)\s*=\s*(.+)$/', trim($line), $matches);
                                                                    if (count($matches) >= 5) {
                                                                        $productName = $matches[1];
                                                                        $price = $matches[2];
                                                                        $quantity = $matches[3];
                                                                        $subtotal = $matches[4];
                                                                    }
                                                                @endphp
                                                                @if(isset($productName))
                                                                    <tr>
                                                                        <td>{{ $productName }}</td>
                                                                        <td>{{ $price }}</td>
                                                                        <td>{{ $quantity }}</td>
                                                                        <td>{{ $subtotal }}</td>
                                                                    </tr>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-1">
                                            <label>{{ __('order.fields.total_items') }}：</label><span>{{ $buy_amount }} {{ __('cart.items') }}</span>
                                        </div>
                                    @else
                                        <!-- 單一商品訂單顯示 -->
                                        <div class="mb-1">
                                            <label>{{ __('order.fields.title') }}：</label><span>{{ $title }}</span>
                                        </div>
                                        <div class="mb-1"><label>{{ __('order.fields.goods_price') }}：</label><span> {{ $goods_price }}</span></div>
                                        <div class="mb-1"><label>{{ __('order.fields.buy_amount') }}：</label><span>{{ $buy_amount }}</span></div>
                                    @endif
                                    
                                    @if(empty($email))
                                        <div class="mb-3">
                                            <label for="order_email" class="form-label">{{ __('order.fields.email') }} *</label>
                                            <input type="email" class="form-control" id="order_email" name="email" required>
                                            <small class="text-muted">{{ __('dujiaoka.email_for_order_notification') }}</small>
                                        </div>
                                    @else
                                        <div class="mb-1"><label>{{ __('order.fields.email') }}：</label><span>{{ $email }}</span></div>
                                    @endif
                                    <div class="mb-1">
                                        <label>{{ __('order.fields.type') }}：</label>
                                        @if($type == \App\Models\Order::AUTOMATIC_DELIVERY)
                                            <span class="badge bg-success">{{ __('goods.fields.automatic_delivery') }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ __('goods.fields.manual_processing') }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($coupon))
                                        <div class="mb-1"><label>{{ __('order.fields.coupon_id') }}：</label><span>{{ $coupon['coupon'] }}</span></div>
                                        <div class="mb-1"><label>{{ __('order.fields.coupon_discount_price') }}：</label><span>{{ __('dujiaoka.money_symbol') }}{{ $coupon_discount_price }}</span></div>
                                    @endif
                                    @if($wholesale_discount_price > 0 )
                                        <div class="mb-1"><label>{{ __('order.fields.wholesale_discount_price') }}：</label><span>{{ __('dujiaoka.money_symbol') }}{{ $wholesale_discount_price }}</span></div>
                                    @endif
                                    @if(!empty($info) && !$isCartOrder)
                                        <div class="mb-1"><label>{{ __('dujiaoka.order_information') }}：</label><p>{{ $info }}</p></div>
                                    @endif
                                    <div class="mb-1"><label>{{ __('order.fields.actual_price') }}：</label><span class="fw-bold text-success">{{ __('dujiaoka.money_symbol') }}{{ $actual_price }}</span></div>
                                    <div class="mb-1"><label>{{ __('dujiaoka.payment_method') }}：</label><span>{{ $pay['pay_name'] }}</span></div>
                                    <div class="mb-1"><label>{{ __('order.fields.order_created') }}：</label><span>{{ $created_at }}</span></div>

                                    <div class="pay-now text-center mt-3">
                                        @if(empty($email))
                                            <button type="button" class="btn btn-dark" id="update-email-and-pay"><i class="ali-icon">&#xe673;</i>
                                                {{ __('dujiaoka.pay_immediately') }}
                                            </button>
                                        @else
                                            <a href="{{ url('pay-gateway', ['handle' => urlencode($pay['pay_handleroute']),'payway' => $pay['pay_check'], 'orderSN' => $order_sn]) }}" type="button" class="btn btn-dark"><i class="ali-icon">&#xe673;</i>
                                                {{ __('dujiaoka.pay_immediately') }}
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            </div>
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
    $('#update-email-and-pay').click(function() {
        var email = $('#order_email').val();
        
        if (!email) {
            alert('{{ __("dujiaoka.prompt.email_format_error") }}');
            return;
        }
        
        // 驗證 email 格式
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('{{ __("dujiaoka.prompt.email_format_error") }}');
            return;
        }
        
        // 更新訂單 email
        $.ajax({
            url: '{{ url("update-order-email") }}',
            method: 'POST',
            data: {
                order_sn: '{{ $order_sn }}',
                email: email,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // 跳轉到支付頁面
                    window.location.href = '{{ url("pay-gateway", ["handle" => urlencode($pay["pay_handleroute"]), "payway" => $pay["pay_check"], "orderSN" => $order_sn]) }}';
                } else {
                    alert(response.message || '{{ __("dujiaoka.update_email_failed") }}');
                }
            },
            error: function() {
                alert('{{ __("dujiaoka.update_email_failed") }}');
            }
        });
    });
});
</script>
@stop
