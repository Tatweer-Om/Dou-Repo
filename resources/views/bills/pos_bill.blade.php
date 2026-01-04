<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale', 'ar') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<title>{{ trans('messages.order_invoice', [], session('locale')) }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- QR Code -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

<style>
:root{
  --primary:#b34b8a;
  --soft:#fdf2f8;
  --border:#eee;
  --text:#1f2937;
}
*{box-sizing:border-box}
body{
  margin:0;
  background:#f6f6fb;
  font-family:"IBM Plex Sans Arabic",sans-serif;
  color:var(--text);
}
.invoice{
  max-width:900px;
  margin:30px auto;
  background:#fff;
  border-radius:20px;
  padding:28px;
  box-shadow:0 10px 30px rgba(0,0,0,.06);
}

.invoice-welcome {
  text-align: center;
  margin: 20px 0 28px;
  color: #374151; /* neutral dark */
}

.welcome-icon {
  font-size: 26px;
  line-height: 1;
  margin-bottom: 6px;
}

.welcome-title {
  font-size: 18px;
  font-weight: 600;
  letter-spacing: 0.3px;
  margin-bottom: 6px;
}

.welcome-text {
  font-size: 13.5px;
  line-height: 1.8;
  color: #6b7280; /* soft gray */
}




/* Header */
.invoice-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:1px solid var(--border);
  padding-bottom:16px;
}
.brand{
  display:flex;
  gap:12px;
  align-items:center;
}
.brand img{
  height:55px;
}
.brand h1{
  margin:0;
  font-size:20px;
}
.order-meta{
  text-align:left;
  font-size:14px;
}


/* Customer info */
.customer{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px 20px;
  font-size:14px;
  margin-bottom:20px;
}
.customer strong{color:#000}
.customer .full{grid-column:1/-1}

/* Table */
table{
  width:100%;
  border-collapse:collapse;
  font-size:14px;
}
thead{
  background:#fafafa;
}
th,td{
  padding:10px;
  border-bottom:1px solid var(--border);
  text-align:right;
}
th{
  font-weight:600;
  color:#555;
}
td img{
  width:55px;
  height:55px;
  border-radius:8px;
  object-fit:cover;
  border:1px solid #ddd;
}
.measure{
  color:#555;
  font-size:13px;
}

/* Summary */
.summary{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  margin-top:20px;
}
.summary-box{
  background:#fafafa;
  padding:16px 20px;
  border-radius:14px;
  width:320px;
}
.summary-box div{
  display:flex;
  justify-content:space-between;
  margin-bottom:8px;
}
.summary-box .total{
  font-size:18px;
  font-weight:700;
  color:var(--primary);
}

/* QR */
#qrcode{
  width:110px;
}

/* Footer */
.footer{
  border-top:1px dashed var(--border);
  margin-top:24px;
  padding-top:16px;
  font-size:13px;
  color:#555;
}

/* Print */
@media print{
  body{background:#fff}
  .invoice{box-shadow:none;margin:0}
}
</style>
</head>

<body>

<div class="invoice">

  <!-- Header -->
  <div class="invoice-header">
    <div class="brand">
      <img src="https://www.duo-fashion.com/en/assets/images/logo/9.png" alt="Logo">
      <!-- <h1>Duo Fashion </h1> -->
    </div>
    <div class="order-meta">
      @if(isset($order) && $order)
        <div><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> {{ $orderNo ?? 'A-' . str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT) }}</div>
        <div><strong>{{ trans('messages.source', [], session('locale')) }}:</strong> 
          @if($order->channel_id && $order->channel)
            @if(session('locale') === 'ar')
              {{ $order->channel->channel_name_ar ?? $order->channel->channel_name_en ?? trans('messages.direct', [], session('locale')) }}
            @else
              {{ $order->channel->channel_name_en ?? $order->channel->channel_name_ar ?? trans('messages.direct', [], session('locale')) }}
            @endif
          @else
            {{ trans('messages.direct', [], session('locale')) }}
          @endif
        </div>
      @else
        <div><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> -</div>
        <div><strong>{{ trans('messages.source', [], session('locale')) }}:</strong> -</div>
      @endif
    </div>
  </div>

  <div class="invoice-welcome">
  <p class="welcome-title">{{ trans('messages.welcome_customer', [], session('locale')) }}</p>
  <p class="welcome-text">
    {{ trans('messages.thank_you_message', [], session('locale')) }}
  </p>
