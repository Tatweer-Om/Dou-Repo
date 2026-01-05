<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Stock;
use App\Models\Tailor;
use App\Models\Customer;
use App\Models\SpecialOrder;
use App\Models\Settings;
use Illuminate\Http\Request;
use App\Models\SpecialOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SpecialOrderController extends Controller
{
       public function index(){
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(5, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $stock = Stock::select('id', 'abaya_code as code', 'design_name as name', 'sales_price')->get();

        return view ('special_orders.special_order', compact('stock'));

    }

  public function show_specialorder()
{
    $sno = 0;

    $view_authspecialorder = SpecialOrder::with('items')->get(); // eager load items
    $json = [];

    if ($view_authspecialorder->count() > 0) {
        foreach ($view_authspecialorder as $order) {

            // Format created date
            $add_data = Carbon::parse($order->created_at)->format('d-m-Y (h:i a)');

            // Modal actions
            $modal = '
            <a href="javascript:void(0);" class="me-3 edit-staff" data-bs-toggle="modal" data-bs-target="#add_specialorder_modal" onclick=edit("'.$order->id.'")>
                <i class="fa fa-pencil fs-18 text-success"></i>
            </a>
            <a href="javascript:void(0);" onclick=del("'.$order->id.'")>
                <i class="fa fa-trash fs-18 text-danger"></i>
            </a>';

            // Increment serial number
            $sno++;

            // Loop through items and combine their info into a string
            $items_info = '';
            foreach ($order->items as $item) {
                $items_info .= '<div class="mb-1">';
                $items_info .= '<strong>'.$item->item_name.'</strong>';
                $items_info .= ' | Abaya Length: '.$item->abaya_length;
                $items_info .= ' | Bust: '.$item->bust;
                $items_info .= ' | Sleeves: '.$item->sleeves_length;
                $items_info .= ' | Buttons: '.($item->buttons ? 'Yes' : 'No');
                $items_info .= ' | Notes: '.$item->notes;
                $items_info .= '</div>';
            }

            $json[] = [
                '<span class="patient-info ps-0">'. $sno . '</span>',
                '<span class="text-nowrap ms-2">' . $order->customer_name . '</span>',
                '<span >' . $order->source . '</span>',
                '<span >' . ($order->send_as_gift ? 'Yes' : 'No') . '</span>',
                '<span >' . $order->gift_text . '</span>',
                '<span >' . $order->notes . '</span>',
                '<span >' . $order->added_by . '</span>',
                '<span >' . $add_data . '</span>',
                '<span>' . $items_info . '</span>',
                $modal
            ];
        }

        $response = [
            'success' => true,
            'aaData' => $json,
        ];
        echo json_encode($response);

    } else {
        $response = [
            'sEcho' => 0,
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'aaData' => [],
        ];
        echo json_encode($response);
    }
}


 public function add_specialorder(Request $request)
{
    try {
        DB::beginTransaction();

    $user_id = Auth::id();
    $user = User::find($user_id);
        $user_name = $user->user_name ?? 'System';

        // Log the incoming request for debugging
        \Log::info('Special Order Request:', [
            'customer' => $request->input('customer'),
            'orders_count' => count($request->input('orders', [])),
            'orders' => $request->input('orders'),
        ]);

        // Validate required fields
        $request->validate([
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.source' => 'required|string|in:whatsapp,walkin',
            'customer.area_id' => 'required|exists:areas,id',
            'customer.city_id' => 'required|exists:cities,id',
            'customer.address' => 'required|string',
            'orders' => 'required|array|min:1',
            'orders.*.stock_id' => 'nullable|exists:stocks,id',
            'orders.*.quantity' => 'required|integer|min:1',
            'orders.*.price' => 'required|numeric|min:0',
        ]);

        // Create or find customer
        $phone = $request->input('customer.phone');
        $areaId = $request->input('customer.area_id'); // Governorate ID
        $cityId = $request->input('customer.city_id'); // State/Area ID
        $address = $request->input('customer.address');
        
        if (!empty($phone)) {
            // If phone exists, find or create by phone
            $customer = Customer::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => $request->input('customer.name'),
                    'area_id' => $areaId,
                    'city_id' => $cityId,
                    'address' => $address,
                ]
            );

            // Update customer if phone exists but data changed
            if ($customer->wasRecentlyCreated === false) {
                $customer->name = $request->input('customer.name');
                $customer->area_id = $areaId;
                $customer->city_id = $cityId;
                $customer->address = $address;
                $customer->save();
            }
        } else {
            // If no phone, create new customer
            $customer = new Customer();
            $customer->name = $request->input('customer.name');
            $customer->phone = null;
            $customer->area_id = $areaId;
            $customer->city_id = $cityId;
            $customer->address = $address;
            $customer->save();
        }

        // Calculate total amount
        $totalAmount = $request->input('shipping_fee', 0);
        foreach ($request->input('orders', []) as $orderData) {
            $totalAmount += ($orderData['price'] ?? 0) * ($orderData['quantity'] ?? 1);
        }

        // Create special order
        $specialOrder = new SpecialOrder();
        $specialOrder->source = $request->input('customer.source');
        $specialOrder->customer_id = $customer->id;
        $specialOrder->send_as_gift = $request->input('customer.is_gift') === 'yes' ? true : false;
        $specialOrder->gift_text = $request->input('customer.gift_message');
        $specialOrder->shipping_fee = $request->input('shipping_fee', 0);
        $specialOrder->total_amount = $totalAmount;
        $specialOrder->paid_amount = 0;
        $specialOrder->status = 'new';
        $specialOrder->notes = $request->input('notes');
        $specialOrder->user_id = 1;
        $specialOrder->added_by = 'system_add';
        $specialOrder->save();

        // Save order items
        foreach ($request->input('orders', []) as $orderData) {
            $item = new SpecialOrderItem();
            $item->special_order_id = $specialOrder->id;
            $item->stock_id = $orderData['stock_id'] ?? null;
            $item->abaya_code = $orderData['abaya_code'] ?? null;
            $item->design_name = $orderData['design_name'] ?? null;
            $item->quantity = $orderData['quantity'] ?? 1;
            $item->price = $orderData['price'] ?? 0;
            $item->abaya_length = $orderData['length'] ?? null;
            $item->bust = $orderData['bust'] ?? null;
            $item->sleeves_length = $orderData['sleeves'] ?? null;
            $item->buttons = ($orderData['buttons'] ?? 'yes') === 'yes' ? true : false;
            $item->notes = $orderData['notes'] ?? null;
            $item->save();
        }

        DB::commit();
        
        // Refresh the special order to ensure items are loaded
        $specialOrder->refresh();
        $specialOrder->load('items');
        
        // Get customer contact (phone) for SMS
        $customerContact = $customer->phone ?? null;
        
        // Prepare SMS parameters for Special Order
        $smsParams = [
            'sms_status' => 2, // 2 is for Special Order
            'special_order_id' => $specialOrder->id,
            'contact' => $customerContact, // Customer phone number for sending SMS
        ];

        $smsContent = \get_sms($smsParams);
        
        // Send SMS if contact is available and content is generated
        if (!empty($customerContact) && !empty($smsContent)) {
            \sms_module($customerContact, $smsContent);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.order_saved_successfully', [], session('locale')),
            'special_order_id' => $specialOrder->id
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error saving order: ' . $e->getMessage()
        ], 500);
    }
}


  public function edit_specialorder(Request $request)
{
    $specialorder_id = $request->input('id');
    $specialorder = SpecialOrder::with('items')->find($specialorder_id);

    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    $data = [
        'specialorder_id' => $specialorder->id,
        'source' => $specialorder->source,
        'name' => $specialorder->customer_name,
        'contact' => $specialorder->contact,
        'city' => $specialorder->city,
        'area' => $specialorder->area,
        'send_as_gift' => $specialorder->send_as_gift,
        'gift_text' => $specialorder->gift_text,
        'notes' => $specialorder->notes,
        'items' => $specialorder->items, // Array of dresses
    ];

    return response()->json($data);
}


