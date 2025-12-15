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
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
        // Initialize sellable: if sending TO boutique, sellable = quantity (all items are sellable initially)
        // If pulling FROM boutique, sellable = 0 (no new sellable items)
        $transfer->sellable = (strpos($toChannel, 'boutique-') === 0) ? $totalQuantity : 0;
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

            // Check if transfer item already exists for this transfer with same code, color_id, size_id
            $transferItem = TransferItem::where('transfer_id', $transfer->id)
                ->where('abaya_code', $item['code'])
                ->where('color_id', $colorId)
                ->where('size_id', $sizeId)
                ->first();
            
            if ($transferItem) {
                // Update existing record: add to quantity and sellable
                $transferItem->quantity += (int)$item['qty'];
                // If transferring TO boutique, add to sellable; otherwise keep sellable as is
                if (strpos($toChannel, 'boutique-') === 0) {
                    $transferItem->sellable += (int)$item['qty'];
                }
                $transferItem->save();
            } else {
                // Create new transfer item
                $transferItem = new TransferItem();
                $transferItem->transfer_id = $transfer->id;
                $transferItem->stock_id = $stock->id;
                $transferItem->abaya_code = $item['code'];
                $transferItem->item_type = $itemType;
                $transferItem->color_id = $colorId;
                $transferItem->size_id = $sizeId;
                $transferItem->color_name = $colorName;
                $transferItem->size_name = $sizeName;
                // If transferring TO boutique, sellable = quantity (all items are sellable initially)
                // If pulling FROM boutique or transferring to channel/main, sellable = 0
                $transferItem->sellable = (strpos($toChannel, 'boutique-') === 0) ? (int)$item['qty'] : 0;
                $transferItem->quantity = (int)$item['qty'];
                $transferItem->from_location = $fromChannel;
                $transferItem->to_location = $toChannel;
                $transferItem->added_by = $user_name;
                $transferItem->user_id = $user_id;
                $transferItem->save();
            }

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

            // If transferring FROM boutique to another boutique/channel, decrease sellable in source boutique's transfer
            if (strpos($fromChannel, 'boutique-') === 0) {
                $this->updateSellableOnPull($fromChannel, $item['code'], $colorName, $sizeName, (int)$item['qty'], $colorId, $sizeId);
            }
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
 * Get settlement data for a boutique (sent, pulled, sellable)
 */
// public function get_settlement_data(Request $request)
// {
//     $locale = session('locale');
//     $boutiqueId = $request->input('boutique_id');
//     $dateFrom = $request->input('date_from');
//     $dateTo = $request->input('date_to');

//     if (!$boutiqueId || !$dateFrom || !$dateTo) {
//         return response()->json(['error' => 'Missing required parameters'], 400);
//     }

//     $boutiqueLocation = 'boutique-' . $boutiqueId;

//     // Get all transfers TO the boutique (SENT) within date range
//     $sentTransfers = Transfer::with('items')
//         ->where('to', $boutiqueLocation)
//         ->whereBetween('date', [$dateFrom, $dateTo])
//         ->get();

//     // Get all transfers FROM the boutique (PULLED) within date range
//     $pulledTransfers = Transfer::with('items')
//         ->where('from', $boutiqueLocation)
//         ->whereBetween('date', [$dateFrom, $dateTo])
//         ->get();

//     // Aggregate sent quantities and sellable by code, color, size
//     $sentData = [];
//     foreach ($sentTransfers as $transfer) {
//         $transferSellable = isset($transfer->sellable) ? (int)$transfer->sellable : 0;
//         $transferTotalQty = 0;
        
//         // Calculate total quantity for this transfer
//         foreach ($transfer->items as $item) {
//             $transferTotalQty += (int)$item->quantity;
//         }
        
//         foreach ($transfer->items as $item) {
//             $key = $item->abaya_code . '|' . ($item->size_name ?? '') . '|' . ($item->color_name ?? '');
            
