@extends('unicorn.layouts.default')
@section('content')
    <!-- main start -->
    <section class="main-container">
        <div class="container">
            <div class="order-card">
                <div class="row justify-content-center">

                    @foreach($orders as $order)
                    <div class="col-md-8">
                        <div class="card mt-3">
                            <div class="row no-gutters">
                                <div class="col-12 col-md-6">
                                    <div class="card card-body p-3 border-0">
                                        <div class="mx-auto">
                                            <h5>
                                                <small class="text-muted">{{ __('order.fields.order_sn') }}：{{ $order['order_sn'] }}</small>
                                            </h5>
                                            <div class="mb-1">
                                                <label>{{ __('order.fields.title') }}：</label><span>{{ $order['title'] }}</span>
                                            </div>

                                            <div class="mb-1"><label>{{ __('order.fields.buy_amount') }}：</label><span>{{ $order['buy_amount'] }}</span></div>

                                            <div class="mb-1"><label>{{ __('order.fields.order_created') }}：</label><span>{{ $order['created_at'] }}</span>
                                            </div>

                                            <div class="mb-1"><label>{{ __('order.fields.email') }}：</label><span>{{ $order['email'] }}</span></div>
                                            <div class="mb-1">

                                                <label>{{ __('order.fields.type') }}：</label>
                                                @if($order['type'] == \App\Models\Order::AUTOMATIC_DELIVERY)
                                                    <span class="badge bg-success">{{ __('goods.fields.automatic_delivery') }}</span>
                                                @else
                                                    <span class="badge bg-warning">{{ __('goods.fields.manual_processing') }}</span>
                                                @endif
                                            </div>
                                            <div class="mb-1">

                                                <label>{{ __('order.fields.actual_price') }}：</label>
                                                <span>{{ $order['actual_price'] }}</span>
                                            </div>
                                            <div class="mb-1">

                                                <label>{{ __('order.fields.status') }}：</label>
                                                @switch($order['status'])
                                                    @case(\App\Models\Order::STATUS_EXPIRED)
                                                    <span class="badge bg-dark">{{ __('order.fields.status_expired') }}</span>
                                                    @break
                                                    @case(\App\Models\Order::STATUS_WAIT_PAY)
                                                    <span class="badge bg-secondary">{{ __('order.fields.status_wait_pay') }}</span>
                                                    @break
                                                    @case(\App\Models\Order::STATUS_PENDING)
                                                    <span class="badge bg-info">{{ __('order.fields.status_pending') }}</span>
                                                    @break
                                                    @case(\App\Models\Order::STATUS_PROCESSING)
                                                    <span class="badge bg-primary">{{ __('order.fields.status_processing') }}</span>
                                                    @break
                                                    @case(\App\Models\Order::STATUS_COMPLETED)
                                                    <span class="badge bg-success">{{ __('order.fields.status_completed') }}</span>
                                                    @break
                                                    @case(\App\Models\Order::STATUS_FAILURE)
                                                    <span class="badge bg-danger">{{ __('order.fields.status_failure') }}</span>
                                                    @break
                                                    @case(\App\Models\Order::STATUS_ABNORMAL)
                                                    <span class="badge bg-danger">{{ __('order.fields.status_abnormal') }}</span>
                                                    @break
                                                @endswitch
                                            </div>
                                            <div class="mb-1">

                                                <label>{{ __('dujiaoka.payment_method') }}：</label>
                                                <span>{{ $order['pay']['pay_name'] ?? ''  }}</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ __('order.fields.info') }}</h6>
                                        
                                        @if($order['status'] == \App\Models\Order::STATUS_COMPLETED && $order['type'] == \App\Models\Order::AUTOMATIC_DELIVERY)
                                            @php
                                                $carmis = array_filter(explode(PHP_EOL, $order['info']));
                                            @endphp
                                            
                                            @if(count($carmis) > 1)
                                                <!-- 多個卡密分別顯示 -->
                                                <div class="carmis-container">
                                                    @foreach($carmis as $index => $carmi)
                                                        <div class="carmi-item mb-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <small class="text-muted">{{ __('dujiaoka.carmi_number', ['number' => $index + 1]) }}</small>
                                                                <button class="btn btn-sm btn-outline-primary copy-single-carmi" data-clipboard-text="{{ trim($carmi) }}">
                                                                    <i class="fas fa-copy"></i> {{ __('dujiaoka.copy_text') }}
                                                                </button>
                                                            </div>
                                                            <div class="carmi-content">
                                                                <textarea class="form-control" rows="2" readonly>{{ trim($carmi) }}</textarea>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    
                                                    <!-- 複製全部按鈕 -->
                                                    <div class="text-center mt-3">
                                                        <button class="btn btn-primary" data-clipboard-text="{{ $order['info'] }}">
                                                            <i class="fas fa-copy"></i> {{ __('dujiaoka.copy_all_carmis') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <!-- 單個卡密顯示 -->
                                                <div class="card-info">
                                                    <textarea class="form-control" rows="5" readonly>{{ $order['info'] }}</textarea>
                                                </div>
                                                <button id="copy-card" class="btn btn-primary mt-2" data-clipboard-text="{{ $order['info'] }}">{{ __('dujiaoka.copy_text') }}</button>
                                            @endif
                                        @else
                                            <!-- 非自動發貨或未完成訂單的原始顯示 -->
                                            <div class="card-info">
                                                <textarea class="form-control" rows="5" readonly>{{ $order['info'] }}</textarea>
                                            </div>
                                            <button id="copy-card" class="btn btn-primary mt-2" data-clipboard-text="{{ $order['info'] }}">{{ __('dujiaoka.copy_text') }}</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @endforeach
                </div>
            </div>
        </div>

    </section>
    <!-- main end -->
@stop
@section('css')
<style>
.carmis-container {
    max-height: 400px;
    overflow-y: auto;
}

.carmi-item {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
}

.carmi-content textarea {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    background-color: white;
    border: 1px solid #ced4da;
}

.copy-single-carmi {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.carmi-item:hover {
    background-color: #e9ecef;
}
</style>
@stop
@section('js')
<script src="/assets/unicorn/js/clipboard.min.js"></script>
<script>
    // 複製單個卡密
    var singleClipboard = new ClipboardJS(".copy-single-carmi");
    singleClipboard.on('success', function (e) {
        alert("{{ __('dujiaoka.prompt.copy_text_success') }}");
        e.clearSelection();
    });
    singleClipboard.on('error', function (e) {
        alert("{{ __('dujiaoka.prompt.copy_text_failed') }}");
    });

    // 複製全部卡密
    var allClipboard = new ClipboardJS("#copy-card, .btn-primary[data-clipboard-text]");
    allClipboard.on('success', function (e) {
        alert("{{ __('dujiaoka.prompt.copy_text_success') }}");
        e.clearSelection();
    });
    allClipboard.on('error', function (e) {
        alert("{{ __('dujiaoka.prompt.copy_text_failed') }}");
    });
</script>
@stop