public function update_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;
    $specialorder_id = $request->input('specialorder_id');

    $specialorder = SpecialOrder::find($specialorder_id);
    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    $previousData = $specialorder->toArray();
    $specialorder->source = $request['source'];
    $specialorder->customer_name = $request['name'];
    $specialorder->contact = $request['contact'];
    $specialorder->city = $request['city'];
    $specialorder->area = $request['area'];
    $specialorder->send_as_gift = $request['send_as_gift'] ?? 0;
    $specialorder->gift_text = $request['gift_text'] ?? null;
    $specialorder->notes = $request['notes'] ?? null;
    $specialorder->added_by = 'system_update';
    $specialorder->user_id =  1;
    $specialorder->save();

    // Delete old items and insert new ones
    $specialorder->items()->delete();
    foreach ($request->items as $item) {
        $specialorder_item = new Stock();
        $specialorder_item->special_order_id = $specialorder->id;
        $specialorder_item->item_name = $item['item_name'];
        $specialorder_item->abaya_length = $item['abaya_length'] ?? null;
        $specialorder_item->bust = $item['bust'] ?? null;
        $specialorder_item->sleeves_length = $item['sleeves_length'] ?? null;
        $specialorder_item->buttons = $item['buttons'] ?? 0;
        $specialorder_item->notes = $item['notes'] ?? null;
        $specialorder_item->save();
    }

    // You can add history logging here following your previous pattern

    return response()->json(['message' => 'Special order updated successfully']);
}



public function delete_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;
    $specialorder_id = $request->input('id');

    $specialorder = SpecialOrder::find($specialorder_id);
    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    // Delete related items first
    $specialorder->items()->delete();

    // Add history logging here if needed

    $specialorder->delete();

    return response()->json(['message' => 'Special order deleted successfully']);
}


public function searchAbayas(Request $request)
{
    $search = $request->input('search', '');
    
    $query = Stock::with(['images']);
    
    if (!empty($search)) {
        $query->where(function($q) use ($search) {
            $q->where('abaya_code', 'LIKE', '%' . $search . '%')
              ->orWhere('design_name', 'LIKE', '%' . $search . '%')
              ->orWhere('barcode', 'LIKE', '%' . $search . '%');
        });
    }
    
    $stocks = $query->limit(20)->get();
    
    $abayas = $stocks->map(function($stock) {
        return [
            'id' => $stock->id,
            'code' => $stock->abaya_code,
            'name' => $stock->design_name ?? $stock->abaya_code,
            'price' => $stock->sales_price ?? 0,
            'image' => $stock->images->first() ? $stock->images->first()->image_path : '/images/placeholder.png'
        ];
    });
    
    return response()->json($abayas);
}

public function view_special_order()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(5, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('special_orders.view_special_order');
}

public function getOrdersList(Request $request)
{
    try {
        $orders = SpecialOrder::with(['customer.area', 'customer.city', 'items.stock.images', 'items.tailor'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $formattedOrders = $orders->map(function($order) {
            // Get first item image or placeholder (for list view)
            $firstItem = $order->items->first();
            $image = '/images/placeholder.png';
            if ($firstItem && $firstItem->stock && $firstItem->stock->images->first()) {
                $image = $firstItem->stock->images->first()->image_path;
            }

            // Get all items with their details
            $items = $order->items->map(function($item) {
                $itemImage = '/images/placeholder.png';
                if ($item->stock && $item->stock->images->first()) {
                    $itemImage = $item->stock->images->first()->image_path;
                }

                // Get original tailor from stock
                $originalTailor = '';
                $originalTailorName = '';
                if ($item->stock && $item->stock->tailor_id) {
                    $tailorIds = json_decode($item->stock->tailor_id, true);
                    if (!is_array($tailorIds)) {
                        $tailorIds = [$tailorIds];
                    }
                    if (!empty($tailorIds)) {
                        $tailors = \App\Models\Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();
                        $originalTailorName = implode(', ', $tailors);
                        $originalTailor = $item->stock->tailor_id;
                    }
                }

                // Get current tailor (if changed when sending to tailor)
                $currentTailorName = $item->tailor ? $item->tailor->tailor_name : null;

                return [
                    'id' => $item->id,
                    'abaya_code' => $item->abaya_code ?? 'N/A',
                    'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'quantity' => $item->quantity ?? 1,
                    'price' => floatval($item->price ?? 0),
                    'image' => $itemImage,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? false,
                    'notes' => $item->notes ?? '',
                    'tailor_status' => $item->tailor_status ?? 'new',
                    'tailor_id' => $item->tailor_id,
                    'tailor_name' => $currentTailorName,
                    'original_tailor' => $originalTailor,
                    'original_tailor_name' => $originalTailorName,
                ];
            });

            // Get tailor info (if available from items notes or order notes)
            $tailor = 'N/A';
            if ($firstItem && $firstItem->notes) {
                $tailor = $firstItem->notes;
            } elseif ($order->notes) {
                $tailor = $order->notes;
            }

            $customer = $order->customer;
            
            // Get governorate from area relationship or fallback to direct field
            $governorate = '';
            if ($customer && $customer->area) {
                // Use locale to get the correct language version
                $locale = session('locale', 'en');
                if ($locale === 'ar') {
                    $governorate = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                } else {
                    $governorate = $customer->area->area_name_en ?? $customer->area->area_name_ar ?? '';
                }
            } elseif ($customer && isset($customer->governorate)) {
                $governorate = $customer->governorate;
            }
            
            // Get city/state from city relationship or fallback to direct field
            $city = '';
            if ($customer && $customer->city) {
                // Use locale to get the correct language version
                $locale = session('locale', 'en');
                if ($locale === 'ar') {
                    $city = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                } else {
                    $city = $customer->city->city_name_en ?? $customer->city->city_name_ar ?? '';
                }
            } elseif ($customer && isset($customer->area)) {
                $city = $customer->area; // Fallback for old data
            }
            
            $location = trim($governorate . ($city ? ' - ' . $city : ''));

            // Calculate and update order status based on items' tailor_status
            $this->updateOrderStatusBasedOnItems($order);
            $calculatedStatus = $order->status;

            // Generate order number: YYYY-00ID (e.g., 2025-0001)
            $orderDate = Carbon::parse($order->created_at);
            $orderNumber = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

            return [
                'id' => $order->id,
                'order_no' => $orderNumber,
                'customer' => optional($customer)->name ?? 'N/A',
                'governorate' => $governorate,
                'city' => $city,
                'location' => $location,
                'date' => $order->created_at->format('Y-m-d'),
                'status' => $calculatedStatus,
                'source' => $order->source,
                'total' => floatval($order->total_amount ?? 0),
                'paid' => floatval($order->paid_amount ?? 0),
                'image' => $image, // First item image for list view
                'items' => $items, // All items array
                'tailor' => $tailor,
                'notes' => $order->notes ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $formattedOrders
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getOrdersList: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching orders: ' . $e->getMessage(),
            'orders' => []
        ], 500);
    }
}

public function recordPayment(Request $request)
{
    try {
        $orderId = $request->input('order_id');
        $amount = $request->input('amount');
        $accountId = $request->input('account_id');

        $order = SpecialOrder::findOrFail($orderId);
        
        $newPaidAmount = $order->paid_amount + $amount;
        
        // Validate amount doesn't exceed total
        if ($newPaidAmount > $order->total_amount + 0.001) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds total amount'
            ], 422);
        }

        // Validate account_id is provided
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account is required'
            ], 422);
        }

        $order->paid_amount = $newPaidAmount;
        $order->account_id = $accountId;
        
        // If fully paid and not delivered, set status to ready
        if (abs($order->total_amount - $newPaidAmount) < 0.001 && $order->status !== 'delivered') {
            $order->status = 'ready';
        }
        
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'order' => [
                'id' => $order->id,
                'paid' => floatval($order->paid_amount),
                'status' => $order->status
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error recording payment: ' . $e->getMessage()
        ], 500);
    }
}

