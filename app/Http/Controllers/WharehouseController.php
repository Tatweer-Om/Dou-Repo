<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Stock;
use App\Models\Channel;
use App\Models\History;
use App\Models\Boutique;
use App\Models\Wharehouse;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\TransferItemHistory;
use App\Models\ChannelStock;
use App\Models\ColorSize;
use App\Models\StockSize;
use App\Models\StockColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WharehouseController extends Controller
{
    public function index(){

    return view ('wharehouse.wharehouse');

    }

  public function show_wharehouse()
{
    $sno = 0;
    $view_authwharehouse = Wharehouse::all();
    $json = [];

    if ($view_authwharehouse->count() > 0) {
        foreach ($view_authwharehouse as $value) {

            $wharehouse_name = '<a class="patient-info ps-0" href="javascript:void(0);">' . $value->wharehouse_name . '</a>';

            $modal = '
            <a href="javascript:void(0);" class="me-3 edit-staff" data-bs-toggle="modal" data-bs-target="#add_wharehouse_modal" onclick=edit("' . $value->id . '")>
                <i class="fa fa-pencil fs-18 text-success"></i>
            </a>
            <a href="javascript:void(0);" onclick=del("' . $value->id . '")>
                <i class="fa fa-trash fs-18 text-danger"></i>
            </a>';

            $add_data = Carbon::parse($value->created_at)->format('d-m-Y (h:i a)');

            $sno++;
            $json[] = [
                '<span class="patient-info ps-0">' . $sno . '</span>',
                '<span class="text-nowrap ms-2">' . $wharehouse_name . '</span>',
                '<span >' . $value->location . '</span>',
                '<span >' . $value->notes . '</span>',
                '<span >' . $value->added_by . '</span>',
                '<span >' . $add_data . '</span>',
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

// Add warehouse
public function add_wharehouse(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;

    $wharehouse = new Wharehouse();
    $wharehouse->wharehouse_name = $request->input('wharehouse_name');
    $wharehouse->location = $request->input('location');
    $wharehouse->notes = $request->input('notes');
    $wharehouse->added_by = $user_name;
    $wharehouse->user_id = $user_id;
    $wharehouse->save();

    return response()->json(['wharehouse_id' => $wharehouse->id]);
}

// Edit warehouse
public function edit_wharehouse(Request $request)
{
    $wharehouse_id = $request->input('id');
    $wharehouse = Wharehouse::find($wharehouse_id);

    if (!$wharehouse) {
        return response()->json(['error' => 'Warehouse not found'], 404);
    }

    $data = [
        'wharehouse_id' => $wharehouse->id,
        'wharehouse_name' => $wharehouse->wharehouse_name,
        'location' => $wharehouse->location,
        'notes' => $wharehouse->notes,
    ];

    return response()->json($data);
}

// Update warehouse
public function update_wharehouse(Request $request)
{
    $wharehouse_id = $request->input('wharehouse_id');
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;

    $wharehouse = Wharehouse::find($wharehouse_id);
    if (!$wharehouse) {
        return response()->json(['error' => 'Warehouse not found'], 404);
    }

    $previousData = $wharehouse->only(['wharehouse_name', 'location', 'notes', 'added_by', 'user_id', 'created_at']);

    $wharehouse->wharehouse_name = $request->input('wharehouse_name');
    $wharehouse->location = $request->input('location');
    $wharehouse->notes = $request->input('notes');
    $wharehouse->added_by = $user_name;
    $wharehouse->user_id = $user_id;
    $wharehouse->save();

    // History logging
    $history = new History();
    $history->user_id = $user_id;
    $history->table_name = 'wharehousees';
    $history->function = 'update';
    $history->function_status = 1;
    $history->wharehouse_id = $wharehouse_id;
    $history->record_id = $wharehouse->id;
    $history->previous_data = json_encode($previousData);
    $history->updated_data = json_encode($wharehouse->only(['wharehouse_name', 'location', 'notes', 'added_by', 'user_id']));
    $history->added_by = $user_name;
    $history->save();

    return response()->json(['message' => 'Warehouse updated successfully']);
}

// Delete warehouse
public function delete_wharehouse(Request $request)
{
    $wharehouse_id = $request->input('id');
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;

    $wharehouse = Wharehouse::find($wharehouse_id);
    if (!$wharehouse) {
        return response()->json(['error' => 'Warehouse not found'], 404);
    }

    $previousData = $wharehouse->only(['wharehouse_name', 'location', 'notes', 'added_by', 'user_id', 'created_at']);

    // History logging
    $history = new History();
    $history->user_id = $user_id;
    $history->table_name = 'wharehousees';
    $history->function = 'delete';
    $history->function_status = 2;
    $history->wharehouse_id = $wharehouse_id;
    $history->record_id = $wharehouse->id;
    $history->previous_data = json_encode($previousData);
    $history->added_by = $user_name;
    $history->save();

    $wharehouse->delete();

    return response()->json(['message' => 'Warehouse deleted successfully']);
}

public function manage_quantity()
{
    $locale = session('locale'); 

    $total_stock = Stock::sum('quantity');


    // Get all boutiques
    $boutiques = Boutique::select('id', 'boutique_name')
        ->get()
        ->map(function ($item) {
            return [
                'id' => (int)$item->id,
                'type' => 'boutique',
                'display_name' => $item->boutique_name
            ];
        })
        ->toArray();

    // Get channels
    $channels = Channel::select('id', 'channel_name_en', 'channel_name_ar')
        ->get()
        ->map(function ($item) use ($locale) {
            return [
                'id' => (int)$item->id,
                'type' => 'channel',
                'display_name' => $locale == 'ar'
                    ? $item->channel_name_ar
                    : $item->channel_name_en
            ];
        })
        ->toArray();

    // Merge both arrays
    $items = array_merge($boutiques, $channels);
    
    return view('wharehouse.manage_quantity', compact('total_stock', 'items'));
}

public function get_inventory(Request $request)
{
    $locale = session('locale');
    $inventory = [];
    
    // Get all stocks with their relationships
    $stocks = Stock::with(['sizes.size', 'colors.color', 'colorSizes.color', 'colorSizes.size'])
        ->get();
    
    foreach ($stocks as $stock) {
        $code = $stock->abaya_code;
        $name = $stock->design_name;
        $mode = $stock->mode; // 'size', 'color', or 'color_size'
        
        if ($mode === 'size') {
            // Get all sizes for this stock
            foreach ($stock->sizes as $stockSize) {
                $size = $stockSize->size;
                $sizeName = $size ? ($locale == 'ar' ? $size->size_name_ar : $size->size_name_en) : null;
                $uid = $code . '|' . ($sizeName ? $sizeName : '') . '|';
                
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'size',
                    'size' => $sizeName,
                    'color' => null,
                    'color_code' => '#000000',
                    'available' => (int)$stockSize->qty
                ];
            }
        } elseif ($mode === 'color') {
            // Get all colors for this stock
            foreach ($stock->colors as $stockColor) {
                $color = $stockColor->color;
                $colorName = $color ? ($locale == 'ar' ? $color->color_name_ar : $color->color_name_en) : null;
                $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
                $uid = $code . '||' . ($colorName ? $colorName : '');
                
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'color',
                    'size' => null,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => (int)$stockColor->qty
                ];
            }
        } elseif ($mode === 'color_size') {
            // Get all color+size combinations for this stock
            foreach ($stock->colorSizes as $colorSize) {
                $color = $colorSize->color;
                $size = $colorSize->size;
                $colorName = $color ? ($locale == 'ar' ? $color->color_name_ar : $color->color_name_en) : null;
                $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
                $sizeName = $size ? ($locale == 'ar' ? $size->size_name_ar : $size->size_name_en) : null;
                $uid = $code . '|' . ($sizeName ? $sizeName : '') . '|' . ($colorName ? $colorName : '');
                
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'color_size',
                    'size' => $sizeName,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => (int)$colorSize->qty
                ];
            }
        }
    }
    
    return response()->json($inventory);
}

/**
 * Get inventory from a specific channel/boutique
 */
public function get_channel_inventory(Request $request)
{
    $locale = session('locale');
    $channelId = $request->input('channel_id');
    
    if (!$channelId) {
        return response()->json([]);
    }

    // Parse channel ID
    $channelType = strpos($channelId, 'boutique-') === 0 ? 'boutique' : 'channel';
    $locationId = (int)explode('-', $channelId)[1];

    // Get stocks from channel_stocks table
    $channelStocks = ChannelStock::where('location_type', $channelType)
        ->where('location_id', $locationId)
        ->with(['stock'])
        ->get();

    $inventory = [];
    
    foreach ($channelStocks as $channelStock) {
        $stock = $channelStock->stock;
        if (!$stock) continue;

        $code = $channelStock->abaya_code;
        $name = $stock->design_name ?? '';
        $itemType = $channelStock->item_type ?? 'color_size';
        
        // Create UID based on item type
        $sizeName = $channelStock->size_name;
        $colorName = $channelStock->color_name;
        
        if ($itemType === 'color_size') {
            $uid = $code . '|' . ($sizeName ? $sizeName : '') . '|' . ($colorName ? $colorName : '');
        } elseif ($itemType === 'color') {
            $uid = $code . '||' . ($colorName ? $colorName : '');
        } else {
            $uid = $code . '|' . ($sizeName ? $sizeName : '') . '|';
        }

        // Get color code
        $colorCode = '#000000';
        if ($channelStock->color_id) {
            $color = \App\Models\Color::find($channelStock->color_id);
            $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
        }

        $inventory[] = [
            'uid' => $uid,
            'code' => $code,
            'name' => $name,
            'type' => $itemType,
            'size' => $sizeName,
            'color' => $colorName,
            'color_code' => $colorCode,
            'available' => (int)$channelStock->quantity
        ];
    }
    
    return response()->json($inventory);
}

public function settlement(){
    return view ('wharehouse.settlement');
}

/**
 * Execute transfer operation
 */
public function execute_transfer(Request $request)
{
    try {
        DB::beginTransaction();

        $user_id = Auth::id();
        $user_name = Auth::user()->user_name ?? 'System';

        $mode = $request->input('mode');
        $fromChannel = $request->input('from');
        $toChannel = $request->input('to');
        $transferDate = $request->input('transfer_date') ?: date('Y-m-d');
        $transferNote = $request->input('note', '');
        $basket = $request->input('basket', []);

        if (empty($basket)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Basket is empty'
            ], 400);
        }

        // Generate transfer code
        $transferCode = 'TR-' . date('Ymd') . '-' . str_pad(Transfer::count() + 1, 3, '0', STR_PAD_LEFT);

        // Determine transfer type and channel type
        $transferType = 'transfer';
        $channelType = 'channel';
        if (strpos($toChannel, 'boutique-') === 0) {
            $channelType = 'boutique';
        }

        // Parse from/to locations
        $fromType = $fromChannel === 'main' ? 'main' : (strpos($fromChannel, 'boutique-') === 0 ? 'boutique' : 'channel');
        $toType = $toChannel === 'main' ? 'main' : (strpos($toChannel, 'boutique-') === 0 ? 'boutique' : 'channel');
        $fromId = $fromChannel === 'main' ? null : (int)explode('-', $fromChannel)[1];
        $toId = $toChannel === 'main' ? null : (int)explode('-', $toChannel)[1];

        // Calculate total quantity
        $totalQuantity = array_sum(array_column($basket, 'qty'));

        // Create transfer record
        $transfer = new Transfer();
        $transfer->transfer_code = $transferCode;
        $transfer->transfer_type = $transferType;
        $transfer->channel_type = $channelType;
        $transfer->date = $transferDate;
        $transfer->quantity = $totalQuantity;
        $transfer->from = $fromChannel;
        $transfer->to = $toChannel;
        $transfer->boutique_id = $toType === 'boutique' ? $toId : null;
        $transfer->channel_id = $toType === 'channel' ? $toId : null;
        $transfer->notes = $transferNote;
        $transfer->added_by = $user_name;
        $transfer->user_id = $user_id;
        $transfer->save();

        // Process each item in basket
        foreach ($basket as $item) {
            $stock = Stock::where('abaya_code', $item['code'])->first();
            if (!$stock) continue;

            $itemType = $item['type'] ?? 'color_size';
            $colorId = null;
            $sizeId = null;
            $colorName = $item['color'] ?? null;
            $sizeName = $item['size'] ?? null;

            // Find color_id and size_id if needed
            if ($colorName) {
                $color = \App\Models\Color::where(function($q) use ($colorName) {
                    $q->where('color_name_ar', $colorName)
                      ->orWhere('color_name_en', $colorName);
                })->first();
                $colorId = $color ? $color->id : null;
            }

            if ($sizeName) {
                $size = \App\Models\Size::where(function($q) use ($sizeName) {
                    $q->where('size_name_ar', $sizeName)
                      ->orWhere('size_name_en', $sizeName);
                })->first();
                $sizeId = $size ? $size->id : null;
            }

            // Create transfer item
            $transferItem = new TransferItem();
            $transferItem->transfer_id = $transfer->id;
            $transferItem->stock_id = $stock->id;
            $transferItem->abaya_code = $item['code'];
            $transferItem->item_type = $itemType;
            $transferItem->color_id = $colorId;
            $transferItem->size_id = $sizeId;
            $transferItem->color_name = $colorName;
            $transferItem->size_name = $sizeName;
            $transferItem->quantity = (int)$item['qty'];
            $transferItem->from_location = $fromChannel;
            $transferItem->to_location = $toChannel;
            $transferItem->added_by = $user_name;
            $transferItem->user_id = $user_id;
            $transferItem->save();

            // Update channel stocks
            $this->updateChannelStock($stock->id, $item['code'], $itemType, $colorId, $sizeId, $colorName, $sizeName, $fromChannel, $toChannel, (int)$item['qty']);

            // Update main warehouse quantities (if transferring from/to main)
            if ($fromChannel === 'main') {
                $this->decreaseMainWarehouseStock($stock->id, $itemType, $colorId, $sizeId, (int)$item['qty']);
            }
            if ($toChannel === 'main') {
                $this->increaseMainWarehouseStock($stock->id, $itemType, $colorId, $sizeId, (int)$item['qty']);
            }

            // Create transfer item history
            $history = new TransferItemHistory();
            $history->transfer_id = $transfer->id;
            $history->item_code = $item['code'];
            $history->item_size = $sizeName;
            $history->item_color = $colorName;
            $history->item_previous_quantity = $item['available'] ?? 0;
            $history->quantity_action = $fromChannel === 'main' ? 'pulled' : 'transferred';
            $history->item_new_quantity = ($item['available'] ?? 0) - (int)$item['qty'];
            $history->quantity_pulled = $fromChannel === 'main' ? (int)$item['qty'] : 0;
            $history->quantity_pushed = $toChannel !== 'main' ? (int)$item['qty'] : 0;
            $history->added_by = $user_name;
            $history->user_id = $user_id;
            $history->save();
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Transfer executed successfully',
            'transfer_code' => $transferCode
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Error executing transfer: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Update channel stock quantities
 */
private function updateChannelStock($stockId, $abayaCode, $itemType, $colorId, $sizeId, $colorName, $sizeName, $fromLocation, $toLocation, $quantity)
{
    // Decrease from source location
    if ($fromLocation !== 'main') {
        $fromType = strpos($fromLocation, 'boutique-') === 0 ? 'boutique' : 'channel';
        $fromId = (int)explode('-', $fromLocation)[1];
        
        $channelStock = ChannelStock::where('stock_id', $stockId)
            ->where('location_type', $fromType)
            ->where('location_id', $fromId)
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->first();

        if ($channelStock) {
            $channelStock->quantity = max(0, $channelStock->quantity - $quantity);
            $channelStock->save();
        }
    }

    // Increase in destination location
    if ($toLocation !== 'main') {
        $toType = strpos($toLocation, 'boutique-') === 0 ? 'boutique' : 'channel';
        $toId = (int)explode('-', $toLocation)[1];
        
        $channelStock = ChannelStock::firstOrNew([
            'stock_id' => $stockId,
            'location_type' => $toType,
            'location_id' => $toId,
            'color_id' => $colorId,
            'size_id' => $sizeId,
        ]);

        $channelStock->abaya_code = $abayaCode;
        $channelStock->item_type = $itemType;
        $channelStock->color_name = $colorName;
        $channelStock->size_name = $sizeName;
        $channelStock->quantity = ($channelStock->quantity ?? 0) + $quantity;
        $channelStock->save();
    }
}

/**
 * Decrease main warehouse stock
 */
private function decreaseMainWarehouseStock($stockId, $itemType, $colorId, $sizeId, $quantity)
{
    if ($itemType === 'color_size' && $colorId && $sizeId) {
        $colorSize = ColorSize::where('stock_id', $stockId)
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->first();
        if ($colorSize) {
            $colorSize->qty = max(0, $colorSize->qty - $quantity);
            $colorSize->save();
        }
    } elseif ($itemType === 'color' && $colorId) {
        $stockColor = StockColor::where('stock_id', $stockId)
            ->where('color_id', $colorId)
            ->first();
        if ($stockColor) {
            $stockColor->qty = max(0, $stockColor->qty - $quantity);
            $stockColor->save();
        }
    } elseif ($itemType === 'size' && $sizeId) {
        $stockSize = StockSize::where('stock_id', $stockId)
            ->where('size_id', $sizeId)
            ->first();
        if ($stockSize) {
            $stockSize->qty = max(0, $stockSize->qty - $quantity);
            $stockSize->save();
        }
    }
}

/**
 * Increase main warehouse stock
 */
private function increaseMainWarehouseStock($stockId, $itemType, $colorId, $sizeId, $quantity)
{
    if ($itemType === 'color_size' && $colorId && $sizeId) {
        $colorSize = ColorSize::firstOrNew([
            'stock_id' => $stockId,
            'color_id' => $colorId,
            'size_id' => $sizeId,
        ]);
        $colorSize->qty = ($colorSize->qty ?? 0) + $quantity;
        $colorSize->save();
    } elseif ($itemType === 'color' && $colorId) {
        $stockColor = StockColor::firstOrNew([
            'stock_id' => $stockId,
            'color_id' => $colorId,
        ]);
        $stockColor->qty = ($stockColor->qty ?? 0) + $quantity;
        $stockColor->save();
    } elseif ($itemType === 'size' && $sizeId) {
        $stockSize = StockSize::firstOrNew([
            'stock_id' => $stockId,
            'size_id' => $sizeId,
        ]);
        $stockSize->qty = ($stockSize->qty ?? 0) + $quantity;
        $stockSize->save();
    }
}

/**
 * Get transfer history
 */
public function get_transfer_history(Request $request)
{
    $locale = session('locale');
    $search = $request->input('search', '');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');

    $query = Transfer::with('items')->orderBy('date', 'desc')->orderBy('id', 'desc');

    if ($search) {
        $query->where('transfer_code', 'like', '%' . $search . '%');
    }

    if ($dateFrom) {
        $query->where('date', '>=', $dateFrom);
    }

    if ($dateTo) {
        $query->where('date', '<=', $dateTo);
    }

    $transfers = $query->get();

    $history = [];
    foreach ($transfers as $transfer) {
        $items = [];
        foreach ($transfer->items as $item) {
            $items[] = [
                'code' => $item->abaya_code,
                'color' => $item->color_name,
                'size' => $item->size_name,
                'qty' => $item->quantity,
            ];
        }

        $history[] = [
            'no' => $transfer->transfer_code,
            'date' => $transfer->date->format('Y-m-d'),
            'from' => $transfer->from,
            'to' => $transfer->to,
            'total' => $transfer->quantity,
            'items' => $items,
            'note' => $transfer->notes ?? '',
        ];
    }

    return response()->json($history);
}

/**
 * Export transfer history to Excel
 */
public function export_transfers_excel(Request $request)
{
    $locale = session('locale');
    $search = $request->input('search', '');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');

    $query = Transfer::with('items')->orderBy('date', 'desc')->orderBy('id', 'desc');

    if ($search) {
        $query->where('transfer_code', 'like', '%' . $search . '%');
    }

    if ($dateFrom) {
        $query->where('date', '>=', $dateFrom);
    }

    if ($dateTo) {
        $query->where('date', '<=', $dateTo);
    }

    $transfers = $query->get();

    // Helper function to get channel/boutique name
    $getLocationName = function($locationId) use ($locale) {
        if ($locationId === 'main') {
            return trans('messages.main_warehouse', [], $locale);
        }
        
        if (strpos($locationId, 'boutique-') === 0) {
            $id = (int)explode('-', $locationId)[1];
            $boutique = Boutique::find($id);
            return $boutique ? $boutique->boutique_name : $locationId;
        }
        
        if (strpos($locationId, 'channel-') === 0) {
            $id = (int)explode('-', $locationId)[1];
            $channel = Channel::find($id);
            if ($channel) {
                return $locale == 'ar' ? $channel->channel_name_ar : $channel->channel_name_en;
            }
        }
        
        return $locationId;
    };

    // Prepare Excel data
    $filename = 'transfers_' . date('Y-m-d_His') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    // Add BOM for UTF-8 Excel compatibility
    $output = "\xEF\xBB\xBF";

    // Create CSV content
    $output .= trans('messages.operation_number', [], $locale) . ',';
    $output .= trans('messages.date', [], $locale) . ',';
    $output .= trans('messages.from', [], $locale) . ',';
    $output .= trans('messages.to', [], $locale) . ',';
    $output .= trans('messages.number_of_items', [], $locale) . ',';
    $output .= trans('messages.total_quantity', [], $locale) . ',';
    $output .= trans('messages.items_details', [], $locale) . ',';
    $output .= trans('messages.operation_notes', [], $locale) . "\n";

    foreach ($transfers as $transfer) {
        $itemsDetails = [];
        foreach ($transfer->items as $item) {
            $itemStr = $item->abaya_code;
            if ($item->size_name) {
                $itemStr .= ' - ' . trans('messages.size', [], $locale) . ': ' . $item->size_name;
            }
            if ($item->color_name) {
                $itemStr .= ' - ' . trans('messages.color', [], $locale) . ': ' . $item->color_name;
            }
            $itemStr .= ' (' . $item->quantity . ' ' . trans('messages.pieces', [], $locale) . ')';
            $itemsDetails[] = $itemStr;
        }

        $fromName = $getLocationName($transfer->from);
        $toName = $getLocationName($transfer->to);
        $itemsText = implode('; ', $itemsDetails);
        $notes = str_replace(["\r\n", "\n", "\r"], ' ', $transfer->notes ?? '');

        $output .= '"' . str_replace('"', '""', $transfer->transfer_code) . '",';
        $output .= '"' . str_replace('"', '""', $transfer->date->format('Y-m-d')) . '",';
        $output .= '"' . str_replace('"', '""', $fromName) . '",';
        $output .= '"' . str_replace('"', '""', $toName) . '",';
        $output .= '"' . str_replace('"', '""', count($transfer->items)) . '",';
        $output .= '"' . str_replace('"', '""', $transfer->quantity) . '",';
        $output .= '"' . str_replace('"', '""', $itemsText) . '",';
        $output .= '"' . str_replace('"', '""', $notes) . '"';
        $output .= "\n";
    }

    return response($output, 200, $headers);
}

/**
 * Get channel stocks (quantities in channels/boutiques)
 */
public function get_channel_stocks(Request $request)
{
    $channelId = $request->input('channel_id');
    if (!$channelId) {
        return response()->json([]);
    }

    $channelType = strpos($channelId, 'boutique-') === 0 ? 'boutique' : 'channel';
    $locationId = (int)explode('-', $channelId)[1];

    $stocks = ChannelStock::where('location_type', $channelType)
        ->where('location_id', $locationId)
        ->with('stock')
        ->get();

    $result = [];
    foreach ($stocks as $stock) {
        $result[] = [
            'code' => $stock->abaya_code,
            'color' => $stock->color_name,
            'size' => $stock->size_name,
            'qty' => $stock->quantity,
        ];
    }

    return response()->json($result);
}

/**
 * Get warehouse statistics
 */
public function get_stats()
{
    // Main warehouse total (sum of all color_sizes, stock_colors, stock_sizes)
    $mainTotal = ColorSize::sum('qty') + StockColor::sum('qty') + StockSize::sum('qty');

    // Website (channels) total
    $websiteTotal = ChannelStock::where('location_type', 'channel')->sum('quantity');

    // POS (assuming it's a channel type, adjust if different)
    // For now, POS is included in channels total
    $posTotal = 0; // You can adjust this based on your POS identification

    // Boutiques total
    $boutiquesTotal = ChannelStock::where('location_type', 'boutique')->sum('quantity');

    return response()->json([
        'main' => (int)$mainTotal,
        'website' => (int)$websiteTotal,
        'pos' => (int)$posTotal,
        'boutiques' => (int)$boutiquesTotal,
    ]);
}

}