</div>


  <!-- Customer -->
  <div class="customer">
    @if(isset($customer) && $customer)
      <div><strong>{{ trans('messages.name', [], session('locale')) }}:</strong> {{ $customer->name ?? '-' }}</div>
      <div><strong>{{ trans('messages.phone', [], session('locale')) }}:</strong> {{ $customer->phone ?? '-' }}</div>
      <div class="full"><strong>{{ trans('messages.address', [], session('locale')) }}:</strong> {{ $customer->notes ?? ($order->notes ?? '-') }}</div>
      @if(isset($order) && $order && $order->order_type === 'delivery')
        <div><strong>{{ trans('messages.delivery', [], session('locale')) }}:</strong> 
          @if($order->delivery_paid ?? false)
            {{ trans('messages.paid_to_delivery_agent', [], session('locale')) }}
          @else
            {{ trans('messages.paid', [], session('locale')) }}
          @endif
        </div>
        <div><strong>{{ trans('messages.delivery_fee', [], session('locale')) }}:</strong> 
          {{ number_format($order->delivery_charges ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}
        </div>
      @endif
    @else
      <div><strong>{{ trans('messages.name', [], session('locale')) }}:</strong> -</div>
      <div><strong>{{ trans('messages.phone', [], session('locale')) }}:</strong> -</div>
      <div class="full"><strong>{{ trans('messages.address', [], session('locale')) }}:</strong> -</div>
    @endif
  </div>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th>{{ trans('messages.abaya', [], session('locale')) }}</th>
        <th>{{ trans('messages.details', [], session('locale')) }}</th>
        <th>{{ trans('messages.unit_price', [], session('locale')) }}</th>
        <th>{{ trans('messages.quantity', [], session('locale')) }}</th>
        <th>{{ trans('messages.total', [], session('locale')) }}</th>
      </tr>
    </thead>
    <tbody>
      @if(isset($orderDetails) && count($orderDetails) > 0)
        @foreach($orderDetails as $detail)
          <tr>
            <td>
              @if($detail['image'])
                <img src="{{ $detail['image'] }}" alt="{{ $detail['design_name'] }}">
              @else
                <div style="width:55px;height:55px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#999;">
                  {{ trans('messages.no_image', [], session('locale')) ?: 'No Image' }}
                </div>
              @endif
            </td>
            <td>
              {{ $detail['design_name'] ?? $detail['abaya_code'] }}<br>
              @if($detail['color_name'] || $detail['size_name'])
                <span class="measure">
                  @if($detail['color_name'])
                    {{ trans('messages.color', [], session('locale')) }}: {{ $detail['color_name'] }}
                  @endif
                  @if($detail['color_name'] && $detail['size_name']) – @endif
                  @if($detail['size_name'])
                    {{ trans('messages.size', [], session('locale')) }}: {{ $detail['size_name'] }}
                  @endif
                </span>
              @endif
            </td>
            <td>{{ number_format($detail['unit_price'], 3) }} {{ trans('messages.currency', [], session('locale')) }}</td>
            <td>{{ $detail['quantity'] }}</td>
            <td><strong>{{ number_format($detail['total'], 3) }} {{ trans('messages.currency', [], session('locale')) }}</strong></td>
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="5" style="text-align:center;padding:20px;color:#999;">
            {{ trans('messages.no_data', [], session('locale')) ?: 'No order data available' }}
          </td>
        </tr>
      @endif
    </tbody>
  </table>

  <!-- Summary -->
  <div class="summary">
    <div id="qrcode"></div>

    <div class="summary-box">
      @if(isset($order) && $order)
        @php
          $subtotal = ($order->total_amount ?? 0) + ($order->total_discount ?? 0);
          $total = $order->total_amount ?? 0;
          $paid = $order->paid_amount ?? 0;
          $remaining = $total - $paid;
        @endphp
        <div><span>{{ trans('messages.total', [], session('locale')) }}</span><span>{{ number_format($total, 3) }} {{ trans('messages.currency', [], session('locale')) }}</span></div>
        <div><span>{{ trans('messages.paid', [], session('locale')) }}</span><span>{{ number_format($paid, 3) }} {{ trans('messages.currency', [], session('locale')) }}</span></div>
        <div class="total"><span>{{ trans('messages.remaining', [], session('locale')) }}</span><span>{{ number_format($remaining, 3) }} {{ trans('messages.currency', [], session('locale')) }}</span></div>
      @else
        <div><span>{{ trans('messages.total', [], session('locale')) }}</span><span>0 {{ trans('messages.currency', [], session('locale')) }}</span></div>
        <div><span>{{ trans('messages.paid', [], session('locale')) }}</span><span>0 {{ trans('messages.currency', [], session('locale')) }}</span></div>
        <div class="total"><span>{{ trans('messages.remaining', [], session('locale')) }}</span><span>0 {{ trans('messages.currency', [], session('locale')) }}</span></div>
      @endif
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <strong>{{ trans('messages.terms_and_conditions', [], session('locale')) }}:</strong><br>
    • {{ trans('messages.no_return_after_tailoring', [], session('locale')) }}<br>
    • {{ trans('messages.execution_period', [], session('locale')) }}<br>
    • {{ trans('messages.contact_if_delayed', [], session('locale')) }}
  </div>

</div>

<script>
@if(isset($order) && $order)
new QRCode(document.getElementById("qrcode"), {
  text: "ORDER-{{ $orderNo ?? 'A-' . str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT) }}",
  width:110,
  height:110
});
@else
new QRCode(document.getElementById("qrcode"), {
  text: "ORDER-N/A",
  width:110,
  height:110
});
@endif
</script>

</body>
</html>