public function updateDeliveryStatus(Request $request)
{
    try {
        $orderIds = $request->input('order_ids', []);
        
        if (empty($orderIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No orders selected'
            ], 422);
        }

        $updated = SpecialOrder::whereIn('id', $orderIds)
            ->where('status', 'ready')
            ->update(['status' => 'delivered']);

        return response()->json([
            'success' => true,
            'message' => "{$updated} order(s) marked as delivered",
            'updated_count' => $updated
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error updating delivery status: ' . $e->getMessage()
        ], 500);
    }
}

public function deleteOrder(Request $request)
{
    try {
        $orderId = $request->input('order_id');
        
        $order = SpecialOrder::findOrFail($orderId);
        
        // Delete related items first
        $order->items()->delete();
        
        // Delete the order
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting order: ' . $e->getMessage()
        ], 500);
    }
}


public function send_request()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(7, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('special_orders.send_request');
}

public function getTailorAssignmentsData(Request $request)
{
    try {
        // Get all tailors
        $tailors = Tailor::select('id', 'tailor_name as name')->get();

        // Get new items (not yet assigned to tailor)
        $newItems = SpecialOrderItem::with(['specialOrder.customer', 'stock.images'])
            ->where(function($query) {
                $query->whereNull('tailor_id')
                      ->orWhere('tailor_status', 'new');
            })
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                $stock = $item->stock;
                $image = optional(optional($stock)->images->first())->image_path ?? '/images/placeholder.png';
                
                if ($stock && $stock->images->first()) {
                    $image = $stock->images->first()->image_path;
                }

                // Get original tailor from stock if exists
        $originalTailor = '';
        $originalTailorId = null;

        if ($stock && $stock->tailor_id) {

            // Convert JSON string to PHP array
            $tailorIds = json_decode($stock->tailor_id, true);

            // Ensure always an array
            if (!is_array($tailorIds)) {
                $tailorIds = [$tailorIds];
            }

            // Get first tailor ID for auto-assignment
            if (!empty($tailorIds)) {
                $originalTailorId = is_numeric($tailorIds[0]) ? (int)$tailorIds[0] : null;
            }

    // Fetch all tailors
    $tailors = Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();

    // Join names into a single string
    $originalTailor = implode(', ', $tailors);
}
                
                // Generate order number in same format as view_special_order: YYYY-00ID
                $orderNo = '—';
                if ($order) {
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                }
                
                return [
                    'rowId' => $item->id,
                    'orderId' => $order->id ?? 0,
                    'order_no' => $orderNo,
                    'source' => $order->source ?? '',
                    'customer' => $order->customer->name ?? 'N/A',
                    'code' => $item->abaya_code ?? '',
                    'abayaName' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'image' => $image,
                    'quantity' => $item->quantity ?? 1,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? true,
                    'notes' => $item->notes ?? '',
                    'date' => $order->created_at->format('Y-m-d'),
                    'originalTailor' => $originalTailor,
                    'originalTailorId' => $originalTailorId,
                    'tailor_id' => null,
                    'tailor' => '',
                    'status' => 'new',
                ];
            });

        // Get processing items (assigned to tailor but not received)
        $processingItems = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'tailor'])
            ->where('tailor_status', 'processing')
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                $stock = $item->stock;
                $image = '/images/placeholder.png';
                
                if ($stock && $stock->images->first()) {
                    $image = $stock->images->first()->image_path;
                }

                // Get original tailor from stock if exists
                $originalTailor = '';
                $originalTailorId = null;
                if ($stock && $stock->tailor_id) {
                    // Convert JSON string to PHP array
                    $tailorIds = json_decode($stock->tailor_id, true);

                    // Ensure always an array
                    if (!is_array($tailorIds)) {
                        $tailorIds = [$tailorIds];
                    }

                    // Get first tailor ID for reference
                    if (!empty($tailorIds)) {
                        $originalTailorId = is_numeric($tailorIds[0]) ? (int)$tailorIds[0] : null;
                    }

                    // Fetch all tailors
                    $tailors = Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();

                    // Join names into a single string
                    $originalTailor = implode(', ', $tailors);
                }

                // Generate order number in same format as view_special_order: YYYY-00ID
                $orderNo = '—';
                if ($order) {
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                }

                return [
                    'rowId' => $item->id,
                    'orderId' => $order->id ?? 0,
                    'order_no' => $orderNo,
                    'source' => $order->source ?? '',
                    'customer' => $order->customer->name ?? 'N/A',
                    'code' => $item->abaya_code ?? '',
                    'abayaName' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'image' => $image,
                    'quantity' => $item->quantity ?? 1,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? true,
                    'notes' => $item->notes ?? '',
                   'date' => $item->sent_to_tailor_at
    ? \Carbon\Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d')
    : \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
                    'originalTailor' => $originalTailor,
                    'originalTailorId' => $originalTailorId,
                    'tailor_id' => $item->tailor_id,
                    'tailor' => $item->tailor ? $item->tailor->tailor_name : '',
                    'tailor_name' => $item->tailor ? $item->tailor->tailor_name : '',
                    'status' => 'processing',
                ];
            });

        return response()->json([
            'success' => true,
            'tailors' => $tailors,
            'new' => $newItems,
            'processing' => $processingItems,
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getTailorAssignmentsData: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching data: ' . $e->getMessage(),
            'tailors' => [],
            'new' => [],
            'processing' => [],
        ], 500);
    }
}

    /**
     * Update order status based on items' tailor_status
     */
    private function updateOrderStatusBasedOnItems($order)
    {
        if ($order->status === 'delivered') {
            return; // Don't change delivered orders
        }

        if ($order->items->count() === 0) {
            $order->status = 'new';
            $order->save();
            return;
        }

        $itemStatuses = $order->items->pluck('tailor_status')->filter()->toArray();
        
        if (empty($itemStatuses)) {
            $order->status = 'new';
        } else {
            $allNew = count(array_filter($itemStatuses, function($status) {
                return $status === 'new';
            })) === count($itemStatuses);
            
            $allReceived = count(array_filter($itemStatuses, function($status) {
                return $status === 'received';
            })) === count($itemStatuses);
            
            $hasReceived = count(array_filter($itemStatuses, function($status) {
                return $status === 'received';
            })) > 0;
            
            $hasProcessing = count(array_filter($itemStatuses, function($status) {
                return $status === 'processing';
            })) > 0;
            
            if ($allNew) {
                $order->status = 'new';
            } elseif ($allReceived) {
                $order->status = 'ready';
            } elseif ($hasReceived && ($hasProcessing || count(array_filter($itemStatuses, function($status) {
                return $status === 'new';
            })) > 0)) {
                // Some items are ready but others are still with tailor or new
                $order->status = 'partially_ready';
            } else {
                $order->status = 'processing';
            }
        }
        
        $order->save();
    }

