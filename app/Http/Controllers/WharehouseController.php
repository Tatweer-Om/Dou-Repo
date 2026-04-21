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
use App\Models\Customer;
use App\Models\SpecialOrder;
use App\Models\SpecialOrderItem;
use App\Models\PosOrders;
use App\Models\PosOrdersDetail;
use App\Models\PosPayment;
use App\Models\StockAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;   // 👈 add this


class WharehouseController extends Controller
{
    public function index(){
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(6, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

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
    if (!Auth::check()) {
        return redirect()->route('login_page')->with('error', 'Please login first');
    }

    $permissions = Auth::user()->permissions ?? [];

    if (!in_array(6, $permissions)) {
        return redirect()->route('login_page')->with('error', 'Permission denied');
    }

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
    $full = $request->boolean('full');
    $perPage = (int) $request->input('per_page', 70);
    $perPage = min(100, max(1, $perPage));
    $page = max(1, (int) $request->input('page', 1));

    // Full list (e.g. for warehouseInventory when transferring from channel to main)
    $skipWebsiteQty = $request->boolean('skip_website_qty');
    if ($full) {
        $inventory = $this->buildFullInventory($locale, $skipWebsiteQty);
        return response()->json($inventory);
    }

    // Search: filter by code or name across all inventory (server-side so pagination applies to filtered set)
    $search = trim((string) $request->input('search', $request->input('q', '')));
    $whereClause = '';
    $bindings = [];
    if ($search !== '') {
        $whereClause = ' WHERE (u.abaya_code LIKE ? OR u.design_name LIKE ?)';
        $like = '%' . $search . '%';
        $bindings = [$like, $like];
    }

    // DB-level pagination: union of size/color/color_size rows, then limit/offset
    $unionSql = "
        ( SELECT s.id as stock_id, s.abaya_code, s.design_name, s.barcode, 'size' as mode,
          sz.size_name_ar, sz.size_name_en, NULL as color_name_ar, NULL as color_name_en, NULL as color_code,
          ss.qty, NULL as color_id, ss.size_id
          FROM stocks s
          INNER JOIN stock_sizes ss ON ss.stock_id = s.id AND ss.qty > 0
          INNER JOIN sizes sz ON sz.id = ss.size_id
          WHERE s.mode = 'size' )
        UNION ALL
        ( SELECT s.id, s.abaya_code, s.design_name, s.barcode, 'color',
          NULL, NULL, c.color_name_ar, c.color_name_en, COALESCE(c.color_code,'#000000'),
          sc.qty, sc.color_id, NULL
          FROM stocks s
          INNER JOIN stock_colors sc ON sc.stock_id = s.id AND sc.qty > 0
          INNER JOIN colors c ON c.id = sc.color_id
          WHERE s.mode = 'color' )
        UNION ALL
        ( SELECT s.id, s.abaya_code, s.design_name, s.barcode, 'color_size',
          sz.size_name_ar, sz.size_name_en, c.color_name_ar, c.color_name_en, COALESCE(c.color_code,'#000000'),
          cs.qty, cs.color_id, cs.size_id
          FROM stocks s
          INNER JOIN color_sizes cs ON cs.stock_id = s.id AND cs.qty > 0
          INNER JOIN sizes sz ON sz.id = cs.size_id
          INNER JOIN colors c ON c.id = cs.color_id
          WHERE s.mode = 'color_size' )
    ";
    $countSql = "SELECT COUNT(*) as total FROM ({$unionSql}) as u" . $whereClause;
    $countResult = DB::selectOne($countSql, $bindings);
    $total = (int) ($countResult->total ?? 0);
    $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
    $offset = ($page - 1) * $perPage;

    $limit = (int) $perPage;
    $offsetInt = (int) $offset;
    $orderSql = "SELECT * FROM ({$unionSql}) as u" . $whereClause . " ORDER BY u.abaya_code, u.mode, u.size_name_en, u.color_name_en LIMIT {$limit} OFFSET {$offsetInt}";
    $rows = DB::select($orderSql, $bindings);

    $inventory = [];
    foreach ($rows as $row) {
        $code = $row->abaya_code;
        $name = $row->design_name;
        $mode = $row->mode;
        $sizeName = $locale == 'ar' ? $row->size_name_ar : $row->size_name_en;
        $colorName = $locale == 'ar' ? $row->color_name_ar : $row->color_name_en;
        $colorCode = $row->color_code ?? '#000000';
        if ($mode === 'size') {
            $uid = $code . '|' . ($sizeName ?: '') . '|';
            $inventory[] = [
                'uid' => $uid,
                'code' => $code,
                'name' => $name,
                'type' => 'size',
                'size' => $sizeName,
                'color' => null,
                'color_code' => '#000000',
                'available' => (int) $row->qty,
                'webiste_available' => 0
            ];
        } elseif ($mode === 'color') {
            $uid = $code . '||' . ($colorName ?: '');
            $inventory[] = [
                'uid' => $uid,
                'code' => $code,
                'name' => $name,
                'type' => 'color',
                'size' => null,
                'color' => $colorName,
                'color_code' => $colorCode,
                'available' => (int) $row->qty,
                'webiste_available' => 0
            ];
        } else {
            $uid = $code . '|' . ($sizeName ?: '') . '|' . ($colorName ?: '');
            $webisteQty = 0;
            $skipWebsiteQty = $request->boolean('skip_website_qty');
            if (!$skipWebsiteQty && $row->stock_id && $row->color_id && $row->size_id) {
                $barcode = DB::table('stocks')->where('id', $row->stock_id)->value('barcode');
                $webisteQty = (int) fetchWebsiteCurrentQty($row->stock_id, $barcode, $row->color_id, $row->size_id);
            }
            $inventory[] = [
                'uid' => $uid,
                'code' => $code,
                'name' => $name,
                'type' => 'color_size',
                'size' => $sizeName,
                'color' => $colorName,
                'color_code' => $colorCode,
                'available' => (int) $row->qty,
                'webiste_available' => $webisteQty
            ];
        }
    }

    return response()->json([
        'data' => $inventory,
        'total' => $total,
        'current_page' => $page,
        'last_page' => $lastPage,
        'per_page' => $perPage
    ]);
}

/**
 * Build full inventory list (used when full=1 for warehouseInventory).
 * When $skipWebsiteQty is true, skips external website API calls so response is fast (light form).
 */
private function buildFullInventory($locale, $skipWebsiteQty = false)
{
    $inventory = [];
    $stocks = Stock::with(['sizes.size', 'colors.color', 'colorSizes.color', 'colorSizes.size'])
        ->get();

    foreach ($stocks as $stock) {
        $code = $stock->abaya_code;
        $name = $stock->design_name;
        $mode = $stock->mode;
        $barcode = $stock->barcode;

        if ($mode === 'size') {
            foreach ($stock->sizes as $stockSize) {
                if ((int) $stockSize->qty <= 0) continue;
                $size = $stockSize->size;
                $sizeName = $size ? ($locale == 'ar' ? $size->size_name_ar : $size->size_name_en) : null;
                $uid = $code . '|' . ($sizeName ?: '') . '|';
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'size',
                    'size' => $sizeName,
                    'color' => null,
                    'color_code' => '#000000',
                    'available' => (int) $stockSize->qty,
                    'webiste_available' => 0
                ];
            }
        } elseif ($mode === 'color') {
            foreach ($stock->colors as $stockColor) {
                if ((int) $stockColor->qty <= 0) continue;
                $color = $stockColor->color;
                $colorName = $color ? ($locale == 'ar' ? $color->color_name_ar : $color->color_name_en) : null;
                $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
                $uid = $code . '||' . ($colorName ?: '');
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'color',
                    'size' => null,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => (int) $stockColor->qty,
                    'webiste_available' => 0
                ];
            }
        } elseif ($mode === 'color_size') {
            foreach ($stock->colorSizes as $colorSize) {
                if ((int) $colorSize->qty <= 0) continue;
                $webisteQty = 0;
                if (!$skipWebsiteQty) {
                    $webisteQty = (int) fetchWebsiteCurrentQty($colorSize->stock_id, $barcode, $colorSize->color_id, $colorSize->size_id);
                }
                $color = $colorSize->color;
                $size = $colorSize->size;
                $colorName = $color ? ($locale == 'ar' ? $color->color_name_ar : $color->color_name_en) : null;
                $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
                $sizeName = $size ? ($locale == 'ar' ? $size->size_name_ar : $size->size_name_en) : null;
                $uid = $code . '|' . ($sizeName ?: '') . '|' . ($colorName ?: '');
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'color_size',
                    'size' => $sizeName,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => (int) $colorSize->qty,
                    'webiste_available' => $webisteQty
                ];
            }
        }
    }
    return $inventory;
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
    $parts = explode('-', $channelId);
    if (count($parts) < 2) {
        return response()->json([]);
    }
    
    $channelType = strpos($channelId, 'boutique-') === 0 ? 'boutique' : 'channel';
    $locationId = (int)$parts[1];
    
    if ($locationId <= 0) {
        return response()->json([]);
    }

    // Get stocks from channel_stocks table
    $channelStocks = ChannelStock::where('location_type', $channelType)
        ->where('location_id', $locationId)
        ->where('quantity', '>', 0) // Only get items with quantity > 0
        ->with(['stock'])
        ->get();

    // Group by uid (code|size|color) to aggregate quantities
    $groupedInventory = [];
    
    foreach ($channelStocks as $channelStock) {
        $stock = $channelStock->stock;
        if (!$stock) continue;

        $code = $channelStock->abaya_code;
        if (!$code) continue; // Skip if no code
        
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

        // Aggregate quantities for the same uid
        if (!isset($groupedInventory[$uid])) {
            $groupedInventory[$uid] = [
                'uid' => $uid,
                'code' => $code,
                'name' => $name,
                'type' => $itemType,
                'size' => $sizeName,
                'color' => $colorName,
                'color_code' => $colorCode,
                'available' => 0
            ];
        }
        
        $groupedInventory[$uid]['available'] += (int)$channelStock->quantity;
    }
    
    // Convert grouped array to indexed array
    $inventory = array_values($groupedInventory);
    
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

            // Get destination channel stock quantity BEFORE update (for audit log when transferring TO channel)
            $channelQtyBefore = null;
            if ($toChannel !== 'main' && strpos($toChannel, 'channel-') === 0 && $itemType === 'color_size' && $colorId && $sizeId) {
                $toChId = (int) explode('-', $toChannel)[1];
                $chStock = ChannelStock::where('stock_id', $stock->id)->where('location_type', 'channel')->where('location_id', $toChId)
                    ->where('color_id', $colorId)->where('size_id', $sizeId)->first();
                $channelQtyBefore = $chStock ? (int) $chStock->quantity : 0;
            }
            // Get source channel stock quantity BEFORE update (for audit log when transferring FROM channel)
            $fromChannelQtyBefore = null;
            if ($fromChannel !== 'main' && strpos($fromChannel, 'channel-') === 0 && $itemType === 'color_size' && $colorId && $sizeId) {
                $fromChId = (int) explode('-', $fromChannel)[1];
                $chStock = ChannelStock::where('stock_id', $stock->id)->where('location_type', 'channel')->where('location_id', $fromChId)
                    ->where('color_id', $colorId)->where('size_id', $sizeId)->first();
                $fromChannelQtyBefore = $chStock ? (int) $chStock->quantity : 0;
            }

            // Update channel stocks
            $this->updateChannelStock($stock->id, $item['code'], $itemType, $colorId, $sizeId, $colorName, $sizeName, $fromChannel, $toChannel, (int)$item['qty']);

            // Update main warehouse quantities (if transferring from/to main)
            if ($fromChannel === 'main') {
                $this->decreaseMainWarehouseStock($stock->id, $itemType, $colorId, $sizeId, (int)$item['qty']);

                // Log audit: when destination is a channel, store channel-side quantities (per barcode) so channel audit shows correctly
                if ($toType === 'channel' && $channelQtyBefore !== null) {
                    $channelNewQty = $channelQtyBefore + (int)$item['qty'];
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'transferred',
                        'previous_quantity' => $channelQtyBefore,
                        'new_quantity' => $channelNewQty,
                        'quantity_change' => (int)$item['qty'],
                        'related_id' => $transferCode,
                        'related_type' => 'transfer',
                        'related_info' => ['to' => $toChannel, 'from' => 'main'],
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'user_id' => $user_id,
                        'added_by' => $user_name,
                        'notes' => 'Transferred out',
                    ]);
                } else {
                    $currentQty = 0;
                    if ($itemType === 'color_size' && $colorId && $sizeId) {
                        $colorSize = ColorSize::where('stock_id', $stock->id)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
                        $currentQty = $colorSize ? (int)$colorSize->qty : 0;
                    }
                    $newQty = max(0, $currentQty - (int)$item['qty']);
                    $toWhom = $toType === 'boutique' ? (Boutique::find($toId)->boutique_name ?? $toChannel) : $toChannel;
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'transferred',
                        'previous_quantity' => $currentQty,
                        'new_quantity' => $newQty,
                        'quantity_change' => -(int)$item['qty'],
                        'related_id' => $transferCode,
                        'related_type' => 'transfer',
                        'related_info' => ['to' => $toChannel, 'from' => 'main'],
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'user_id' => $user_id,
                        'added_by' => $user_name,
                        'notes' => 'Transferred out',
                    ]);
                }
            }
            if ($toChannel === 'main') {
                $this->increaseMainWarehouseStock($stock->id, $itemType, $colorId, $sizeId, (int)$item['qty']);

                // Log audit: when source is a channel, store channel-side quantities for channel audit
                if ($fromType === 'channel' && $fromChannelQtyBefore !== null) {
                    $channelNewQty = max(0, $fromChannelQtyBefore - (int)$item['qty']);
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'transferred',
                        'previous_quantity' => $fromChannelQtyBefore,
                        'new_quantity' => $channelNewQty,
                        'quantity_change' => -(int)$item['qty'],
                        'related_id' => $transferCode,
                        'related_type' => 'transfer',
                        'related_info' => ['from' => $fromChannel, 'to' => 'main'],
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'user_id' => $user_id,
                        'added_by' => $user_name,
                        'notes' => 'Transferred in',
                    ]);
                } else {
                    $currentQty = 0;
                    if ($itemType === 'color_size' && $colorId && $sizeId) {
                        $colorSize = ColorSize::where('stock_id', $stock->id)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
                        $currentQty = $colorSize ? (int)$colorSize->qty : 0;
                    }
                    $newQty = $currentQty + (int)$item['qty'];
                    $fromWhom = $fromType === 'boutique' ? (Boutique::find($fromId)->boutique_name ?? $fromChannel) : $fromChannel;
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'transferred',
                        'previous_quantity' => $currentQty,
                        'new_quantity' => $newQty,
                        'quantity_change' => (int)$item['qty'],
                        'related_id' => $transferCode,
                        'related_type' => 'transfer',
                        'related_info' => ['from' => $fromChannel, 'to' => 'main'],
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'user_id' => $user_id,
                        'added_by' => $user_name,
                        'notes' => 'Transferred in',
                    ]);
                }
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
            $history->quantity_pushed = $toChannel === 'main' ? (int)$item['qty'] : 0;
            $history->added_by = $user_name;
            $history->user_id = $user_id;
            $history->save();

            // If transferring FROM boutique to another boutique/channel, decrease sellable in source boutique's transfer
            if (strpos($fromChannel, 'boutique-') === 0) {
                $this->updateSellableOnPull($fromChannel, $item['code'], $colorName, $sizeName, (int)$item['qty'], $colorId, $sizeId);
            }
        }

        DB::commit();

        // Sync transfer items to website (only when to_location = channel-1
        // and related stock.website_data_delivery_status = 1)
        try {
            if (function_exists('syncTransferItemsToWebsiteReceiveQty')) {
                syncTransferItemsToWebsiteReceiveQty($transfer->id, 'channel-1');
            }
        } catch (\Throwable $e) {
            \Log::error('Transfer items website sync failed', [
                'transfer_id' => $transfer->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

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
 * Manual endpoint to sync transfer items to website API:
 * - Only items where to_location = channel-1
 * - Only items whose related stock has website_data_delivery_status = 1
 *
 * URL: /sync-transfer-receive-qty/{transferId}
 */
public function syncTransferReceiveQty($transferId)
{
    if (!Auth::check()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Please login first',
        ], 401);
    }

    $permissions = Auth::user()->permissions ?? [];
    if (!in_array(6, $permissions)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Permission denied',
        ], 403);
    }

    $transfer = Transfer::find($transferId);
    if (!$transfer) {
        return response()->json([
            'status' => 'error',
            'message' => 'Transfer not found',
        ], 404);
    }

    if (!function_exists('syncTransferItemsToWebsiteReceiveQty')) {
        return response()->json([
            'status' => 'error',
            'message' => 'Sync helper not available',
        ], 500);
    }

    $summary = syncTransferItemsToWebsiteReceiveQty($transfer->id, 'channel-1');

    return response()->json([
        'status' => 'success',
        'transfer_id' => $transfer->id,
        'transfer_code' => $transfer->transfer_code,
        'summary' => $summary,
    ]);
}