//             if (!isset($sentData[$key])) {
//                 $sentData[$key] = [
//                     'code' => $item->abaya_code,
//                     'size' => $item->size_name,
//                     'color' => $item->color_name,
//                     'color_id' => $item->color_id ?? null,
//                     'size_id' => $item->size_id ?? null,
//                     'quantity' => 0,
//                     'sellable' => 0,
//                     'price' => 0,
//                     'color_code' => '#000000'
//                 ];
//             }
//             $sentData[$key]['quantity'] += $item->quantity;
            
//             // Distribute transfer's sellable proportionally to items
//             if ($transferTotalQty > 0 && $transferSellable > 0) {
//                 $itemProportion = (int)$item->quantity / $transferTotalQty;
//                 $sentData[$key]['sellable'] += (int)round($transferSellable * $itemProportion);
//             } else {
//                 // If no sellable set, use quantity as fallback
//                 $sentData[$key]['sellable'] += (int)$item->quantity;
//             }
            
//             // Keep the first color_id and size_id found (they should be consistent for same key)
//             if (!isset($sentData[$key]['color_id']) && isset($item->color_id)) {
//                 $sentData[$key]['color_id'] = $item->color_id;
//             }
//             if (!isset($sentData[$key]['size_id']) && isset($item->size_id)) {
//                 $sentData[$key]['size_id'] = $item->size_id;
//             }
//         }
//     }

//     // Aggregate pulled quantities by code, color, size
//     $pulledData = [];
//     foreach ($pulledTransfers as $transfer) {
//         foreach ($transfer->items as $item) {
//             $key = $item->abaya_code . '|' . ($item->size_name ?? '') . '|' . ($item->color_name ?? '');
            
//             if (!isset($pulledData[$key])) {
//                 $pulledData[$key] = [
//                     'code' => $item->abaya_code,
//                     'size' => $item->size_name,
//                     'color' => $item->color_name,
//                     'quantity' => 0
//                 ];
//             }
//             $pulledData[$key]['quantity'] += $item->quantity;
//         }
//     }

//     // Get stock prices and color codes
//     $codes = array_unique(array_column($sentData, 'code'));
//     if (empty($codes)) {
//         return response()->json([]);
//     }
    
//     $stocks = Stock::whereIn('abaya_code', $codes)->get()->keyBy('abaya_code');
    
//     // Get color codes from stock colors
//     $stockIds = $stocks->pluck('id')->toArray();
//     $stockColors = StockColor::with('color')
//         ->whereIn('stock_id', $stockIds)
//         ->get()
//         ->groupBy('stock_id');
    
//     $colorSizes = ColorSize::with('color', 'size')
//         ->whereIn('stock_id', $stockIds)
//         ->get()
//         ->groupBy('stock_id');

//     // Combine sent and pulled data, use sellable from transfers table
//     $result = [];
//     foreach ($sentData as $key => $sent) {
//         $pulledQty = isset($pulledData[$key]) ? $pulledData[$key]['quantity'] : 0;
//         // Use sellable from transfers table, subtract pulled quantity
//         $sellable = isset($sent['sellable']) ? max(0, $sent['sellable'] - $pulledQty) : max(0, $sent['quantity'] - $pulledQty);
        
//         $stock = $stocks->get($sent['code']);
//         $price = $stock ? (float)($stock->sales_price ?? 0) : 0;
        
//         // Get color code
//         $colorCode = '#000000';
//         if ($stock && $sent['color']) {
//             if ($sent['size']) {
//                 // Color-size combination
//                 $colorSize = $colorSizes->get($stock->id);
//                 if ($colorSize) {
//                     foreach ($colorSize as $cs) {
//                         if ($cs->color) {
//                             $colorMatch = ($cs->color->color_name_ar === $sent['color'] || 
//                                          $cs->color->color_name_en === $sent['color']);
//                             $sizeMatch = ($cs->size && 
//                                         ($cs->size->size_name_ar === $sent['size'] || 
//                                          $cs->size->size_name_en === $sent['size']));
//                             if ($colorMatch && $sizeMatch) {
//                                 $colorCode = $cs->color->color_code ?? '#000000';
//                                 break;
//                             }
//                         }
//                     }
//                 }
//             } else {
//                 // Color only
//                 $stockColor = $stockColors->get($stock->id);
//                 if ($stockColor) {
//                     foreach ($stockColor as $sc) {
//                         if ($sc->color && 
//                             ($sc->color->color_name_ar === $sent['color'] || 
//                              $sc->color->color_name_en === $sent['color'])) {
//                             $colorCode = $sc->color->color_code ?? '#000000';
//                             break;
//                         }
//                     }
//                 }
//             }
//         }