public function assignItemsToTailor(Request $request)
{
    try {
        $assignments = $request->input('assignments', []);

        if (empty($assignments)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected'
            ], 422);
        }

        DB::beginTransaction();

        $orderIds = [];
        foreach ($assignments as $assignment) {
            $itemId = $assignment['item_id'] ?? null;
            $tailorId = $assignment['tailor_id'] ?? null;

            if (!$itemId || !$tailorId) {
                continue;
            }

            $item = SpecialOrderItem::find($itemId);
            if ($item) {
                $item->tailor_id = $tailorId;
                $item->tailor_status = 'processing';
                $item->sent_to_tailor_at = now();
                $item->save();
                
                if (!in_array($item->special_order_id, $orderIds)) {
                    $orderIds[] = $item->special_order_id;
                }
            }
        }

        // Update order statuses based on items
        foreach ($orderIds as $orderId) {
            $order = SpecialOrder::with('items')->find($orderId);
            if ($order) {
                $this->updateOrderStatusBasedOnItems($order);
            }
        }

        DB::commit();

        // Check for late deliveries after assignment (don't wait for response)
        try {
            $this->checkAndMarkLateDeliveries();
        } catch (\Exception $e) {
            // Log but don't fail the assignment
            \Log::error('Error checking late deliveries after assignment: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => count($assignments) . ' item(s) assigned to tailor successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in assignItemsToTailor: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error assigning items: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Check and mark items as late delivery based on settings
 */
public function checkAndMarkLateDeliveries()
{
    try {
        $settings = Settings::getSettings();
        $lateDeliveryWeeks = $settings->late_delivery_weeks ?? 2;
        
        // Get all items that are with tailor (processing status) and not yet marked as late
        $itemsWithTailor = SpecialOrderItem::where('tailor_status', 'processing')
            ->whereNotNull('sent_to_tailor_at')
            ->where('is_late_delivery', false)
            ->get();
        
        $now = Carbon::now();
        $markedLate = 0;
        
        foreach ($itemsWithTailor as $item) {
            $sentDate = Carbon::parse($item->sent_to_tailor_at);
            $weeksPassed = $sentDate->diffInWeeks($now);
            
            // If weeks passed exceeds the late delivery threshold, mark as late
            if ($weeksPassed >= $lateDeliveryWeeks) {
                $item->is_late_delivery = true;
                $item->marked_late_at = $now;
                $item->save();
                $markedLate++;
            }
        }
        
        return response()->json([
            'success' => true,
            'marked_late' => $markedLate
        ]);
    } catch (\Exception $e) {
        \Log::error('Error checking late deliveries: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error checking late deliveries'
        ], 500);
    }
}

/**
 * Get late delivery items for dashboard
 */
public function getLateDeliveries()
{
    $lateItems = SpecialOrderItem::with(['specialOrder.customer', 'tailor', 'stock.images'])
        ->where('is_late_delivery', true)
        ->where('tailor_status', 'processing')
        ->orderBy('marked_late_at', 'DESC')
        ->get()
        ->map(function($item) {
            $order = $item->specialOrder;
            $tailor = $item->tailor;
            $stock = $item->stock;
            
            $image = '/images/placeholder.png';
            if ($stock && $stock->images->first()) {
                $imagePath = $stock->images->first()->image_path;
                if (strpos($imagePath, 'http') === 0) {
                    $image = $imagePath;
                } else {
                    $image = asset('images/stock_images/' . basename($imagePath));
                }
            }
            
            $sentDate = $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
            $daysLate = $sentDate ? $sentDate->diffInDays(Carbon::now()) : 0;
            
            $orderDate = $order ? Carbon::parse($order->created_at) : null;
            $orderNo = $orderDate ? $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT) : '—';
            
            return [
                'id' => $item->id,
                'order_no' => $orderNo,
                'order_id' => $order->id ?? 0,
                'customer_name' => $order->customer->name ?? 'N/A',
                'customer_phone' => $order->customer->phone ?? 'N/A',
                'abaya_code' => $item->abaya_code ?? 'N/A',
                'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                'image' => $image,
                'tailor_name' => $tailor->tailor_name ?? 'N/A',
                'tailor_id' => $item->tailor_id,
                'sent_date' => $sentDate ? $sentDate->format('Y-m-d') : null,
                'days_late' => $daysLate,
                'marked_late_at' => $item->marked_late_at ? Carbon::parse($item->marked_late_at)->format('Y-m-d H:i') : null,
            ];
        });
    
    return response()->json([
        'success' => true,
        'items' => $lateItems
    ]);
}

public function markTailorItemsReceived(Request $request)
{
    try {
        $itemIds = $request->input('item_ids', []);

        if (empty($itemIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected'
            ], 422);
        }

        DB::beginTransaction();

        // Get items before update to get order IDs
        $items = SpecialOrderItem::whereIn('id', $itemIds)
            ->where('tailor_status', 'processing')
            ->get();
        
        $orderIds = $items->pluck('special_order_id')->unique()->toArray();

        $updated = SpecialOrderItem::whereIn('id', $itemIds)
            ->where('tailor_status', 'processing')
            ->update([
                'tailor_status' => 'received',
                'received_from_tailor_at' => now(),
                'is_late_delivery' => false // Unmark as late when received
            ]);

        // Update order statuses based on items
        foreach ($orderIds as $orderId) {
            $order = SpecialOrder::with('items')->find($orderId);
            if ($order) {
                $this->updateOrderStatusBasedOnItems($order);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $updated . ' item(s) marked as received successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in markTailorItemsReceived: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error marking items as received: ' . $e->getMessage()
        ], 500);
    }
}

