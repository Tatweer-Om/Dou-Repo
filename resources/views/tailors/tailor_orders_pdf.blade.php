<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale') === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('messages.tailor_orders_list', [], session('locale')) }} - {{ $tailor->tailor_name }}</title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #8B5CF6;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .buttons-yes {
            text-align: center;
            color: green;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ trans('messages.tailor_orders_list', [], session('locale')) }}</h1>
        <h2>{{ trans('messages.tailor', [], session('locale')) }}: {{ $tailor->tailor_name }}</h2>
        <p>{{ trans('messages.date', [], session('locale')) }}: {{ date('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ trans('messages.order_number', [], session('locale')) }}</th>
                <th>{{ trans('messages.dress_name', [], session('locale')) }}</th>
                <th>{{ trans('messages.dress_code', [], session('locale')) }}</th>
                <th>{{ trans('messages.size', [], session('locale')) }}</th>
                <th>{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th>{{ trans('messages.buttons', [], session('locale')) }}</th>
                <th>{{ trans('messages.gift', [], session('locale')) }}</th>
                <th>{{ trans('messages.notes', [], session('locale')) }}</th>
                <th>{{ trans('messages.customer_name', [], session('locale')) }}</th>
                <th>{{ trans('messages.phone', [], session('locale')) }}</th>
                <th>{{ trans('messages.address', [], session('locale')) }}</th>
                <th>{{ trans('messages.country', [], session('locale')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order['order_no'] }}</td>
                <td>{{ $order['dress_name'] }}</td>
                <td>{{ $order['dress_code'] }}</td>
                <td>{{ $order['size'] }}</td>
                <td>{{ $order['quantity'] }}</td>
                <td class="buttons-yes">{{ $order['buttons'] }}</td>
                <td>{{ $order['gift'] }}</td>
                <td>{{ $order['notes'] }}</td>
                <td>{{ $order['customer_name'] }}</td>
                <td>{{ $order['customer_phone'] }}</td>
                <td>{{ $order['customer_address'] }}</td>
                <td>{{ $order['customer_country'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ trans('messages.generated_on', [], session('locale')) }}: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