//         $result[] = [
//             'uid' => $key,
//             'code' => $sent['code'],
//             'color' => $sent['color'],
//             'color_id' => $sent['color_id'] ?? null,
//             'color_code' => $colorCode,
//             'size' => $sent['size'],
//             'size_id' => $sent['size_id'] ?? null,
//             'sent' => $sent['quantity'],
//             'pulled' => $pulledQty,
//             'sellable' => $sellable,
//             'price' => $price,
//             'sold' => 0,
//             'diff' => 0,
//             'total' => 0
//         ];
//     }

//     return response()->json($result);
// }
public function get_settlement_data(Request $request)
{
    $locale = session('locale');
    $boutiqueId = $request->input('boutique_id');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');

    if (!$boutiqueId || !$dateFrom || !$dateTo) {
        return response()->json(['error' => 'Missing required parameters'], 400);
    }

    $boutiqueLocation = 'boutique-' . $boutiqueId;

    // Fetch transfers
    $sentTransfers = Transfer::with('items')
        ->where('to', $boutiqueLocation)
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->get();

    $pulledTransfers = Transfer::with('items')
        ->where('from', $boutiqueLocation)
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->get();

    // -------------------------
    //   SENT DATA AGGREGATION
    // -------------------------
    $sentData = [];

    foreach ($sentTransfers as $transfer) {
        foreach ($transfer->items as $item) {

            $key = $item->abaya_code . '|' . ($item->size_name ?? '') . '|' . ($item->color_name ?? '');

            if (!isset($sentData[$key])) {
                $sentData[$key] = [
                    'code'       => $item->abaya_code,
                    'size'       => $item->size_name,
                    'color'      => $item->color_name,
                    'color_id'   => $item->color_id,
                    'size_id'    => $item->size_id,
                    'quantity'   => 0,
                    'sellable'   => 0,   // now from transfer_items table
                    'price'      => 0,
                    'color_code' => '#000000'
                ];
            }

            // Add quantity
            $sentData[$key]['quantity'] += (int)$item->quantity;

            // Use item-level sellable directly
            $sentData[$key]['sellable'] += (int)$item->sellable;
        }
    }

    // -------------------------
    //   PULLED DATA
    // -------------------------
    $pulledData = [];

    foreach ($pulledTransfers as $transfer) {
        foreach ($transfer->items as $item) {

            $key = $item->abaya_code . '|' . ($item->size_name ?? '') . '|' . ($item->color_name ?? '');

            if (!isset($pulledData[$key])) {
                $pulledData[$key] = [
                    'code'     => $item->abaya_code,
                    'size'     => $item->size_name,
                    'color'    => $item->color_name,
                    'quantity' => 0
                ];
            }

            $pulledData[$key]['quantity'] += (int)$item->quantity;
        }
    }

    // -------------------------
    //   STOCK + COLORS + SIZES
    // -------------------------
    $codes = array_unique(array_column($sentData, 'code'));

    if (empty($codes)) {
        return response()->json([]);
    }

    $stocks = Stock::whereIn('abaya_code', $codes)->get()->keyBy('abaya_code');

    $stockIds = $stocks->pluck('id')->toArray();

    $stockColors = StockColor::with('color')
        ->whereIn('stock_id', $stockIds)
        ->get()
        ->groupBy('stock_id');

    $colorSizes = ColorSize::with('color', 'size')
        ->whereIn('stock_id', $stockIds)
        ->get()
        ->groupBy('stock_id');

    // -------------------------
    //   FINAL RESULT BUILD
    // -------------------------
    $result = [];

    foreach ($sentData as $key => $sent) {

        $pulledQty = $pulledData[$key]['quantity'] ?? 0;

        // Sellable = item.sellable - pulled
        $sellable = max(0, $sent['sellable'] - $pulledQty);

        $stock = $stocks->get($sent['code']);
        $price = $stock ? (float)($stock->sales_price ?? 0) : 0;

        // Resolve color code
        $colorCode = '#000000';

        if ($stock && $sent['color']) {

            if ($sent['size']) {
                // color+size
                $combinations = $colorSizes->get($stock->id);
                if ($combinations) {
                    foreach ($combinations as $cs) {
                        if ($cs->color &&
                            ($cs->color->color_name_ar === $sent['color'] ||
                             $cs->color->color_name_en === $sent['color'])) {

                            if ($cs->size &&
                               ($cs->size->size_name_ar === $sent['size'] ||
                                $cs->size->size_name_en === $sent['size'])) {
                                $colorCode = $cs->color->color_code ?? '#000000';
                                break;
                            }
                        }
                    }
                }
            } else {
                // color only
                $cList = $stockColors->get($stock->id);
                if ($cList) {
                    foreach ($cList as $sc) {
                        if ($sc->color &&
                           ($sc->color->color_name_ar === $sent['color'] ||
                            $sc->color->color_name_en === $sent['color'])) {
                            $colorCode = $sc->color->color_code ?? '#000000';
                            break;
                        }
                    }
                }
            }
        }

        // Add final row
        $result[] = [
            'uid'        => $key,
            'code'       => $sent['code'],
            'color'      => $sent['color'],
            'color_id'   => $sent['color_id'],
            'color_code' => $colorCode,
            'size'       => $sent['size'],
            'size_id'    => $sent['size_id'],
            'sent'       => $sent['quantity'],
            'pulled'     => $pulledQty,
            'sellable'   => $sellable,   // FINAL VALUE
            'price'      => $price,
            'sold'       => 0,
            'diff'       => 0,
            'total'      => 0,
        ];
    }

    return response()->json($result);
}

