@extends('hyper.layouts.default')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            {{-- 订单详情 --}}
            <h4 class="page-title">{{ __('hyper.orderinfo_title') }}</h4>
        </div>
    </div>
</div>
<div class="orderinfo-grid">
@foreach($orders as $order)
    <div class="row">
        <div class="col-md-12">
            <h3>
                <span class="badge badge-outline-primary">
                    订单号：{{ $order['order_sn'] }}
                </span>
            </h3>
        </div>
    </div>
    <div class="card card-body">
        <div class="orderinfo-card-grid">
            <div class="orderinfo-info">
                {{-- 订单名称 --}}
                <div class="mb-1"><label>{{ __('hyper.orderinfo_order_title') }}：</label><span>{{ $order['title'] }}</span></div>
                {{-- 下单数量 --}}
                <div class="mb-1"><label>{{ __('hyper.orderinfo_number_of_orders') }}：</label><span>{{ $order['buy_amount'] }}</span></div>
                {{-- 下单时间 --}}
                <div class="mb-1"><label>{{ __('hyper.orderinfo_order_time') }}：</label><span>{{ $order['created_at'] }}</span></div>
                {{-- 付款邮箱 --}}
                <div class="mb-1"><label>{{ __('hyper.orderinfo_email') }}：</label><span>{{ $order['email'] }}</span></div>
                <div class="mb-1">
                    {{-- 订单类型 --}}
                    <label>{{ __('hyper.orderinfo_order_class') }}：</label>
                    <span>
                        @if($order['type'] == \App\Models\Order::AUTOMATIC_DELIVERY)
                            {{-- 自动发货 --}}
                            {{ __('hyper.orderinfo_automatic_delivery') }}
                        @else
                            {{-- 人工发货 --}}
                            {{ __('hyper.orderinfo_charge') }}
                        @endif
                    </span>
                </div>
                <div class="mb-1">
                    {{-- 订单总价 --}}
                    <label>{{ __('hyper.orderinfo_total_order_price') }}：</label>
                    <span>{{ $order['actual_price'] }}</span>
                </div>
                <div class="mb-1">
                    {{-- 订单状态 --}}
                    <label>{{ __('hyper.orderinfo_order_status') }}：</label>
                    <span>
                        @switch($order['status'])
                            @case(\App\Models\Order::STATUS_EXPIRED)
                                {{-- 已过期 --}}
                                {{ __('hyper.orderinfo_status_expired') }}
                            @break
                            @case(\App\Models\Order::STATUS_WAIT_PAY)
                                {{-- 待支付 --}}
                                {{ __('hyper.orderinfo_status_wait_pay') }}
                            @break
                            @case(\App\Models\Order::STATUS_PENDING)
                                {{-- 待处理 --}}
                                {{ __('hyper.orderinfo_status_pending') }}
                            @break
                            @case(\App\Models\Order::STATUS_PROCESSING)
                                {{-- 已处理 --}}
                                {{ __('hyper.orderinfo_status_processed') }}
                            @break
                            @case(\App\Models\Order::STATUS_COMPLETED)
                                {{-- 已完成 --}}
                                {{ __('hyper.orderinfo_status_completed') }}
                            @break
                            @case(\App\Models\Order::STATUS_FAILURE)
                                {{-- 已失败 --}}
                                {{ __('hyper.orderinfo_status_failed') }}
                            @break
                            @case(\App\Models\Order::STATUS_FAILURE)
                                {{-- 状态异常 --}}
                                {{ __('hyper.orderinfo_status_abnormal') }}
                            @break
                        @endswitch
                    </span>
                </div>
                <div class="mb-1">
                    {{-- 支付方式 --}}
                    <label>{{ __('hyper.orderinfo_payment_method') }}：</label>
                    <span>{{ $order['pay']['pay_name'] ?? ''  }}</span>
                </div>
            </div>
            <div class="orderinfo-kami">
                <h5 class="card-title">
                    {{ __('hyper.orderinfo_carmi') }}
                </h5>
                
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
                                <button class="btn btn-primary kami-btn" data-clipboard-text="{{ $order['info'] }}">
                                    <i class="fas fa-copy"></i> {{ __('dujiaoka.copy_all_carmis') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- 單個卡密顯示 -->
                        <textarea class="form-control textarea-kami" rows="5" readonly>{{$order['info']}}</textarea>
                        <button class="btn btn-outline-primary kami-btn" data-clipboard-text="{{$order['info']}}">
                            {{ __('hyper.orderinfo_copy_carmi') }}
                        </button>
                    @endif
                @else
                    <!-- 非自動發貨或未完成訂單的原始顯示 -->
                    <textarea class="form-control textarea-kami" rows="5" readonly>{{$order['info']}}</textarea>
                    <button class="btn btn-outline-primary kami-btn" data-clipboard-text="{{$order['info']}}">
                        {{ __('hyper.orderinfo_copy_carmi') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
@endforeach
</div>
@if(!count($orders))
<div class="row justify-content-center">
    <div class="col-lg-4">
		<div class="text-center">
			<h1 class="text-error mt-4">error</h1>
            <h4 class="text-uppercase text-danger mt-3">{{ __('hyper.orderinfo_order_information') }}</h4>
            <a class="btn btn-info mt-3" href="javascript:history.back(-1);"><i class="mdi mdi-reply"></i> {{ __('hyper.error_back_btn') }}</a>
        </div> <!-- end /.text-center-->
    </div> <!-- end col-->
</div>
@endif
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
<script src="/assets/hyper/js/clipboard.min.js"></script>
<script>
    // 複製單個卡密
    var singleClipboard = new ClipboardJS(".copy-single-carmi");
    singleClipboard.on('success', function (e) {
        $.NotificationApp.send("{{ __('hyper.orderinfo_tips') }}","{{ __('hyper.orderinfo_copy_success') }}","top-center","rgba(0,0,0,0.2)","info");
        e.clearSelection();
    });
    singleClipboard.on('error', function (e) {
        $.NotificationApp.send("{{ __('hyper.orderinfo_tips') }}","{{ __('hyper.orderinfo_copy_error') }}","top-center","rgba(0,0,0,0.2)","error");
    });

    // 複製全部卡密
    var allClipboard = new ClipboardJS('.kami-btn');
    allClipboard.on('success', function(e){
        $.NotificationApp.send("{{ __('hyper.orderinfo_tips') }}","{{ __('hyper.orderinfo_copy_success') }}","top-center","rgba(0,0,0,0.2)","info");
        e.clearSelection();
    });
    allClipboard.on('error', function(e){
        $.NotificationApp.send("{{ __('hyper.orderinfo_tips') }}","{{ __('hyper.orderinfo_copy_error') }}","top-center","rgba(0,0,0,0.2)","error");
    });
</script>
@stop