/**
 * Manual endpoint to sync ALL matching transfer items to website API.
 * Conditions:
 * - transfer_items.to_location = channel-1
 * - related stocks.website_data_delivery_status = 1
 *
 * URL: /sync-transfer-receive-qty-bulk?limit=50 (limit optional)
 */
public function syncTransferReceiveQtyBulk(Request $request)
{
    if (!Auth::check()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Please login first',
        ], 401);
    }

    $permissions = Auth::user()->permissions ?? [];
    if (!in_array(6, $permissions)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Permission denied',
        ], 403);
    }

    if (!function_exists('syncAllTransferItemsToWebsiteReceiveQty')) {
        return response()->json([
            'status' => 'error',
            'message' => 'Sync helper not available',
        ], 500);
    }

    $limit = (int) $request->query('limit', 0);
    $summary = syncAllTransferItemsToWebsiteReceiveQty('channel-1', $limit);
 
    // Helpful debug stats so you can see why "picked" is 0
    $totalTransferItems = TransferItem::count();
    $totalToChannel1 = TransferItem::whereRaw('TRIM(to_location) = ?', ['channel-1'])->count();

    $hasTransferItemsWebsiteStatus = \Illuminate\Support\Facades\Schema::hasColumn('transfer_items', 'website_data_delivery_status');
    $totalTransferItemsWebsiteStatus1 = $hasTransferItemsWebsiteStatus
        ? TransferItem::where('website_data_delivery_status', 1)->count()
        : null;
    $totalTransferItemsBoth = $hasTransferItemsWebsiteStatus
        ? TransferItem::whereRaw('TRIM(to_location) = ?', ['channel-1'])
            ->where('website_data_delivery_status', 1)
            ->count()
        : null;

    return response()->json([
        'status' => 'success',
        'stats' => [
            'total_transfer_items' => $totalTransferItems,
            'to_location_channel_1' => $totalToChannel1,
            'transfer_items_has_website_data_delivery_status' => $hasTransferItemsWebsiteStatus,
            'transfer_items_website_status_1' => $totalTransferItemsWebsiteStatus1,
            'transfer_items_both_conditions' => $totalTransferItemsBoth,
        ],
        'summary' => $summary,
    ]);
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
 * Movements Log Page with Pagination
 */