/**
 * Get list of boutiques for settlement dropdown
 */
public function get_boutiques_list()
{
    $boutiques = Boutique::select('id', 'boutique_name')
        ->orderBy('boutique_name', 'asc')
        ->get()
        ->map(function($boutique) {
            return [
                'id' => $boutique->id,
                'name' => $boutique->boutique_name
            ];
        });

    return response()->json($boutiques);
}

/**
 * Get transfer details for a specific abaya code, color, size, boutique, and date range
 */
public function get_settlement_transfer_details(Request $request)
{
    $locale = session('locale');
    $boutiqueId = $request->input('boutique_id');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');
    $code = $request->input('code');
    $color = $request->input('color');
    $size = $request->input('size');

    if (!$boutiqueId || !$dateFrom || !$dateTo || !$code) {
        return response()->json(['error' => 'Missing required parameters'], 400);
    }

    $boutiqueLocation = 'boutique-' . $boutiqueId;

    // Helper function to get location name
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

    // Get all transfers TO the boutique (SENT) for this specific item
    $sentTransfers = Transfer::with('items')
        ->where('to', $boutiqueLocation)
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->get();

    // Get all transfers FROM the boutique (PULLED) for this specific item
    $pulledTransfers = Transfer::with('items')
        ->where('from', $boutiqueLocation)
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->get();

    $movements = [];

    // Process sent transfers
    foreach ($sentTransfers as $transfer) {
        foreach ($transfer->items as $item) {
            // Match by code, color, and size
            if ($item->abaya_code === $code) {
                $colorMatch = (!$color && !$item->color_name) || ($item->color_name === $color);
                $sizeMatch = (!$size && !$item->size_name) || ($item->size_name === $size);
                
                if ($colorMatch && $sizeMatch) {
                    $movements[] = [
                        'id' => $transfer->id . '-' . $item->id,
                        'date' => $transfer->date->format('Y-m-d'),
                        'type' => trans('messages.sent', [], $locale),
                        'from' => $getLocationName($transfer->from),
                        'to' => $getLocationName($transfer->to),
                        'qty' => $item->quantity,
                        'transfer_code' => $transfer->transfer_code,
                    ];
                }
            }
        }
    }

    // Process pulled transfers
    foreach ($pulledTransfers as $transfer) {
        foreach ($transfer->items as $item) {
            // Match by code, color, and size
            if ($item->abaya_code === $code) {
                $colorMatch = (!$color && !$item->color_name) || ($item->color_name === $color);
                $sizeMatch = (!$size && !$item->size_name) || ($item->size_name === $size);
                
                if ($colorMatch && $sizeMatch) {
                    $movements[] = [
                        'id' => $transfer->id . '-' . $item->id,
                        'date' => $transfer->date->format('Y-m-d'),
                        'type' => trans('messages.pulled', [], $locale),
                        'from' => $getLocationName($transfer->from),
                        'to' => $getLocationName($transfer->to),
                        'qty' => $item->quantity,
                        'transfer_code' => $transfer->transfer_code,
                    ];
                }
            }
        }
    }

    // Sort by date (newest first)
    usort($movements, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    return response()->json($movements);
}

/**
 * Save settlement record
 */
public function save_settlement(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name ?? 'system';
    $locale = session('locale');

    $boutiqueId = $request->input('boutique_id');
    $boutiqueName = $request->input('boutique_name');
    $month = $request->input('month');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');
    $numberOfItems = $request->input('number_of_items', 0);
    $totalSales = $request->input('total_sales', 0);
    $totalDifference = $request->input('total_difference', 0);
    $itemsData = $request->input('items_data', []);
    $notes = $request->input('notes', '');


    // Decode items_data if it's a JSON string
    if (is_string($itemsData)) {
        $itemsData = json_decode($itemsData, true) ?? [];
    foreach ($itemsData as $item) {
        
        $abayaCode = $item['code'];
        $colorId   = $item['color_id'];
        $sizeId    = $item['size_id'];

        TransferItem::where('abaya_code', $abayaCode)
    ->where('color_id', $colorId)
    ->where('size_id', $sizeId)
    ->update([
        'sellable' => max(0, ($item['sellable'] ?? 0) - ($item['sold'] ?? 0)),
    ]);
    }
    }
    
    // Generate settlement code
    $settlementCode = 'STL-' . $month . '-' . str_pad((Settlement::where('month', $month)->count() + 1), 2, '0', STR_PAD_LEFT);

    // Handle file upload
    $attachmentPath = null;
    $attachmentName = null;
    
    if ($request->hasFile('attachment')) {
        $folderPath = public_path('images/settlements');
        
        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
        }

        $file = $request->file('attachment');
        $attachmentName = $file->getClientOriginalName();
        $fileName = time() . '_' . $attachmentName;
        $file->move($folderPath, $fileName);
        $attachmentPath = 'images/settlements/' . $fileName;
    }

    // Save settlement
    $settlement = new Settlement();
    $settlement->settlement_code = $settlementCode;
    $settlement->boutique_id = $boutiqueId;
    $settlement->boutique_name = $boutiqueName;
    $settlement->month = $month;
    $settlement->date_from = $dateFrom;
    $settlement->date_to = $dateTo;
    $settlement->number_of_items = $numberOfItems;
    $settlement->total_sales = $totalSales;
    $settlement->total_difference = $totalDifference;
    $settlement->attachment_path = $attachmentPath;
    $settlement->attachment_name = $attachmentName;
    $settlement->notes = $notes;
    $settlement->items_data = json_encode($itemsData);
    $settlement->added_by = $user_name;
    $settlement->user_id = $user_id;
    $settlement->save();

    // Update sellable in transfers table: decrease by sold quantity
    $boutiqueLocation = 'boutique-' . $boutiqueId;
    
    // Get all transfers TO the boutique within the settlement date range (ordered by date, oldest first - FIFO)
    $transfers = Transfer::with('items')
        ->where('to', $boutiqueLocation)
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->orderBy('date', 'asc')
        ->orderBy('id', 'asc')
        ->get();
    
    // Process each sold item and decrease sellable
    foreach ($itemsData as $item) {
        $code = $item['code'] ?? '';
        $size = $item['size'] ?? '';
        $color = $item['color'] ?? '';
        $colorId = $item['color_id'] ?? null;
        $sizeId = $item['size_id'] ?? null;
        $soldQty = isset($item['sold']) ? (int)$item['sold'] : 0;
        
        if ($soldQty <= 0) continue;
        
        // Find matching transfers and decrease sellable (FIFO - oldest first)
        $remainingSold = $soldQty;
        
        foreach ($transfers as $transfer) {
            if ($remainingSold <= 0) break;
            
            $transferTotalQty = 0;
            $matchingItemQty = 0;
            
            // Calculate total quantity and matching item quantity for this transfer
            foreach ($transfer->items as $transferItem) {
                $transferTotalQty += (int)$transferItem->quantity;
                
                // Match by code, color, size, and IDs
                $codeMatch = $transferItem->abaya_code === $code;
                $sizeMatch = ($transferItem->size_name ?? '') === $size;
                $colorMatch = ($transferItem->color_name ?? '') === $color;
                
                if ($colorId !== null) {
                    $colorMatch = $colorMatch && ($transferItem->color_id == $colorId);
                }
                if ($sizeId !== null) {
                    $sizeMatch = $sizeMatch && ($transferItem->size_id == $sizeId);
                }
                
                if ($codeMatch && $sizeMatch && $colorMatch) {
                    $matchingItemQty += (int)$transferItem->quantity;
                }
            }
            
            // If we found matching items, decrease sellable proportionally
            if ($matchingItemQty > 0 && $transferTotalQty > 0) {
                $currentSellable = (int)($transfer->sellable ?? $transfer->quantity);
                
                // Calculate proportional sellable for matching items
                $proportionalSellable = ($currentSellable * $matchingItemQty) / $transferTotalQty;
                $decreaseAmount = min($remainingSold, (int)round($proportionalSellable));
                
                if ($decreaseAmount > 0) {
                    $transfer->sellable = max(0, $currentSellable - $decreaseAmount);
                    $transfer->save();
                    $remainingSold -= $decreaseAmount;
                }
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => trans('messages.settlement_saved_successfully', [], $locale),
        'settlement_code' => $settlementCode,
        'id' => $settlement->id
    ]);
}

/**
 * Get settlement history
 */
public function get_settlement_history(Request $request)
{
    $locale = session('locale');
    $search = $request->input('search', '');
    $month = $request->input('month', '');

    $query = Settlement::orderBy('month', 'desc')->orderBy('id', 'desc');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('settlement_code', 'like', '%' . $search . '%')
              ->orWhere('boutique_name', 'like', '%' . $search . '%');
        });
    }

    if ($month) {
        $query->where('month', $month);
    }

    $settlements = $query->get();

    $history = [];
    foreach ($settlements as $settlement) {
        $history[] = [
            'no' => $settlement->settlement_code,
            'month' => $settlement->month,
            'boutique' => $settlement->boutique_id,
            'boutique_name' => $settlement->boutique_name,
            'items' => $settlement->number_of_items,
            'amount' => (float)$settlement->total_sales,
            'diff' => $settlement->total_difference,
            'attachment_path' => $settlement->attachment_path,
            'attachment_name' => $settlement->attachment_name,
            'date_from' => $settlement->date_from ? $settlement->date_from->format('Y-m-d') : null,
            'date_to' => $settlement->date_to ? $settlement->date_to->format('Y-m-d') : null,
        ];
    }

    return response()->json($history);
}