public function maintenance()
{
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(5, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

    return view('special_orders.maintenance');
}

public function getMaintenanceData()
{
    try {
        // Get all tailors for the dropdown
        $tailors = Tailor::select('id', 'tailor_name as name')->get();

        // Get items that are ready for maintenance
        // Only show items where tailor_status = 'received' (item is individually ready)
        // Also include items that are already in maintenance (even from delivered orders)
        // EXCLUDE items from delivered orders that are NOT in maintenance
        // EXCLUDE items that are delivered from maintenance (maintenance_status = 'delivered')
        $rawItems = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'maintenanceTailor'])
            ->where(function($query) {
                // Show items where:
                // 1. Item's tailor_status is 'received' (item is ready individually) AND order is NOT delivered AND not yet in maintenance
                // OR
                // 2. Item is already in maintenance (delivered_to_tailor or received_from_tailor) - even from delivered orders
                $query->where(function($q) {
                    // Items that are ready (tailor_status = 'received') from non-delivered orders and not yet in maintenance
                    $q->where('tailor_status', 'received')
                      ->whereNull('maintenance_status')
                      ->whereHas('specialOrder', function($orderQ) {
                          $orderQ->where('status', '!=', 'delivered');
                      });
                })
                ->orWhere(function($q) {
                    // Items that are already in maintenance (can be from delivered orders)
                    $q->whereIn('maintenance_status', ['delivered_to_tailor', 'received_from_tailor']);
                });
            })
            ->where(function($query) {
                // EXCLUDE items that are delivered from maintenance (maintenance_status = 'delivered')
                $query->whereNull('maintenance_status')
                      ->orWhereIn('maintenance_status', ['delivered_to_tailor', 'received_from_tailor']);
            })
            ->get();

        // Group items by same type (abaya_code, length, bust, sleeves) and same order
        $groupedItems = [];
        foreach ($rawItems as $item) {
            $order = $item->specialOrder;
            $stock = $item->stock;
            $customer = $order ? $order->customer : null;
            
            // Create a unique key for grouping: order_id + abaya_code + length + bust + sleeves
            $groupKey = ($order ? $order->id : 'no_order') . '_' . 
                       ($item->abaya_code ?? 'no_code') . '_' . 
                       ($item->abaya_length ?? 'no_length') . '_' . 
                       ($item->bust ?? 'no_bust') . '_' . 
                       ($item->sleeves_length ?? 'no_sleeves');
            
            if (!isset($groupedItems[$groupKey])) {
                // Get image
                $image = '/images/placeholder.png';
                if ($stock && $stock->images && $stock->images->first()) {
                    $image = $stock->images->first()->image_path;
                }

                // Generate order number
                $orderNo = '—';
                if ($order) {
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                }

                // Get the first item's maintenance status (if any item in group has maintenance status)
                $maintenanceStatus = null;
                $deliveryCharges = 0;
                $repairCost = 0;
                $costBearer = null;
                $transferNumber = null;
                $maintenanceNotes = null;
                
                // Check if any item in this group is already in maintenance
                $maintenanceItem = $rawItems->first(function($i) use ($item, $order) {
                    return ($i->specialOrder && $i->specialOrder->id === ($order ? $order->id : null)) &&
                           ($i->abaya_code === $item->abaya_code) &&
                           ($i->abaya_length == $item->abaya_length) &&
                           ($i->bust == $item->bust) &&
                           ($i->sleeves_length == $item->sleeves_length) &&
                           $i->maintenance_status;
                });
                
                if ($maintenanceItem) {
                    $maintenanceStatus = $maintenanceItem->maintenance_status;
                    $deliveryCharges = $maintenanceItem->maintenance_delivery_charges ?? 0;
                    $repairCost = $maintenanceItem->maintenance_repair_cost ?? 0;
                    $costBearer = $maintenanceItem->maintenance_cost_bearer ?? null;
                    $transferNumber = $maintenanceItem->maintenance_transfer_number ?? null;
                    $maintenanceNotes = $maintenanceItem->maintenance_notes ?? null;
                }

                // Calculate available quantity (only items not yet sent to tailor)
                $availableQty = ($item->maintenance_status === null || $item->maintenance_status !== 'delivered_to_tailor') 
                    ? ($item->quantity ?? 1) 
                    : 0;

                $groupedItems[$groupKey] = [
                    'id' => $item->id, // Use first item's ID as representative
                    'item_ids' => [$item->id], // Store all item IDs in this group
                    'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'abaya_code' => $item->abaya_code ?? 'N/A',
                    'abaya_length' => $item->abaya_length ?? null,
                    'bust' => $item->bust ?? null,
                    'sleeves_length' => $item->sleeves_length ?? null,
                    'order_no' => $orderNo,
                    'order_id' => $order ? $order->id : null,
                    'customer_name' => $customer ? $customer->name : 'N/A',
                    'customer_phone' => $customer ? $customer->phone : 'N/A',
                    'maintenance_status' => $maintenanceStatus,
                    'image' => $image,
                    'delivery_charges' => $deliveryCharges,
                    'repair_cost' => $repairCost,
                    'cost_bearer' => $costBearer,
                    'transfer_number' => $transferNumber,
                    'order_status' => $order ? $order->status : null,
                    'maintenance_notes' => $maintenanceNotes,
                    'quantity' => $item->quantity ?? 1, // Total quantity in group
                    'available_quantity' => $availableQty, // Quantity available for maintenance
                ];
            } else {
                // Add this item's quantity to the group
                $groupedItems[$groupKey]['quantity'] += ($item->quantity ?? 1);
                
                // Only add to available_quantity if item is not yet sent to tailor
                if ($item->maintenance_status === null || $item->maintenance_status !== 'delivered_to_tailor') {
                    $groupedItems[$groupKey]['available_quantity'] += ($item->quantity ?? 1);
                }
                
                $groupedItems[$groupKey]['item_ids'][] = $item->id;
            }
        }

        // Convert grouped items to array
        $items = array_values($groupedItems);

        // Calculate statistics
        $statistics = [
            'delivered_to_tailor' => SpecialOrderItem::where('maintenance_status', 'delivered_to_tailor')->count(),
            'received_from_tailor' => SpecialOrderItem::where('maintenance_status', 'received_from_tailor')->count(),
        ];

        return response()->json([
            'success' => true,
            'items' => $items,
            'tailors' => $tailors,
            'statistics' => $statistics
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getMaintenanceData: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching maintenance data: ' . $e->getMessage(),
            'items' => [],
            'tailors' => [],
            'statistics' => ['delivered_to_tailor' => 0, 'received_from_tailor' => 0]
        ], 500);
    }
}

