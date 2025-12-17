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

        $customers = Customer::query()
            ->where(function ($q) use ($search) {
                $q->where('phone', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'governorate', 'area']);

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
            'delivery.area_id' => 'nullable|integer',
            'delivery.city_id' => 'nullable|integer',
            'delivery.address' => 'nullable|string|max:2000',
            'delivery.fee' => 'nullable|numeric',
            'delivery.paid' => 'nullable|boolean',
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
        $deliveryInput = $request->input('delivery', []);

        // Human friendly order number: sequential, padded to 6 digits
        $orderNoInt = (PosOrders::max('order_no') ?? 0) + 1;
        $orderNoFormatted = str_pad($orderNoInt, 6, '0', STR_PAD_LEFT);

        /* ================= CUSTOMER ================= */

        $customerId = null;

        if (!empty($customerInput['phone']) || !empty($customerInput['name'])) {

            if (!empty($customerInput['phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $customerInput['phone']],
                    [
                        'name' => $customerInput['name'] ?? '',
                        'governorate' => $customerInput['area'] ?? null,
                        'area' => $customerInput['wilayah'] ?? null,
                        'area_id' => $deliveryInput['area_id'] ?? null,
                        'city_id' => $deliveryInput['city_id'] ?? null,
                        'address' => $customerInput['address'] ?? null,
                    ]
                );
            } else {
                $customer = Customer::create([
                    'name' => $customerInput['name'] ?? '',
                    'governorate' => $customerInput['area'] ?? null,
                    'area' => $customerInput['wilayah'] ?? null,
                    'area_id' => $deliveryInput['area_id'] ?? null,
                    'city_id' => $deliveryInput['city_id'] ?? null,
                    'address' => $customerInput['address'] ?? null,
                ]);
            }

            // Update existing customer safely
            if (!$customer->wasRecentlyCreated) {
                $customer->update([
                    'name' => $customerInput['name'] ?? $customer->name,
                    'governorate' => $customerInput['area'] ?? $customer->governorate,
                    'area' => $customerInput['wilayah'] ?? $customer->area,
                    'area_id' => $deliveryInput['area_id'] ?? $customer->area_id,
                    'city_id' => $deliveryInput['city_id'] ?? $customer->city_id,
                    'address' => $customerInput['address'] ?? $customer->address,
                ]);
            }

            $customerId = $customer->id;
        }

        /* ================= ORDER ================= */

        $order = PosOrders::create([
            'customer_id' => $customerId,
            'order_type' => $request->input('order_type', 'direct'),
            'delivery_area_id' => $deliveryInput['area_id'] ?? null,
            'delivery_city_id' => $deliveryInput['city_id'] ?? null,
            'delivery_address' => $deliveryInput['address'] ?? null,
            'delivery_fee' => $deliveryInput['fee'] ?? 0,
            'delivery_fee_paid' => !empty($deliveryInput['paid']),
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

            PosOrdersDetail::create([
                'order_id' => $order->id,
                'order_no' => $orderNoFormatted,
                'item_id' => $item['id'],
                'item_barcode' => $stock['barcode'] ?? '',
                'item_quantity' => $qty,
                'item_discount_price' => $discountShare, // store total discount for this line
                'item_price' => $linePrice,
                'item_total' => $effectiveLine,
                'item_tax' => $item['tax'] ?? 0,
                'item_profit' => $itemProfit,
                'added_by' => $userName,
                'user_id' => $userId,
                'branch_id' => $item['branch_id'] ?? null,
            ]);
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

            PosPaymentExpence::create([
                'order_id' => $order->id,
                'order_no' => $orderNoFormatted,
                'total_amount' => $pay['amount'],
                'accoun_id' => $pay['account_id'], // column name in migration
                'account_tax' => $pay['tax'] ?? null,
                'account_tax_fee' => $pay['tax_fee'] ?? null,
                'added_by' => $userName,
                'updated_by' => $userName,
                'user_id' => $userId,
            ]);
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
}
