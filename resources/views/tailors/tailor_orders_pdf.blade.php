<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale') === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('messages.tailor_orders_list', [], session('locale')) }} - {{ $tailor->tailor_name }}</title>
    <style>
        @page { margin: 14mm; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #374151;
        }
        .meta {
            font-size: 11px;
            color: #6b7280;
            margin-top: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
            text-align: right;
        }
        th {
            background-color: #111827;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        .text-center { text-align: center; }
        .w-qty { width: 70px; }
        .w-phone { width: 120px; }
        .w-country { width: 80px; }
        .w-sent { width: 120px; }
        .muted { color: #6b7280; }
        .nowrap { white-space: nowrap; }
        .address {
            max-width: 260px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ trans('messages.tailor_orders_list', [], session('locale')) }}</h1>
        <h2>{{ trans('messages.tailor', [], session('locale')) }}: {{ $tailor->tailor_name }}</h2>
        <div class="meta">
            {{ trans('messages.date', [], session('locale')) }}: {{ date('Y-m-d H:i') }}
            @if(request('start_date') || request('end_date'))
                <span class="muted"> | </span>
                <span class="muted">
                    @if(session('locale') === 'ar')
                        الفترة:
                    @else
                        Period:
                    @endif
                    {{ request('start_date') ?: '—' }} → {{ request('end_date') ?: '—' }}
                </span>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="nowrap">{{ trans('messages.special_order_number', [], session('locale')) }}</th>
                <th class="nowrap">{{ trans('messages.list_number', [], session('locale')) ?: 'List Number' }}</th>
                <th class="nowrap">{{ trans('messages.sending_summary_number', [], session('locale')) ?: 'Summary No.' }}</th>
                <th class="nowrap">{{ trans('messages.tailor', [], session('locale')) }}</th>
                <th class="w-qty text-center">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="nowrap">
                    {{ trans('messages.abaya', [], session('locale')) }} / {{ trans('messages.abaya_code', [], session('locale')) }}
                </th>
                <th class="w-country nowrap">
                    {{ trans('messages.sizes', [], session('locale')) ?: 'Sizes' }}
                </th>
                <th>
                    {{ trans('messages.customer', [], session('locale')) }} / {{ trans('messages.phone', [], session('locale')) }}
                </th>
                <th>{{ trans('messages.address', [], session('locale')) }}</th>
                <th class="w-country nowrap">{{ trans('messages.country', [], session('locale')) }}</th>
                <th class="nowrap">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="w-sent nowrap">
                    @if(session('locale') == 'ar') تاريخ الإرسال @else Sent Date @endif
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="nowrap">{{ $order['special_order_no'] ?? '-' }}</td>
                    <td class="nowrap">{{ $order['list_number'] ?? '-' }}</td>
                    <td class="nowrap">{{ $order['sending_summary_number'] ?? '-' }}</td>
                    <td class="nowrap">{{ $order['tailor_name'] ?? '-' }}</td>
                    <td class="text-center">{{ $order['quantity'] ?? '-' }}</td>
                    <td class="nowrap">
                        <div>{{ $order['design_name'] ?? '-' }}</div>
                        <div class="muted">{{ $order['abaya_code'] ?? '-' }}</div>
                    </td>
                    <td class="nowrap">
                        <div>{{ trans('messages.abaya_length', [], session('locale')) }}: {{ $order['abaya'] ?? '-' }}</div>
                        <div>{{ trans('messages.bust', [], session('locale')) }}: {{ $order['bust'] ?? '-' }}</div>
                        <div>{{ trans('messages.sleeves_length', [], session('locale')) }}: {{ $order['sleeves'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div>{{ $order['customer_name'] ?? '-' }}</div>
                        <div class="muted">{{ $order['customer_phone'] ?? '-' }}</div>
                    </td>
                    <td class="address">{{ $order['customer_address'] ?? '-' }}</td>
                    <td class="nowrap">{{ $order['customer_country'] ?? 'Oman' }}</td>
                    <td class="nowrap">{{ $order['status'] ?? '-' }}</td>
                    <td class="nowrap">{{ $order['sent_at'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center muted" style="padding: 18px;">
                        {{ trans('messages.no_orders_found', [], session('locale')) }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ trans('messages.generated_on', [], session('locale')) }}: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