public function getRepairHistory()
{
    try {
        // Get items that have been received from tailor or delivered (completed maintenance)
        $history = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'maintenanceTailor'])
            ->whereIn('maintenance_status', ['received_from_tailor', 'delivered'])
            ->orderBy('repaired_delivered_at', 'DESC')
            ->orderBy('repaired_at', 'DESC')
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                $stock = $item->stock;
                $customer = $order ? $order->customer : null;
                $tailor = $item->maintenanceTailor;

                // Generate order number in same format as view_special_order: YYYY-00ID
                $orderNo = '—';
                if ($order) {
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                }

                return [
                    'id' => $item->id,
                    'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'abaya_code' => $item->abaya_code ?? 'N/A',
                    'order_no' => $orderNo,
                    'transfer_number' => $item->maintenance_transfer_number ?? '—',
                    'customer_name' => $customer ? $customer->name : 'N/A',
                    'customer_phone' => $customer ? $customer->phone : 'N/A',
                    'tailor_name' => $tailor ? $tailor->tailor_name : 'N/A',
                    'sent_date' => $item->sent_for_repair_at ? Carbon::parse($item->sent_for_repair_at)->format('Y-m-d') : '—',
                    'received_date' => $item->repaired_at ? Carbon::parse($item->repaired_at)->format('Y-m-d') : '—',
                    'delivered_date' => $item->repaired_delivered_at ? Carbon::parse($item->repaired_delivered_at)->format('Y-m-d') : '—',
                    'delivery_charges' => $item->maintenance_delivery_charges ?? 0,
                    'repair_cost' => $item->maintenance_repair_cost ?? 0,
                    'cost_bearer' => $item->maintenance_cost_bearer ?? null,
                    'maintenance_status' => $item->maintenance_status,
                    'maintenance_notes' => $item->maintenance_notes ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getRepairHistory: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching repair history: ' . $e->getMessage(),
            'history' => []
        ], 500);
    }
}

public function sendForRepair(Request $request)
{
    try {
        DB::beginTransaction();

        $itemId = $request->input('item_id');
        $itemIds = $request->input('item_ids', [$itemId]); // Get all item IDs in the group
        $quantity = $request->input('quantity'); // Quantity to send (if > 1)
        $tailorId = $request->input('tailor_id');
        $maintenanceNotes = $request->input('maintenance_notes');

        if (!$itemId || !$tailorId) {
            return response()->json([
                'success' => false,
                'message' => 'Item ID and Tailor ID are required'
            ], 422);
        }

        // Get all items in the group
        $items = SpecialOrderItem::whereIn('id', $itemIds)->get();
        
        if ($items->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No items found'
            ], 404);
        }

        // Calculate total available quantity
        $totalQuantity = $items->sum('quantity');
        
        // If quantity is specified and > 1, we need to handle splitting
        if ($quantity && $quantity > 1 && $quantity <= $totalQuantity) {
            $remainingQuantity = $totalQuantity - $quantity;
            $sentCount = 0;
            
            // Send items until we reach the requested quantity
            foreach ($items as $item) {
                if ($sentCount >= $quantity) break;
                
                if ($item->quantity <= ($quantity - $sentCount)) {
                    // Send entire item
                    $item->maintenance_status = 'delivered_to_tailor';
                    $item->maintenance_tailor_id = $tailorId;
                    $item->maintenance_notes = $maintenanceNotes;
                    $item->sent_for_repair_at = now();
                    $item->save();
                    $sentCount += $item->quantity;
                } else {
                    // Need to split: create new item for remaining quantity
                    $sendQty = $quantity - $sentCount;
                    $remainingQty = $item->quantity - $sendQty;
                    
                    // Update current item to send quantity
                    $item->quantity = $sendQty;
                    $item->maintenance_status = 'delivered_to_tailor';
                    $item->maintenance_tailor_id = $tailorId;
                    $item->maintenance_notes = $maintenanceNotes;
                    $item->sent_for_repair_at = now();
                    $item->save();
                    $sentCount += $sendQty;
                    
                    // Create new item for remaining quantity
                    if ($remainingQty > 0) {
                        $newItem = $item->replicate();
                        $newItem->quantity = $remainingQty;
                        $newItem->maintenance_status = null;
                        $newItem->maintenance_tailor_id = null;
                        $newItem->maintenance_notes = null;
                        $newItem->sent_for_repair_at = null;
                        $newItem->save();
                    }
                }
            }
            
            $message = $quantity . ' piece(s) sent to tailor for repair successfully';
        } else {
            // Send all items in the group (quantity = 1 or not specified)
            foreach ($items as $item) {
                $item->maintenance_status = 'delivered_to_tailor';
                $item->maintenance_tailor_id = $tailorId;
                $item->maintenance_notes = $maintenanceNotes;
                $item->sent_for_repair_at = now();
                $item->save();
            }
            
            $message = 'Item(s) sent to tailor for repair successfully';
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in sendForRepair: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error sending item for repair: ' . $e->getMessage()
        ], 500);
    }
}

public function receiveFromTailor(Request $request)
{
    try {
        DB::beginTransaction();

        $itemId = $request->input('item_id');

        if (!$itemId) {
            return response()->json([
                'success' => false,
                'message' => 'Item ID is required'
            ], 422);
        }

        $item = SpecialOrderItem::findOrFail($itemId);

        // Just update status to received - no costs at this stage
        $item->maintenance_status = 'received_from_tailor';
        $item->repaired_at = now();
        $item->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item received from tailor successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in receiveFromTailor: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error receiving item from tailor: ' . $e->getMessage()
        ], 500);
    }
}

