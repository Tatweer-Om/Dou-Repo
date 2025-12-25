<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale') == 'ar' ? 'rtl' : 'ltr' }}">
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
      <div><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> {{ $orderNumber }}</div>
      <div><strong>{{ trans('messages.source', [], session('locale')) }}:</strong> 
        @if($specialOrder->source == 'whatsapp')
          {{ trans('messages.whatsapp', [], session('locale')) }}
        @elseif($specialOrder->source == 'walkin')
          {{ trans('messages.walk_in', [], session('locale')) ?? 'زيارة' }}
        @else
          {{ $specialOrder->source }}
        @endif
      </div>
    </div>
  </div>

  <div class="invoice-welcome">
  <p class="welcome-title">{{ trans('messages.welcome_customer', [], session('locale')) }}</p>
  <p class="welcome-text">
    {!! trans('messages.thank_you_message', [], session('locale')) !!}
  </p>
</div>


  <!-- Customer -->
  <div class="customer">
    <div><strong>{{ trans('messages.customer_name', [], session('locale')) }}:</strong> {{ $specialOrder->customer->name ?? 'N/A' }}</div>
    <div><strong>{{ trans('messages.phone_number', [], session('locale')) ?? 'الهاتف' }}:</strong> {{ $specialOrder->customer->phone ?? '—' }}</div>
    <div class="full">
      <strong>{{ trans('messages.address', [], session('locale')) }}:</strong> 
      @php
        $addressParts = [];
        if ($specialOrder->customer->area) {
          $locale = session('locale', 'en');
          $addressParts[] = $locale === 'ar' 
            ? ($specialOrder->customer->area->area_name_ar ?? $specialOrder->customer->area->area_name_en ?? '')
            : ($specialOrder->customer->area->area_name_en ?? $specialOrder->customer->area->area_name_ar ?? '');
        }
        if ($specialOrder->customer->city) {
          $locale = session('locale', 'en');
          $addressParts[] = $locale === 'ar'
            ? ($specialOrder->customer->city->city_name_ar ?? $specialOrder->customer->city->city_name_en ?? '')
            : ($specialOrder->customer->city->city_name_en ?? $specialOrder->customer->city->city_name_ar ?? '');
        }
        $location = implode(' – ', array_filter($addressParts));
        $fullAddress = trim(($location ? $location . ' – ' : '') . ($specialOrder->customer->address ?? ''));
      @endphp
      {{ $fullAddress ?: '—' }}
    </div>
    <div><strong>{{ trans('messages.shipping', [], session('locale')) }}:</strong> {{ trans('messages.paid_to_delivery_agent', [], session('locale')) }}</div>
    <div><strong>{{ trans('messages.delivery_fee', [], session('locale')) }}:</strong> {{ number_format($specialOrder->shipping_fee, 3) }} {{ trans('messages.currency', [], session('locale')) }}</div>
    @if($specialOrder->send_as_gift && $specialOrder->gift_text)
    <div class="full"><strong>{{ trans('messages.gift_card_message', [], session('locale')) ?? 'رسالة الهدية' }}:</strong> {{ $specialOrder->gift_text }}</div>
    @endif
  </div>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th>{{ trans('messages.abaya', [], session('locale')) ?? 'العباية' }}</th>
        <th>{{ trans('messages.details', [], session('locale')) }}</th>
        <th>{{ trans('messages.price', [], session('locale')) ?? 'سعر الوحدة' }}</th>
        <th>{{ trans('messages.quantity', [], session('locale')) ?? 'الكمية' }}</th>
        <th>{{ trans('messages.total', [], session('locale')) ?? 'الإجمالي' }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($specialOrder->items as $item)
       <tr>
        <td>
          @php
            $imageUrl = asset('images/placeholder.png');
            if($item->stock && $item->stock->images && $item->stock->images->first()) {
              $imagePath = $item->stock->images->first()->image_path;
              if(strpos($imagePath, 'http') === 0) {
                $imageUrl = $imagePath;
              } else {
                $imageUrl = asset($imagePath);
              }
            }
          @endphp
          <img src="{{ $imageUrl }}" alt="{{ $item->design_name ?? $item->abaya_code }}">
        </td>
        <td>
          {{ $item->design_name ?? $item->abaya_code ?? 'N/A' }}<br>
          @if($item->abaya_length || $item->bust || $item->sleeves_length)
          <span class="measure">
            @if($item->abaya_length){{ trans('messages.abaya_length', [], session('locale')) }}: {{ $item->abaya_length }} {{ trans('messages.inches', [], session('locale')) ?? 'إنش' }}@endif
            @if($item->bust){{ $item->abaya_length ? ' – ' : '' }}{{ trans('messages.bust_one_side', [], session('locale')) }}: {{ $item->bust }} {{ trans('messages.inches', [], session('locale')) ?? 'إنش' }}@endif
            @if($item->sleeves_length){{ ($item->abaya_length || $item->bust) ? ' – ' : '' }}{{ trans('messages.sleeves_length', [], session('locale')) }}: {{ $item->sleeves_length }} {{ trans('messages.inches', [], session('locale')) ?? 'إنش' }}@endif
            @if($item->buttons !== null){{ ($item->abaya_length || $item->bust || $item->sleeves_length) ? ' – ' : '' }}{{ trans('messages.buttons', [], session('locale')) }}: {{ $item->buttons ? trans('messages.yes', [], session('locale')) : trans('messages.no', [], session('locale')) }}@endif
          </span>
          @endif
          @if($item->notes)
          <br><small style="color: #666;">{{ trans('messages.notes', [], session('locale')) }}: {{ $item->notes }}</small>
          @endif
        </td>
        <td>{{ number_format($item->price, 3) }} {{ trans('messages.currency', [], session('locale')) }}</td>
        <td>{{ $item->quantity }}</td>
        <td><strong>{{ number_format($item->price * $item->quantity, 3) }} {{ trans('messages.currency', [], session('locale')) }}</strong></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Summary -->
  <div class="summary">
    <div id="qrcode"></div>

    <div class="summary-box">
      <div><span>{{ trans('messages.total', [], session('locale')) }}</span><span>{{ number_format($specialOrder->total_amount, 3) }} {{ trans('messages.currency', [], session('locale')) }}</span></div>
      <div><span>{{ trans('messages.paid', [], session('locale')) }}</span><span>{{ number_format($specialOrder->paid_amount, 3) }} {{ trans('messages.currency', [], session('locale')) }}</span></div>
      <div class="total"><span>{{ trans('messages.remaining', [], session('locale')) }}</span><span>{{ number_format($specialOrder->total_amount - $specialOrder->paid_amount, 3) }} {{ trans('messages.currency', [], session('locale')) }}</span></div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <strong>{{ trans('messages.terms_and_conditions', [], session('locale')) ?? 'الشروط والأحكام' }}:</strong><br>
    • {{ trans('messages.no_return_after_tailoring', [], session('locale')) }}<br>
    • {{ trans('messages.execution_period', [], session('locale')) }}<br>
    • {{ trans('messages.contact_if_delayed', [], session('locale')) }}
  </div>

</div>

<script>
new QRCode(document.getElementById("qrcode"), {
  text: "{{ $orderNumber }}",
  width:110,
  height:110
});
</script>

</body>
</html>