public function movements_log(Request $request)
{
    $locale = session('locale');
    $search = $request->input('search', '');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');
    $perPage = 10;

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

    $transfers = $query->paginate($perPage)->appends($request->query());

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

    // Transform transfers for display
    $transfers->getCollection()->transform(function ($transfer) use ($getLocationName) {
        return [
            'id' => $transfer->id,
            'no' => $transfer->transfer_code,
            'date' => $transfer->date->format('Y-m-d'),
            'from' => $getLocationName($transfer->from),
            'to' => $getLocationName($transfer->to),
            'total' => $transfer->quantity,
            'items' => $transfer->items->map(function ($item) {
                return [
                    'code' => $item->abaya_code,
                    'color' => $item->color_name,
                    'size' => $item->size_name,
                    'qty' => $item->quantity,
                ];
            })->toArray(),
            'note' => $transfer->notes ?? '',
        ];
    });

    return view('wharehouse.movements_log', compact('transfers', 'search', 'dateFrom', 'dateTo'));
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

    // For boutiques, "qty" in ChannelStock can include already-settled quantities.
    // We also compute an "unsettled_qty" per code/color/size:
    // unsettled_qty = sum(transfer_items.sellable to boutique) - sum(transfer_items.quantity pulled from boutique)
    $unsettledMap = [];
    if ($channelType === 'boutique') {
        $boutiqueLocation = 'boutique-' . $locationId;

        // Sum sellable sent TO boutique (already reduced by settlement saves)
        $sent = TransferItem::select(
                'abaya_code',
                'color_id',
                'size_id',
                'color_name',
                'size_name',
                DB::raw('SUM(sellable) as sellable_sum')
            )
            ->where('to_location', $boutiqueLocation)
            ->groupBy('abaya_code', 'color_id', 'size_id', 'color_name', 'size_name')
            ->get();

        // Sum quantities pulled/transferred FROM boutique
        $pulled = TransferItem::select(
                'abaya_code',
                'color_id',
                'size_id',
                'color_name',
                'size_name',
                DB::raw('SUM(quantity) as pulled_sum')
            )
            ->where('from_location', $boutiqueLocation)
            ->groupBy('abaya_code', 'color_id', 'size_id', 'color_name', 'size_name')
            ->get();

        $pulledMap = [];
        foreach ($pulled as $p) {
            $k = ($p->abaya_code ?? '') . '|' . ($p->color_id ?? 'null') . '|' . ($p->size_id ?? 'null') . '|' . ($p->color_name ?? '') . '|' . ($p->size_name ?? '');
            $pulledMap[$k] = (int)($p->pulled_sum ?? 0);
        }

        foreach ($sent as $s) {
            $k = ($s->abaya_code ?? '') . '|' . ($s->color_id ?? 'null') . '|' . ($s->size_id ?? 'null') . '|' . ($s->color_name ?? '') . '|' . ($s->size_name ?? '');
            $sellableSum = (int)($s->sellable_sum ?? 0);
            $pulledSum = (int)($pulledMap[$k] ?? 0);
            $unsettledMap[$k] = max(0, $sellableSum - $pulledSum);
        }
    }

    $result = [];
    foreach ($stocks as $stock) {
        $key = ($stock->abaya_code ?? '') . '|' . ($stock->color_id ?? 'null') . '|' . ($stock->size_id ?? 'null') . '|' . ($stock->color_name ?? '') . '|' . ($stock->size_name ?? '');
        $unsettledQty = $channelType === 'boutique'
            ? (int)($unsettledMap[$key] ?? 0)
            : (int)($stock->quantity ?? 0);

        $result[] = [
            'code' => $stock->abaya_code,
            'color' => $stock->color_name,
            'size' => $stock->size_name,
            'qty' => $stock->quantity,
            'unsettled_qty' => $unsettledQty,
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
 * Get settlement history with pagination
 */
public function get_settlement_history(Request $request)
{
    $locale = session('locale');
    $search = $request->input('search', '');
    $month = $request->input('month', '');
    $page = $request->input('page', 1);

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

    // Paginate results - 10 entries per page
    $settlements = $query->paginate(10, ['*'], 'page', $page);

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

    return response()->json([
        'data' => $history,
        'current_page' => $settlements->currentPage(),
        'last_page' => $settlements->lastPage(),
        'total' => $settlements->total(),
        'per_page' => $settlements->perPage(),
        'from' => $settlements->firstItem(),
        'to' => $settlements->lastItem(),
    ]);
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


        public function syncPendingTransferItemsToWebsite()
    {
        // Optional: you can pass limit or different target location
        $targetToLocation = 'channel-1';
        $limit = 0; // 0 = no limit

        // Call your helper function
        syncAllTransferItemsToWebsiteReceiveQty($targetToLocation, $limit);

        // For debugging, you can return all items that are still pending
    
    }
    
// public function receiveWebsiteOrders(Request $request)
// {
//     try {

//         // 1️⃣ Get data from query OR body (both supported)
//         $pos_order_main   = $request->query('pos_order_main') ?? $request->input('pos_order_main');
//         $pos_order_detail = $request->query('pos_order_detail') ?? $request->input('pos_order_detail');

//         // 2️⃣ Decode JSON if string
//         $pos_order_main_decoded = is_string($pos_order_main)
//             ? json_decode($pos_order_main, true)
//             : $pos_order_main;

//         $pos_order_detail_decoded = is_string($pos_order_detail)
//             ? json_decode($pos_order_detail, true)
//             : $pos_order_detail;

//         // 3️⃣ Log EVERYTHING (proof API was hit)
//         \Log::info('receive_website_orders HIT', [
//             'pos_order_main'   => $pos_order_main_decoded,
//             'pos_order_detail' => $pos_order_detail_decoded,
//             'raw_request'      => $request->all(),
//             'headers'          => $request->headers->all(),
//         ]);

//         // 4️⃣ Return full data in response (for testing)
//         return response()->json([
//             'status' => 'success',
//             'message' => 'Orders received successfully',
//             'pos_order_main' => $pos_order_main_decoded,
//             'pos_order_detail' => $pos_order_detail_decoded,
//         ]);

//     } catch (\Throwable $e) {

//         \Log::error('receive_website_orders FAILED', [
//             'error' => $e->getMessage(),
//             'line'  => $e->getLine(),
//         ]);

//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }

   
public function receiveWebsiteOrders(Request $request)
{
    try {
        $payload = $request->all();

        if (empty($payload) && $request->getContent()) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        Log::info('receive_website_orders HIT', [
            'payload' => $payload,
            'headers' => $request->headers->all(),
        ]);

        $posOrderMain   = json_decode($payload['pos_order_main'] ?? '{}', true);
        $posOrderDetail = json_decode($payload['pos_order_detail'] ?? '[]', true);

        if (empty($posOrderMain) || empty($posOrderDetail)) {
            throw new \Exception('Order data missing');
        }

        DB::beginTransaction();
        
        
     
      
        /* =========================
         * 1️⃣ GET OR CREATE CUSTOMER
         * ========================= */
        $customerName = trim(
            $posOrderMain['customer_name'] . ' ' .
            ($posOrderMain['customer_middle_name'] ?? '') . ' ' .
            ($posOrderMain['customer_sur_name'] ?? '')
        );

        $customer = Customer::where('phone', $posOrderMain['customer_contact'])
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'name'        => $customerName,
                'phone'       => $posOrderMain['customer_contact'],
                'email'       => $posOrderMain['customer_email'] ?? null,
                'governorate' => $posOrderMain['customer_area'] ?? null,
                'area'        => $posOrderMain['customer_city'] ?? null,
            ]);
        }

        /* =========================
         * 2️⃣ CALCULATE TOTALS
         * ========================= */
       

         

        
        
        $specialItemDetail = [];
        $posItemDetail = [];
        
        
        $total_qtys=0;
        foreach ($posOrderDetail as $item) {
        
            // make sure length exists + numeric
            $length = isset($item['length']) ? (float)$item['length'] : 0;
            $total_qtys += $item['product_qty']; 
            if ($length > 0) {
                $specialItemDetail[] = $item;
            } else {
                $posItemDetail[] = $item;
            }
        }
        
        $per_piece_discount = 0;
        
        if($posOrderMain['coupon_discount']>0)
        {
            $per_piece_discount = $posOrderMain['coupon_discount']/$total_qtys;
        }
        
        


        /* =========================
         * 3️⃣ CREATE SPECIAL ORDER
         * ========================= */
         $shipping_charges_pos=0;
         $shipping_charges_special=0;
        if(count($specialItemDetail)>0 && count($posItemDetail)>0)
        {
            $shipping_charges_pos =$posOrderMain['shipping_charges'];
            $shipping_charges_special=0;
        }
        elseif(count($specialItemDetail)>0)
        {
            $shipping_charges_special=$posOrderMain['shipping_charges'];
        }
        elseif(count($posItemDetail)>0)
        {
            $shipping_charges_pos=$posOrderMain['shipping_charges'];
        }
        
        
        $original_price = 0;
        $paid_amount    = 0;
        
        $specialOrder = SpecialOrder::create([
            'source'           => 'website',
            'customer_id'      => $customer->id,
            'special_order_no' => $posOrderMain['order_id'],
            'send_as_gift'     => $posOrderMain['gift'] ?? 0,
            'gift_text'        => $posOrderMain['customer_gift'] ?? null,
            'notes'            => $posOrderMain['status'] ?? null,
            'shipping_fee'     => $shipping_charges_special ?? 0,
            'status'           => 'new',
            
            'account_id'       => 4,
            'user_id'       => 1,
            'added_by'         => 'system',
            'created_at'       => $posOrderMain['created_at'] ?? now(),
        ]);

        foreach ($specialItemDetail as $item) {
            $stock = Stock::find($item['product_id']);
            $new_price = ($item['product_price']-$per_piece_discount);
            $itemTotal =  $new_price* $item['product_qty'];
            $original_price += $itemTotal;

            // FULL PAYMENT
            if (!empty($posOrderMain['pay_full'])) {
                $paid_amount += $itemTotal;
            }
            // PARTIAL PAYMENT (Special Order)
            elseif (!empty($posOrderMain['partial_amount_status'])) {
                $half = $new_price / 2;
                if($half>10)
                {
                    $paid_amount += 10 * $item['product_qty'];
                }
                else
                {
                    $paid_amount += $half * $item['product_qty'];
                }
                
            }
            
            $grand_total = $original_price;
            $remaining   = $grand_total - $paid_amount;

            SpecialOrderItem::create([
                'special_order_id' => $specialOrder->id,
                'stock_id'         => $item['product_id'],
                'abaya_code'       => $stock->abaya_code ?? null,
                'design_name'      => $stock->design_name ?? null,
                'price'            => ($new_price),
                'quantity'         => $item['product_qty'],
                'bust'             => $item['bust'] ?? null,
                'sleeves_length'   => $item['slevees'] ?? null,
                'abaya_length'     => $item['length'] ?? null,
                'button'           => (isset($item['button']) && strtolower($item['button']) === 'yes') ? 1 : 0,
                'notes'            => $item['order_notes'] ?? null,
            ]);
        }
        
       
        /* =========================
         * 4️⃣ CREATE POS ORDER
         * ========================= */
        

        /* =========================
         * 5️⃣ POS ORDER DETAILS
         * ========================= */
         $posgrandtotal=0;
         $total_discount_pos=0;
         $ship_paid=0;
         if($posOrderMain['customer_city']==10 || $posOrderMain['customer_city']==11)
         {
             $ship_paid=$shipping_charges_pos;
         }
         
         $posOrder = PosOrders::create([
            'customer_id'      => $customer->id,
            'order_type'       => 'website',
            'special_order_id' => $specialOrder->id,
            'delivery_status'  => $posOrderMain['shipping_status'] ?? null,
            'delivery_city_id' => $posOrderMain['customer_city'] ?? null,
            'delivery_area_id' => $posOrderMain['customer_area'] ?? null,
            'delivery_address' => $posOrderMain['customer_notes'] ?? null,
            'delivery_fee'     => $shipping_charges_pos ?? 0,
            'delivery_fee_paid'     => $ship_paid ?? 0,
            'item_count'       => count($posItemDetail),
            'paid_amount'      => 0,
            'total_amount'     => 0,
            'total_discount'   => 0,
            'order_no'         => $posOrderMain['order_id'],
            'notes'            => $posOrderMain['customer_notes'] ?? null,
            'added_by'         => 'system',
            'created_at'       => $posOrderMain['created_at'] ?? now(),
            'raw_payload'      => json_encode($payload),
        ]);
         
        foreach ($posItemDetail as $item) {
            
             $new_price = ($item['product_price']-$per_piece_discount);
            $posgrandtotal += $new_price * $item['product_qty'];
            $total_discount_pos=($per_piece_discount*$item['product_qty']);
            
            PosOrdersDetail::create([
                'order_id' => $posOrder->id,
                'order_no' => $posOrderMain['order_id'],
                'item_id'     => $item['product_id'],
                'color_id'        => $item['color'],
                'size_id'     => $item['size'],
                'item_barcode' => Stock::find($item['product_id'])->barcode ?? null,
                'item_quantity'        => $item['product_qty'],
                'item_discount_price'       => ($per_piece_discount*$item['product_qty']) ?? 0,
                'item_price'       => $item['product_price'],
                'item_total' => $item['product_price'] * $item['product_qty'],
                'item_profit' => Stock::where('id', $item['product_id'])->first()->sales_price - $item['product_price'] ?? 0,
                'notes'        => $item['order_notes'] ?? null,
                'added_by'   => 'system',
                'created_at'   => $posOrderMain['created_at'] ?? now(),
            ]);
        }
    
        $posOrder->paid_amount     = $posgrandtotal ?? 0;
        $posOrder->total_amount     = $posgrandtotal ?? 0;
        $posOrder->total_discount     = $total_discount_pos ?? 0;
        $posOrder->save();
        
        $specialOrder->pos_order_id     = $posOrder->id;
        $specialOrder->total_amount     = $grand_total ?? 0;
        $specialOrder->paid_amount     = $paid_amount ?? 0;
        $specialOrder->save();
       

        /* =========================
         * 6️⃣ POS PAYMENT
         * ========================= */
        PosPayment::create([
            'order_id'   => $posOrder->id,
            'order_no' => $posOrderMain['order_id'],
            'paid_amount'   => $posgrandtotal,
            'total_amount'  => $posgrandtotal,
            'discount'=>    $total_discount_pos ?? 0,
            'account_id'   => 4,
            'notes'         => $posOrderMain['customer_notes'] ?? null,

            'added_by'       => 'system',
            'created_at'     => $posOrderMain['created_at'] ?? now(),
        ]);

        DB::commit();

        return response()->json([
            'status'       => 'success',
            'message'      => 'Website order processed successfully',
            'order_no'     => $posOrder->order_no,
            'special_id'   => $specialOrder->id,
            'pos_order_id' => $posOrder->id,
            'paid_amount'  => $paid_amount,
            'remaining'    => $remaining,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('receive_website_orders FAILED', [
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
            'file'  => $e->getFile(),
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
} 
    
}