public function markRepairedDelivered(Request $request)
{
    try {
        DB::beginTransaction();

        $itemId = $request->input('item_id');
        $deliveryCharges = floatval($request->input('delivery_charges', 0));
        $repairCost = floatval($request->input('repair_cost', 0));
        $costBearer = $request->input('cost_bearer');
        $accountId = $request->input('account_id');
        $paymentAmount = floatval($request->input('payment_amount', 0));

        if (!$itemId) {
            return response()->json([
                'success' => false,
                'message' => 'Item ID is required'
            ], 422);
        }

        if (!$costBearer) {
            return response()->json([
                'success' => false,
                'message' => 'Cost bearer is required'
            ], 422);
        }

        // If customer is bearer and there are costs, require account and payment
        if ($costBearer === 'customer' && ($deliveryCharges > 0 || $repairCost > 0)) {
            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account selection is required when customer bears the cost'
                ], 422);
            }
            if ($paymentAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount is required when customer bears the cost'
                ], 422);
            }
        }

        // Ensure charges are 0 if company is the bearer
        if ($costBearer === 'company') {
            $deliveryCharges = 0;
            $repairCost = 0;
        }

        $item = SpecialOrderItem::with('specialOrder')->findOrFail($itemId);
        $order = $item->specialOrder;

        if (!$order) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Special order not found for this item'
            ], 404);
        }

        // Update item with cost details
        $item->maintenance_delivery_charges = $deliveryCharges;
        $item->maintenance_repair_cost = $repairCost;
        $item->maintenance_cost_bearer = $costBearer;
        $item->repaired_delivered_at = now();
        // Mark item as delivered from maintenance - this will exclude it from current items
        $item->maintenance_status = 'delivered';
        
        // Build maintenance notes with cost details
        $costDetails = [];
        if ($deliveryCharges > 0) {
            $costDetails[] = 'Delivery Charges: ' . number_format($deliveryCharges, 3) . ' ر.ع';
        }
        if ($repairCost > 0) {
            $costDetails[] = 'Repair Cost: ' . number_format($repairCost, 3) . ' ر.ع';
        }
        $costDetailsText = !empty($costDetails) ? "\n" . implode("\n", $costDetails) . "\nCost Bearer: " . ($costBearer === 'customer' ? 'Customer' : 'Company') : '';
        
        // Append cost details to maintenance_notes if not empty
        if (!empty($costDetailsText)) {
            $existingNotes = $item->maintenance_notes ?? '';
            $item->maintenance_notes = $existingNotes . $costDetailsText;
        }
        
        $item->save();

        // Update order with costs and payment
        if ($costBearer === 'customer' && ($deliveryCharges > 0 || $repairCost > 0)) {
            $totalAdditionalCharges = $deliveryCharges + $repairCost;
            $order->total_amount = floatval($order->total_amount) + $totalAdditionalCharges;
            
            // Update paid amount and account
            $order->paid_amount = floatval($order->paid_amount) + $paymentAmount;
            $order->account_id = $accountId;
            $order->status = 'delivered';
        }
        
        // Update shipping_cost and repair_cost in special_orders table
        $order->shipping_cost = $deliveryCharges;
        $order->repair_cost = $repairCost;
        $order->save();

        // Update account balance if payment was made
        if ($accountId && $paymentAmount > 0) {
            $account = \App\Models\Account::find($accountId);
            if ($account) {
                $account->opening_balance = floatval($account->opening_balance) + $paymentAmount;
                $account->save();
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Item delivered successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in markRepairedDelivered: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error delivering item: ' . $e->getMessage()
        ], 500);
    }
}

public function searchDeliveredOrders(Request $request)
{
    try {
        $search = $request->input('search', '');
        
        if (empty($search)) {
            return response()->json([
                'success' => true,
                'orders' => []
            ]);
        }

        // Search delivered orders by customer name, order number, abaya code, or customer phone
        $orders = SpecialOrder::with(['customer', 'items.stock.images'])
            ->where('status', 'delivered')
            ->where(function($query) use ($search) {
                // Search by customer name
                $query->whereHas('customer', function($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%');
                })
                // Search by customer phone
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('phone', 'LIKE', '%' . $search . '%');
                })
                // Search by order number (format: YYYY-00ID or just ID)
                ->orWhere('id', 'LIKE', '%' . preg_replace('/[^0-9]/', '', $search) . '%')
                ->orWhereRaw("CONCAT(YEAR(created_at), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $search . '%'])
                // Search by abaya code in items
                ->orWhereHas('items', function($q) use ($search) {
                    $q->where('abaya_code', 'LIKE', '%' . $search . '%');
                });
            })
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()
            ->map(function($order) {
                $customer = $order->customer;
                
                // Generate order number
                $orderDate = Carbon::parse($order->created_at);
                $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                
                return [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $customer ? $customer->name : 'N/A',
                    'customer_phone' => $customer ? $customer->phone : 'N/A',
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in searchDeliveredOrders: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error searching orders: ' . $e->getMessage(),
            'orders' => []
        ], 500);
    }
}

public function getDeliveredOrderItems(Request $request)
{
    try {
        $orderId = $request->input('order_id');
        
        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID is required'
            ], 422);
        }

        // Only get orders with status 'delivered'
        $order = SpecialOrder::with(['customer', 'items.stock.images'])
            ->where('status', 'delivered')
            ->findOrFail($orderId);
        
        // Generate order number
        $orderDate = Carbon::parse($order->created_at);
        $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

        // Only return items from this delivered order
        $items = $order->items->map(function($item) {
            $stock = $item->stock;
            
            // Get image
            $image = '/images/placeholder.png';
            if ($stock && $stock->images && $stock->images->first()) {
                $image = $stock->images->first()->image_path;
            }

            return [
                'id' => $item->id,
                'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                'abaya_code' => $item->abaya_code ?? 'N/A',
                'abaya_length' => $item->abaya_length ?? null,
                'bust' => $item->bust ?? null,
                'sleeves_length' => $item->sleeves_length ?? null,
                'quantity' => $item->quantity ?? 1,
                'image' => $image,
                'maintenance_status' => $item->maintenance_status ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_no' => $orderNo,
                'customer_name' => $order->customer ? $order->customer->name : 'N/A',
                'customer_phone' => $order->customer ? $order->customer->phone : 'N/A',
            ],
            'items' => $items
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getDeliveredOrderItems: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching order items: ' . $e->getMessage()
        ], 500);
    }
}

