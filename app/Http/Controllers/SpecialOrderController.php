<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Stock;
use App\Models\Tailor;
use App\Models\Customer;
use App\Models\SpecialOrder;
use Illuminate\Http\Request;
use App\Models\SpecialOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SpecialOrderController extends Controller
{
       public function index(){

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
            'customer.phone' => 'nullable|string|max:20',
            'customer.source' => 'required|string|in:whatsapp,walkin',
            'orders' => 'required|array|min:1',
            'orders.*.stock_id' => 'nullable|exists:stocks,id',
            'orders.*.quantity' => 'required|integer|min:1',
            'orders.*.price' => 'required|numeric|min:0',
        ]);

        // Create or find customer
        $phone = $request->input('customer.phone');
        
        if (!empty($phone)) {
            // If phone exists, find or create by phone
            $customer = Customer::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => $request->input('customer.name'),
                    'governorate' => $request->input('customer.governorate'),
                    'area' => $request->input('customer.area'),
                ]
            );

            // Update customer if phone exists but name/governorate/area changed
            if ($customer->wasRecentlyCreated === false) {
                $customer->name = $request->input('customer.name');
                $customer->governorate = $request->input('customer.governorate');
                $customer->area = $request->input('customer.area');
                $customer->save();
            }
        } else {
            // If no phone, create new customer
            $customer = new Customer();
            $customer->name = $request->input('customer.name');
            $customer->phone = null;
            $customer->governorate = $request->input('customer.governorate');
            $customer->area = $request->input('customer.area');
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
    return view('special_orders.view_special_order');
}

public function getOrdersList(Request $request)
{
    try {
        $orders = SpecialOrder::with(['customer', 'items.stock.images', 'items.tailor'])
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
                    'tailor_name' => $item->tailor ? $item->tailor->tailor_name : null,
                ];
            });

            // Get tailor info (if available from items notes or order notes)
            $tailor = 'N/A';
            if ($firstItem && $firstItem->notes) {
                $tailor = $firstItem->notes;
            } elseif ($order->notes) {
                $tailor = $order->notes;
            }

            // Calculate and update order status based on items' tailor_status
            $this->updateOrderStatusBasedOnItems($order);
            $calculatedStatus = $order->status;

            return [
                'id' => $order->id,
                'customer' => $order->customer->name ?? 'N/A',
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
        $paymentMethod = $request->input('payment_method', 'cash');

        $order = SpecialOrder::findOrFail($orderId);
        
        $newPaidAmount = $order->paid_amount + $amount;
        
        // Validate amount doesn't exceed total
        if ($newPaidAmount > $order->total_amount + 0.001) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds total amount'
            ], 422);
        }

        $order->paid_amount = $newPaidAmount;
        
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

        if ($stock && $stock->tailor_id) {

            // Convert JSON string to PHP array
            $tailorIds = json_decode($stock->tailor_id, true);

            // Ensure always an array
            if (!is_array($tailorIds)) {
                $tailorIds = [$tailorIds];
            }

    // Fetch all tailors
    $tailors = Tailor::whereIn('id', $tailorIds)->pluck('tailor_name')->toArray();

    // Join names into a single string
    $originalTailor = implode(', ', $tailors);
}
                return [
                    'rowId' => $item->id,
                    'orderId' => $order->id ?? 0,
                    'source' => $order->source ?? '',
                    'customer' => $order->customer->name ?? 'N/A',
                    'code' => $item->abaya_code ?? '',
                    'abayaName' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'image' => $image,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? true,
                    'notes' => $item->notes ?? '',
                    'date' => $order->created_at->format('Y-m-d'),
                    'originalTailor' => $originalTailor,
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
                if ($stock && $stock->tailor_id) {
                    $tailor = Tailor::find($stock->tailor_id);
                    $originalTailor = $tailor ? $tailor->tailor_name : '';
                }

                return [
                    'rowId' => $item->id,
                    'orderId' => $order->id ?? 0,
                    'source' => $order->source ?? '',
                    'customer' => $order->customer->name ?? 'N/A',
                    'code' => $item->abaya_code ?? '',
                    'abayaName' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                    'image' => $image,
                    'length' => $item->abaya_length,
                    'bust' => $item->bust,
                    'sleeves' => $item->sleeves_length,
                    'buttons' => $item->buttons ?? true,
                    'notes' => $item->notes ?? '',
                   'date' => $item->sent_to_tailor_at
    ? \Carbon\Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d')
    : \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
                    'originalTailor' => $originalTailor,
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
            
            if ($allNew) {
                $order->status = 'new';
            } elseif ($allReceived) {
                $order->status = 'ready';
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
                'received_from_tailor_at' => now()
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

}