/**
 * Get settlement details by code
 */
public function get_settlement_details(Request $request)
{
    $locale = session('locale');
    $settlementCode = $request->input('settlement_code');

    if (!$settlementCode) {
        return response()->json(['error' => 'Settlement code is required'], 400);
    }

    $settlement = Settlement::where('settlement_code', $settlementCode)->first();

    if (!$settlement) {
        return response()->json(['error' => 'Settlement not found'], 404);
    }

    // Parse items data
    $itemsData = [];
    if ($settlement->items_data) {
        $itemsData = is_string($settlement->items_data) 
            ? json_decode($settlement->items_data, true) 
            : $settlement->items_data;
    }

    $details = [
        'settlement_code' => $settlement->settlement_code,
        'boutique_name' => $settlement->boutique_name,
        'month' => $settlement->month,
        'date_from' => $settlement->date_from ? $settlement->date_from->format('Y-m-d') : null,
        'date_to' => $settlement->date_to ? $settlement->date_to->format('Y-m-d') : null,
        'number_of_items' => $settlement->number_of_items,
        'total_sales' => (float)$settlement->total_sales,
        'total_difference' => $settlement->total_difference,
        'attachment_path' => $settlement->attachment_path,
        'attachment_name' => $settlement->attachment_name,
        'notes' => $settlement->notes,
        'items_data' => $itemsData,
        'added_by' => $settlement->added_by,
        'created_at' => $settlement->created_at ? $settlement->created_at->format('Y-m-d H:i:s') : null,
    ];

    return response()->json($details);
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

/**
 * Update sellable in transfers when items are pulled/transferred from boutique
 */
private function updateSellableOnPull($boutiqueLocation, $code, $color, $size, $pulledQty, $colorId = null, $sizeId = null)
{
    // Find all transfers that sent this item TO the boutique (ordered by date, oldest first - FIFO)
    $sentTransfers = Transfer::with('items')
        ->where('to', $boutiqueLocation)
        ->orderBy('date', 'asc')
        ->orderBy('id', 'asc')
        ->get();
    
    $remainingPulledQty = $pulledQty;
    
    // Process transfers in FIFO order (oldest first)
    foreach ($sentTransfers as $transfer) {
        if ($remainingPulledQty <= 0) {
            break;
        }
        
        $totalTransferQty = 0;
        $matchingItemQty = 0;
        
        // Find matching items in this transfer
        foreach ($transfer->items as $item) {
            $codeMatch = $item->abaya_code === $code;
            $sizeMatch = ($item->size_name ?? '') === ($size ?? '');
            $colorMatch = ($item->color_name ?? '') === ($color ?? '');
            
            // Also match by IDs if provided
            if ($colorId !== null) {
                $colorMatch = $colorMatch && ($item->color_id == $colorId);
            }
            if ($sizeId !== null) {
                $sizeMatch = $sizeMatch && ($item->size_id == $sizeId);
            }
            
            if ($codeMatch && $sizeMatch && $colorMatch) {
                $matchingItemQty += (int)$item->quantity;
            }
            $totalTransferQty += (int)$item->quantity;
        }
        
        // If we found matching items, decrease sellable
        if ($matchingItemQty > 0) {
            $currentSellable = (int)($transfer->sellable ?? $transfer->quantity);
            
            // Calculate how much to decrease from this transfer
            // Use proportion of matching items to total transfer quantity
            if ($totalTransferQty > 0) {
                $proportionalSellable = ($currentSellable * $matchingItemQty) / $totalTransferQty;
                $decreaseAmount = min($remainingPulledQty, (int)round($proportionalSellable));
            } else {
                $decreaseAmount = min($remainingPulledQty, $currentSellable);
            }
            
            // Decrease sellable
            $newSellable = max(0, $currentSellable - $decreaseAmount);
            $transfer->sellable = $newSellable;
            $transfer->save();
            
            $remainingPulledQty -= $decreaseAmount;
        }
    }
}

}