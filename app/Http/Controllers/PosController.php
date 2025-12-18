<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\PosOrders;
use App\Models\PosOrdersDetail;
use App\Models\PosPayment;
use App\Models\PosPaymentExpence;
use App\Models\Area;
use App\Models\City;
use App\Models\ColorSize;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class PosController extends Controller
{
    public function index(){
        // Fetch all categories
        $categories = Category::orderBy('id', 'ASC')->get();
        
        // Fetch all stocks (abayas) with their images and category
        $stocks = Stock::with(['images', 'category'])
            ->whereNotNull('category_id')
            ->orderBy('id', 'DESC')
            ->get();

        // Areas for delivery selects
        $areas = Area::orderBy('area_name_ar', 'ASC')->get(['id','area_name_ar','area_name_en']);

        // Cities (with delivery charges) for delivery selects
        $cities = City::orderBy('city_name_ar', 'ASC')
            ->get(['id','city_name_ar','city_name_en','delivery_charges','area_id']);
        
        return view('pos.pos_page', compact('categories', 'stocks', 'areas', 'cities'));
    }

    public function getStockDetails($id)
    {
        $stock = Stock::with([
            'colorSizes.size',
            'colorSizes.color',
            'images',
            'category'
        ])->findOrFail($id);

        $colorSizes = [];
        foreach ($stock->colorSizes as $item) {
            $sizeName = session('locale') === 'ar' 
                ? ($item->size?->size_name_ar ?? '-') 
                : ($item->size?->size_name_en ?? '-');
            
            $colorName = session('locale') === 'ar' 
                ? ($item->color?->color_name_ar ?? '-') 
                : ($item->color?->color_name_en ?? '-');
            
            $colorSizes[] = [
                'size_id' => $item->size_id,
                'size_name' => $sizeName,
                'color_id' => $item->color_id,
                'color_name' => $colorName,
                'color_code' => $item->color?->color_code ?? '#000000',
                'quantity' => $item->qty ?? 0,
            ];
        }

        return response()->json([
            'id' => $stock->id,
            'name' => session('locale') === 'ar' && $stock->design_name ? $stock->design_name : ($stock->design_name ?: $stock->abaya_code),
            'abaya_code' => $stock->abaya_code,
            'price' => $stock->sales_price ?? 0,
            'image' => $stock->images->first() ? asset($stock->images->first()->image_path) : null,
            'colorSizes' => $colorSizes,
        ]);
    }

    /**
     * Search customers for POS autocomplete by phone or name.
     */
    public function searchCustomers(Request $request)
    {
        $search = trim($request->query('search', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::with(['city', 'area'])
            ->where(function ($q) use ($search) {
                $q->where('phone', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'city_id', 'area_id']);

        return response()->json($customers);
    }

    /**
     * Cities by area (for delivery wilayah select)
     */
    public function citiesByArea(Request $request)
    {
        $areaId = $request->query('area_id');
        if (!$areaId) {
            return response()->json([]);
        }

        $cities = City::where('area_id', $areaId)
            ->orderBy('city_name_ar', 'ASC')
            ->get(['id','city_name_ar','city_name_en','delivery_charges']);

        return response()->json($cities);
    }

 
    public function store(Request $request)
{
    // ✅ Manual validator to avoid HTML redirects
    $validator = Validator::make($request->all(), [
        'items' => 'required|array|min:1',
        'items.*.id' => 'required|integer',
        'items.*.qty' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric|min:0',
        'payments' => 'required|array|min:1',
        'payments.*.account_id' => 'required|integer',
        'payments.*.amount' => 'required|numeric|min:0.001',
        'totals.subtotal' => 'required|numeric|min:0',
        'totals.total' => 'required|numeric|min:0',
        'customer.name' => 'nullable|string|max:255',
        'customer.phone' => 'nullable|string|max:50',
        'customer.address' => 'nullable|string|max:1000',
        'customer.area' => 'nullable|string|max:255',
        'customer.wilayah' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        DB::beginTransaction();

        $user = Auth::user();
        $userId = $user->id ?? null;
        $userName = $user->user_name ?? $user->name ?? 'system';

        $items = $request->input('items', []);
        $payments = $request->input('payments', []);
        $totals = $request->input('totals', []);
        $customerInput = $request->input('customer', []);

        // Human friendly order number: sequential, padded to 6 digits
        $orderNoInt = (PosOrders::max('order_no') ?? 0) + 1;
        $orderNoFormatted = str_pad($orderNoInt, 6, '0', STR_PAD_LEFT);

        /* ================= CUSTOMER ================= */

        $customerId = null;

        if (!empty($customerInput['phone']) || !empty($customerInput['name'])) {
            // Get area_id and city_id from customer input (area = area_id, wilayah = city_id)
            // Convert to integers if they exist, otherwise null
            $areaId = !empty($customerInput['area']) ? (int)$customerInput['area'] : null;
            $cityId = !empty($customerInput['wilayah']) ? (int)$customerInput['wilayah'] : null;
            $addressNotes = $customerInput['address'] ?? null;

            if (!empty($customerInput['phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $customerInput['phone']],
                    [
                        'name' => $customerInput['name'] ?? '',
                        'city_id' => $cityId,
                        'area_id' => $areaId,
                        'notes' => $addressNotes,
                    ]
                );
            } else {
                $customer = Customer::create([
                    'name' => $customerInput['name'] ?? '',
                    'city_id' => $cityId,
                    'area_id' => $areaId,
                    'notes' => $addressNotes,
                ]);
            }

            // Update existing customer safely
            if (!$customer->wasRecentlyCreated) {
                $customer->update([
                    'name' => $customerInput['name'] ?? $customer->name,
                    'city_id' => $cityId ?? $customer->city_id,
                    'area_id' => $areaId ?? $customer->area_id,
                    'notes' => $addressNotes ?? $customer->notes,
                ]);
            }

            $customerId = $customer->id;
        }

        /* ================= ORDER ================= */

        $order = PosOrders::create([
            'customer_id' => $customerId,
            'order_type' => $request->input('order_type', 'direct'),
            'item_count' => count($items),
            'paid_amount' => collect($payments)->sum('amount'),
            'total_amount' => $totals['total'] ?? 0,
            'discount_type' => data_get($request, 'discount.type'),
            'total_discount' => $totals['discount'] ?? 0,
            'profit' => null,
            'return_status' => 0,
            'restore_status' => 0,
            'order_no' => $orderNoInt,
            'notes' => $request->input('notes') ?? ($customerInput['address'] ?? null),
            'added_by' => $userName,
            'user_id' => $userId,
        ]);

        /* ================= ORDER ITEMS ================= */

        $subtotalAll = $totals['subtotal'] ?? ($totals['total'] ?? 0);
        $totalDiscount = $totals['discount'] ?? 0;

        $totalProfit = 0;

        foreach ($items as $item) {
            $qty = $item['qty'];
            $linePrice = $item['price'];
            $lineSubtotal = $linePrice * $qty;

            // Pro-rate discount based on subtotal share
            $discountShare = $subtotalAll > 0 ? ($totalDiscount * ($lineSubtotal / $subtotalAll)) : 0;
            $effectiveLine = $lineSubtotal - $discountShare;
            $unitEffectivePrice = $qty > 0 ? $effectiveLine / $qty : 0;

            // Fetch stock cost and tailor charges
            $stock = Stock::find($item['id']);
            $stockQty = max(1, (float)($stock->quantity ?? 1));
            $unitTailor = ($stock->tailor_charges ?? 0) / $stockQty;
            $unitCost = ($stock->cost_price ?? 0) + $unitTailor;

            $itemProfit = ($unitEffectivePrice - $unitCost) * $qty;
            $totalProfit += $itemProfit;

            // Handle color_id and size_id - convert to int or null
            $colorId = null;
            if (!empty($item['color_id']) && is_numeric($item['color_id'])) {
                $colorId = (int)$item['color_id'];
            }
            
            $sizeId = null;
            if (!empty($item['size_id']) && is_numeric($item['size_id'])) {
                $sizeId = (int)$item['size_id'];
            }

            PosOrdersDetail::create([
                'order_id' => $order->id,
                'order_no' => $orderNoFormatted,
                'item_id' => $item['id'],
                'item_barcode' => $stock['barcode'] ?? '',
                'item_quantity' => $qty,
                'color_id' => $colorId,
                'size_id' => $sizeId,
                'item_discount_price' => $discountShare, // store total discount for this line
                'item_price' => $linePrice,
                'item_total' => $effectiveLine,
                'item_tax' => $item['tax'] ?? 0,
                'item_profit' => $itemProfit,
                'added_by' => $userName,
                'user_id' => $userId,
                'branch_id' => $item['branch_id'] ?? null,
            ]);

            // Reduce stock quantity from ColorSize table
            if ($colorId && $sizeId) {
                $colorSize = ColorSize::where('stock_id', $item['id'])
                    ->where('color_id', $colorId)
                    ->where('size_id', $sizeId)
                    ->first();

                if ($colorSize) {
                    $currentQty = (int)($colorSize->qty ?? 0);
                    $newQty = max(0, $currentQty - $qty);
                    $colorSize->qty = $newQty;
                    $colorSize->save();

                    // Also update the stock total quantity
                    $stockTotalQty = ColorSize::where('stock_id', $item['id'])->sum('qty');
                    $stock->quantity = $stockTotalQty;
                    $stock->save();
                }
            }
        }

        // Update total profit on order
        $order->profit = $totalProfit;
        $order->save();

        /* ================= PAYMENTS ================= */

        $totalAmount = $totals['total'] ?? 0;
        $totalDiscount = $totals['discount'] ?? 0;

        foreach ($payments as $pay) {

            PosPayment::create([
                'order_id' => $order->id,
                'order_no' => $orderNoFormatted,
                'account_id' => $pay['account_id'],
                'paid_amount' => $pay['amount'],
                'total_amount' => $totalAmount,
                'discount' => $totalDiscount,
                'notes' => $pay['reference'] ?? null,
                'added_by' => $userName,
                'user_id' => $userId,
            ]);

            // Get account to check commission
            $account = Account::find($pay['account_id']);
            $accountTax = null;
            $accountTaxFee = null;

            // Calculate commission if account has commission > 0
            if ($account && $account->commission && (float)$account->commission > 0) {
                $commissionPercentage = (float)$account->commission;
                $paymentAmount = (float)$pay['amount'];
                
                // Calculate commission amount: (commission% / 100) * payment amount
                $commissionAmount = ($commissionPercentage / 100) * $paymentAmount;
                
                // Save commission percentage in account_tax
                $accountTax = $commissionPercentage;
                
                // Save calculated commission amount in account_tax_fee
                $accountTaxFee = $commissionAmount;
            }

            PosPaymentExpence::create([
                'order_id' => $order->id,
                'order_no' => $orderNoFormatted,
                'total_amount' => $pay['amount'],
                'accoun_id' => $pay['account_id'], // column name in migration
                'account_tax' => $accountTax,
                'account_tax_fee' => $accountTaxFee,
                'added_by' => $userName,
                'updated_by' => $userName,
                'user_id' => $userId,
            ]);

            // Update account opening balance
            if ($account) {
                $currentBalance = (float)($account->opening_balance ?? 0);
                $newBalance = $currentBalance + (float)$pay['amount'];
                $account->opening_balance = $newBalance;
                $account->save();
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_no' => $orderNoFormatted,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
        ], 500);
    }
}

    /**
     * Show POS orders list page
     */
    public function ordersList()
    {
        return view('pos.orders_list');
    }

    /**
     * Get all POS orders with details
     */
    public function getOrdersList(Request $request)
    {
        try {
            $orders = PosOrders::with([
                'customer',
                'details.stock.images',
                'details.color',
                'details.size',
                'payments.account'
            ])
            ->orderBy('id', 'DESC')
            ->paginate(20);

            $formattedOrders = $orders->map(function($order) {
                // Format order number
                $orderNo = str_pad($order->order_no ?? $order->id, 6, '0', STR_PAD_LEFT);
                
                // Customer name
                $customerName = $order->customer ? $order->customer->name : '-';
                
                // Format date and time
                $date = $order->created_at ? $order->created_at->format('Y-m-d') : '-';
                $time = $order->created_at ? $order->created_at->format('H:i:s') : '-';
                
                // Items count
                $itemsCount = $order->details->count();
                
                // Total price (before discount)
                $subtotal = (float)($order->total_amount ?? 0) + (float)($order->total_discount ?? 0);
                
                // Discount
                $discount = (float)($order->total_discount ?? 0);
                
                // Paid amount
                $paidAmount = (float)($order->paid_amount ?? 0);
                
                // Payment methods (get from payments)
                $paymentMethods = $order->payments->map(function($payment) {
                    return $payment->account ? $payment->account->account_name : 'Unknown';
                })->unique()->implode(', ');

                // Order type label
                $orderTypeLabel = $order->order_type === 'delivery' ? 
                    trans('messages.delivery', [], session('locale')) : 
                    trans('messages.direct', [], session('locale'));

                return [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'order_type' => $orderTypeLabel,
                    'date' => $date,
                    'time' => $time,
                    'items_count' => $itemsCount,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total_amount' => (float)($order->total_amount ?? 0),
                    'paid_amount' => $paidAmount,
                    'payment_methods' => $paymentMethods ?: '-',
                    'items' => $order->details->map(function($detail) {
                        $locale = session('locale', 'en');
                        $stock = $detail->stock;
                        $colorName = $detail->color ? 
                            ($locale === 'ar' ? ($detail->color->color_name_ar ?? $detail->color->color_name_en) : ($detail->color->color_name_en ?? $detail->color->color_name_ar)) : 
                            '-';
                        $sizeName = $detail->size ? 
                            ($locale === 'ar' ? ($detail->size->size_name_ar ?? $detail->size->size_name_en) : ($detail->size->size_name_en ?? $detail->size->size_name_ar)) : 
                            '-';
                        
                        return [
                            'id' => $detail->id,
                            'stock_id' => $detail->item_id,
                            'abaya_code' => $stock ? ($stock->abaya_code ?? '-') : '-',
                            'design_name' => $stock ? ($stock->design_name ?? '-') : '-',
                            'barcode' => $detail->item_barcode ?? '-',
                            'quantity' => (int)($detail->item_quantity ?? 0),
                            'price' => (float)($detail->item_price ?? 0),
                            'total' => (float)($detail->item_total ?? 0),
                            'color_id' => $detail->color_id,
                            'color_name' => $colorName,
                            'size_id' => $detail->size_id,
                            'size_name' => $sizeName,
                            'image' => $stock && $stock->images->first() ? asset($stock->images->first()->image_path) : null,
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $formattedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