public function showBill($id)
{
    try {
        $specialOrder = SpecialOrder::with(['customer.area', 'customer.city', 'items.stock.images'])
            ->findOrFail($id);
        
        // Generate order number: YYYY-00ID (e.g., 2025-0001)
        $orderDate = Carbon::parse($specialOrder->created_at);
        $orderNumber = $orderDate->format('Y') . '-' . str_pad($specialOrder->id, 4, '0', STR_PAD_LEFT);
        
        return view('bills.special_order_bill', [
            'specialOrder' => $specialOrder,
            'orderNumber' => $orderNumber
        ]);
    } catch (\Exception $e) {
        \Log::error('Error showing special order bill: ' . $e->getMessage());
        abort(404, 'Special order not found');
    }
    }

    /**
     * Show tailor orders list page
     */
    public function tailorOrdersList()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(7, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $tailors = Tailor::orderBy('tailor_name')->get();
        return view('tailors.tailor_orders_list', compact('tailors'));
    }

    /**
     * Get tailor orders list data
     */
    public function getTailorOrdersList(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            
            if (!$tailorId) {
                return response()->json([
                    'success' => true,
                    'orders' => []
                ]);
            }

            $items = SpecialOrderItem::with([
                'specialOrder.customer.city',
                'specialOrder.customer.area',
                'specialOrder.customer'
            ])
            ->where('tailor_id', $tailorId)
            ->whereNotNull('tailor_id')
            ->orderByRaw('COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at) DESC')
            ->paginate(10);

            $formattedOrders = $items->map(function($item) {
                $order = $item->specialOrder;
                $customer = $order->customer ?? null;
                
                // Get size measurements
                $sizeInfo = [];
                if ($item->abaya_length) $sizeInfo[] = 'Length: ' . $item->abaya_length;
                if ($item->bust) $sizeInfo[] = 'Bust: ' . $item->bust;
                if ($item->sleeves_length) $sizeInfo[] = 'Sleeves: ' . $item->sleeves_length;
                $sizeText = !empty($sizeInfo) ? implode(', ', $sizeInfo) : '-';
                
                // Get address from city and area
                $address = '';
                if ($customer) {
                    $addressParts = [];
                    if ($customer->area) $addressParts[] = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                    if ($customer->city) $addressParts[] = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                    $address = implode(', ', array_filter($addressParts)) ?: '-';
                }
                
                return [
                    'id' => $item->id,
                    'order_no' => 'SO-' . str_pad($order->id ?? 0, 6, '0', STR_PAD_LEFT),
                    'dress_name' => $item->design_name ?? '-',
                    'dress_code' => $item->abaya_code ?? '-',
                    'size' => $sizeText,
                    'quantity' => $item->quantity ?? 1,
                    'buttons' => $item->buttons ? true : false,
                    'gift' => $order->send_as_gift ? ($order->gift_text ?? 'Yes') : '-',
                    'notes' => $item->notes ?? ($order->notes ?? '-'),
                    'customer_name' => $customer->name ?? '-',
                    'customer_phone' => $customer->phone ?? '-',
                    'customer_address' => $address,
                    'customer_country' => 'Oman', // Default or get from city if available
                    'sent_at' => $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d H:i') : '-',
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $formattedOrders->values()->all(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tailor orders to PDF
     */
    public function exportTailorOrdersPDF(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            
            if (!$tailorId) {
                return redirect()->back()->with('error', 'Please select a tailor');
            }

            $tailor = Tailor::findOrFail($tailorId);
            
            $items = SpecialOrderItem::with([
                'specialOrder.customer.city',
                'specialOrder.customer.area',
                'specialOrder.customer'
            ])
            ->where('tailor_id', $tailorId)
            ->whereNotNull('tailor_id')
            ->orderByRaw('COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at) DESC')
            ->get();

            $orders = $items->map(function($item) {
                $order = $item->specialOrder;
                $customer = $order->customer ?? null;
                
                $sizeInfo = [];
                if ($item->abaya_length) $sizeInfo[] = 'Length: ' . $item->abaya_length;
                if ($item->bust) $sizeInfo[] = 'Bust: ' . $item->bust;
                if ($item->sleeves_length) $sizeInfo[] = 'Sleeves: ' . $item->sleeves_length;
                $sizeText = !empty($sizeInfo) ? implode(', ', $sizeInfo) : '-';
                
                $address = '';
                if ($customer) {
                    $addressParts = [];
                    if ($customer->area) $addressParts[] = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                    if ($customer->city) $addressParts[] = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                    $address = implode(', ', array_filter($addressParts)) ?: '-';
                }
                
                return [
                    'order_no' => 'SO-' . str_pad($order->id ?? 0, 6, '0', STR_PAD_LEFT),
                    'dress_name' => $item->design_name ?? '-',
                    'dress_code' => $item->abaya_code ?? '-',
                    'size' => $sizeText,
                    'quantity' => $item->quantity ?? 1,
                    'buttons' => $item->buttons ? 'Yes' : 'No',
                    'gift' => $order->send_as_gift ? ($order->gift_text ?? 'Yes') : '-',
                    'notes' => $item->notes ?? ($order->notes ?? '-'),
                    'customer_name' => $customer->name ?? '-',
                    'customer_phone' => $customer->phone ?? '-',
                    'customer_address' => $address,
                    'customer_country' => 'Oman',
                ];
            });

            $html = view('tailors.tailor_orders_pdf', compact('orders', 'tailor'))->render();
            
            // Use dompdf if available, otherwise return HTML view
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                return $pdf->download('tailor_orders_' . $tailor->tailor_name . '_' . date('Y-m-d') . '.pdf');
            } else {
                // Fallback: return HTML view for printing
                return view('tailors.tailor_orders_pdf', compact('orders', 'tailor'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export tailor orders to Excel
     */
    public function exportTailorOrdersExcel(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            
            if (!$tailorId) {
                return redirect()->back()->with('error', 'Please select a tailor');
            }

            $tailor = Tailor::findOrFail($tailorId);
            
            $items = SpecialOrderItem::with([
                'specialOrder.customer.city',
                'specialOrder.customer.area',
                'specialOrder.customer'
            ])
            ->where('tailor_id', $tailorId)
            ->whereNotNull('tailor_id')
            ->orderByRaw('COALESCE(sent_to_tailor_at, received_from_tailor_at, created_at) DESC')
            ->get();

            $data = [];
            $data[] = ['Order No', 'Dress Name', 'Dress Code', 'Size', 'Quantity', 'Buttons', 'Gift', 'Notes', 'Customer Name', 'Phone', 'Address', 'Country'];
            
            foreach ($items as $item) {
                $order = $item->specialOrder;
                $customer = $order->customer ?? null;
                
                $sizeInfo = [];
                if ($item->abaya_length) $sizeInfo[] = 'Length: ' . $item->abaya_length;
                if ($item->bust) $sizeInfo[] = 'Bust: ' . $item->bust;
                if ($item->sleeves_length) $sizeInfo[] = 'Sleeves: ' . $item->sleeves_length;
                $sizeText = !empty($sizeInfo) ? implode(', ', $sizeInfo) : '-';
                
                $address = '';
                if ($customer) {
                    $addressParts = [];
                    if ($customer->area) $addressParts[] = $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '';
                    if ($customer->city) $addressParts[] = $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '';
                    $address = implode(', ', array_filter($addressParts)) ?: '-';
                }
                
                $data[] = [
                    'SO-' . str_pad($order->id ?? 0, 6, '0', STR_PAD_LEFT),
                    $item->design_name ?? '-',
                    $item->abaya_code ?? '-',
                    $sizeText,
                    $item->quantity ?? 1,
                    $item->buttons ? 'Yes' : 'No',
                    $order->send_as_gift ? ($order->gift_text ?? 'Yes') : '-',
                    $item->notes ?? ($order->notes ?? '-'),
                    $customer->name ?? '-',
                    $customer->phone ?? '-',
                    $address,
                    'Oman',
                ];
            }

            // Use PhpSpreadsheet if available
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                // Use fromArray - PhpSpreadsheet handles UTF-8 automatically
                $sheet->fromArray($data, null, 'A1');
                
                // Auto-size columns
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'tailor_orders_' . $tailor->tailor_name . '_' . date('Y-m-d') . '.xlsx';
                
                // UTF-8 encoding is handled by PhpSpreadsheet automatically
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
                header('Cache-Control: max-age=0');
                
                $writer->save('php://output');
                exit;
            } else {
                // Fallback: CSV export with UTF-8 BOM for Excel
                $filename = 'tailor_orders_' . $tailor->tailor_name . '_' . date('Y-m-d') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                // Add UTF-8 BOM for proper Excel display
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($data as $row) {
                    // Ensure UTF-8 encoding
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating Excel: ' . $e->getMessage());
        }
    }

}