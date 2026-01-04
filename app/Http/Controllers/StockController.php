<?php

namespace App\Http\Controllers;

use App\Models\Size;
use App\Models\Color;
use App\Models\Stock;
use App\Models\Tailor;
use App\Models\Category;
use App\Models\ColorSize;
use App\Models\StockSize;
use App\Models\StockColor;
use App\Models\StockImage;
use Illuminate\Support\Str;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $tailors = Tailor::all();
        $colors = Color::all();
        $sizes = Size::all();
        $categories = Category::all();

        return view('stock.add_stock', compact('tailors', 'colors', 'sizes', 'categories'));
    }

public function edit_stock($id)
{
    $tailors = Tailor::all();
    $colors  = Color::all();
    $sizes   = Size::all();
    $categories = Category::all();

    $stock = Stock::with([
        'colors',    
        'sizes',        
        'colorSizes',  
        'images',
        'category'
    ])->findOrFail($id);

    $selectedTailors = json_decode($stock->tailor_id, true) ?? [];

    return view('stock.edit_stock', compact('tailors', 'colors', 'sizes', 'categories', 'id', 'stock', 'selectedTailors'));
}


    public function deleteImage($id)
{
    $image = StockImage::findOrFail($id);

    // Delete file from server
    if (file_exists(public_path($image->image_path))) {
        unlink(public_path($image->image_path));
    }

    // Delete DB record
    $image->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Image deleted successfully!'
    ]);
}





     public function view_stock()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.view_stock');
    }

public function getstock()
{
    // Eager load relationships
   $stocks = Stock::with([
    'colorSizes.size',
    'colorSizes.color',
    'images',
    'category'
])
->orderBy('id', 'DESC')
->paginate(10);

    return response()->json($stocks);
}





public function add_stock(Request $request)
{

    $totalQty = 0;
if (!empty($request->color_sizes)) {
    foreach ($request->color_sizes as $color_id => $sizes) {
        foreach ($sizes as $size_id => $data) {
            $totalQty += $data['qty'] ?? 0;
        }
    }
}

    $tailor_ids = $request->input('tailor_id');
    $stock = new Stock();
    $stock->abaya_code         = $request->abaya_code;
    $stock->design_name        = $request->design_name;
    $stock->barcode            = $request->barcode;
    $stock->abaya_notes        = $request->abaya_notes;
    $stock->category_id        = $request->category_id;
    $stock->cost_price         = $request->cost_price;
    $stock->sales_price        = $request->sales_price;
    $stock->tailor_charges     = $request->tailor_charges;
    $stock->tailor_id          = json_encode($tailor_ids);
    $stock->quantity           = $totalQty;
    $stock->notification_limit = $request->notification_limit;
    $stock->mode               = $request->mode;
    $stock->save();

    // Get authenticated user info for history tracking
    $user_id = \Illuminate\Support\Facades\Auth::id();
    $user = \App\Models\User::find($user_id);
    $user_name = $user ? $user->user_name : 'System';

    // ========= 1️⃣ Save Color Only =========
    if (!empty($request->colors)) {
        foreach ($request->colors as $color) {
            $qty = $color['qty'] ?? 0;
            $stockColor = new StockColor();
            $stockColor->stock_id = $stock->id;
            $stockColor->color_id = $color['color_id'];
            $stockColor->qty      = $qty;
            $stockColor->save();

            // Create StockHistory record for initial addition
            if ($qty > 0) {
                StockHistory::create([
                    'stock_id'    => $stock->id,
                    'size_id'     => null,
                    'color_id'    => $color['color_id'],
                    'old_qty'     => 0,
                    'changed_qty' => $qty,
                    'new_qty'     => $qty,
                    'action_type' => 1, // 1 = addition
                    'pull_notes'  => null,
                    'user_id'     => $user_id ? (string)$user_id : null,
                    'added_by'    => $user_name,
                ]);
            }
        }
    }

    // ========= 2️⃣ Save Size Only =========
    if (!empty($request->sizes)) {
        foreach ($request->sizes as $size_id => $size) {
            $qty = $size['qty'] ?? 0;
            $stockSize = new StockSize();
            $stockSize->stock_id = $stock->id;
            $stockSize->size_id  = $size_id;
            $stockSize->qty      = $qty;
            $stockSize->save();

            // Create StockHistory record for initial addition
            if ($qty > 0) {
                StockHistory::create([
                    'stock_id'    => $stock->id,
                    'size_id'     => $size_id,
                    'color_id'    => null,
                    'old_qty'     => 0,
                    'changed_qty' => $qty,
                    'new_qty'     => $qty,
                    'action_type' => 1, // 1 = addition
                    'pull_notes'  => null,
                    'user_id'     => $user_id ? (string)$user_id : null,
                    'added_by'    => $user_name,
                ]);
            }
        }
    }

    // ========= 3️⃣ Save Color + Size =========
    if (!empty($request->color_sizes)) {
        foreach ($request->color_sizes as $color_id => $sizes) {
            foreach ($sizes as $size_id => $data) {
                $qty = $data['qty'] ?? 0;
                $colorSize = new ColorSize();
                $colorSize->stock_id = $stock->id;
                $colorSize->color_id = $color_id;
                $colorSize->size_id  = $data['size_id'];
                $colorSize->qty      = $qty;
                $colorSize->save();

                // Create StockHistory record for initial addition
                if ($qty > 0) {
                    StockHistory::create([
                        'stock_id'    => $stock->id,
                        'size_id'     => $data['size_id'],
                        'color_id'    => $color_id,
                        'old_qty'     => 0,
                        'changed_qty' => $qty,
                        'new_qty'     => $qty,
                        'action_type' => 1, // 1 = addition
                        'pull_notes'  => null,
                        'user_id'     => $user_id ? (string)$user_id : null,
                        'added_by'    => $user_name,
                    ]);
                }
            }
        }
    }

    // ========= 4️⃣ Save Images =========
    if ($request->hasFile('images')) {
        $folderPath = public_path('images/stock_images');

        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
        }

        foreach ($request->file('images') as $image) {
            $imageName = time() . '_' . \Illuminate\Support\Str::random(6) . '.' . $image->getClientOriginalExtension();
            $image->move($folderPath, $imageName);

            $stock_img = new StockImage();
            $stock_img->stock_id   = $stock->id;
            $stock_img->image_path = asset('images/stock_images/' . $imageName);
            $stock_img->save();
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Stock added successfully!',
        'redirect_url' => url('view_stock'),
    ]);
}


public function update_stock(Request $request)
{
    $stock_id = $request->stock_id;
    $stock = Stock::findOrFail($stock_id);
    $tailor_ids = $request->input('tailor_id');
    // ---------- Update Stock Basic Fields ----------
    $stock->abaya_code         = $request->abaya_code;
    $stock->design_name        = $request->design_name;
    $stock->barcode            = $request->barcode;
    $stock->abaya_notes        = $request->abaya_notes;
    $stock->category_id        = $request->category_id;
    $stock->cost_price         = $request->cost_price;
    $stock->sales_price        = $request->sales_price;
    $stock->tailor_id          = json_encode($request->tailor_id);
    $stock->quantity           = $request->total_quantity ?? null;
    $stock->notification_limit = $request->notification_limit;
    $stock->mode               = $request->mode;
    $stock->save();

    StockColor::where('stock_id', $stock_id)->delete();
    StockSize::where('stock_id', $stock_id)->delete();
    ColorSize::where('stock_id', $stock_id)->delete();


    if (!empty($request->colors)) {
        foreach ($request->colors as $color) {

            $stockColor = new StockColor();
            $stockColor->stock_id = $stock->id;
            $stockColor->color_id = $color['color_id'];
            $stockColor->qty      = $color['qty'] ?? 0;
            $stockColor->save();
        }
    }

    if (!empty($request->sizes)) {
        foreach ($request->sizes as $size_id => $size) {

            $stockSize = new StockSize();
            $stockSize->stock_id = $stock->id;
            $stockSize->size_id  = $size_id;
            $stockSize->qty      = $size['qty'] ?? 0;
            $stockSize->save();
        }
    }

    
    if (!empty($request->color_sizes)) {

        foreach ($request->color_sizes as $color_id => $sizes) {
            foreach ($sizes as $size_id => $data) {

                $colorSize = new ColorSize();
                $colorSize->stock_id = $stock->id;
                $colorSize->color_id = $color_id;
                $colorSize->size_id  = $data['size_id'];  
                $colorSize->qty      = $data['qty'] ?? 0;
                $colorSize->save();
            }
        }
    }

    // ============================================
    // 4️⃣ UPDATE IMAGES
    // ============================================
    if ($request->hasFile('images')) {

        $folderPath = public_path('images/stock_images');

        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
        }

        foreach ($request->file('images') as $image) {

            $imageName = time() . '_' . Str::random(6) . '.' . $image->getClientOriginalExtension();
            $image->move($folderPath, $imageName);

            $stock_img = new StockImage();
            $stock_img->stock_id   = $stock->id;
            $stock_img->image_path = asset('images/stock_images/' . $imageName);
            $stock_img->save();
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Stock Updated successfully!',
        'redirect_url' => url('view_stock'),
    ]);
}


public function delete_stock($id)
{
    $stock = Stock::find($id);

    if (!$stock) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Stock not found'
        ], 404);
    }

    // Delete related data
    StockColor::where('stock_id', $id)->delete();
    StockSize::where('stock_id', $id)->delete();
    ColorSize::where('stock_id', $id)->delete();
    StockImage::where('stock_id', $id)->delete();

    // Delete stock
    $stock->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'Stock deleted successfully!'
    ]);
}



public function stock_detail(Request $request)
{
    $stock = Stock::with(['colors.color', 'sizes.size', 'images',  'colorSizes',])
                  ->findOrFail($request->id);

    $stock_sizes = $stock->sizes;
        $stock_colors = $stock->colors;

$stock_sizescolor = $stock->colorSizes; // Assuming it has color_id, size_id, qty

$htmlSizeColor = '';

foreach ($stock_sizescolor as $index => $item) {
    // Get size name based on session locale
$size_name = session('locale') === 'ar' 
             ? ($item->size?->size_name_ar ?? '-') 
             : ($item->size?->size_name_en ?? '-');

$color_name = session('locale') === 'ar' 
             ? ($item->color?->color_name_ar ?? '-') 
             : ($item->color?->color_name_en ?? '-');

$color_code = $item->color?->color_code ?? '#000'; // fallback to black if null
$qty = $item->qty ?? 0;

    $htmlSizeColor .= '<div class="flex justify-between items-center border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                        <div class="flex flex-col">
                            <span class="font-semibold">Size: ' . $size_name . '</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="w-4 h-4 rounded-full border" style="background:' . $color_code . '"></span>
                                <span>' . $color_name . '</span>
                            </div>
                        </div>
                        <span class="font-bold text-[var(--primary-color)]">' . $qty . ' pcs</span>
                      </div>';
}


    $html = '';
    $color = '';

    foreach ($stock_colors as $index => $stock_color) {
        // Choose size name based on session locale
        $color_name = session('locale') === 'ar' 
                     ? $stock_color->color->color_name_ar 
                     : $stock_color->color->color_name_en;

                     $color_code = $stock_color->color->color_code;

        $color_qty = $stock_color->qty;

        // Use unique IDs if needed, or just remove them
        $color .= '<div class="flex items-center justify-between border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border" style="background:' . $color_code . '"></span>
                            <span class="font-semibold">' . $color_name . '</span>
                        </div>
                        <span class="font-bold text-[var(--primary-color)]">' . $color_qty . ' pcs</span>
                      </div>';
    }

   foreach ($stock_sizes as $index => $stock_size) {
        // Choose size name based on session locale
        $size_name = session('locale') === 'ar' 
                     ? $stock_size->size->size_name_ar 
                     : $stock_size->size->size_name_en;

        $size_qty = $stock_size->qty;

        // Use unique IDs if needed, or just remove them
        $html .= '<div class="p-3 border rounded-lg bg-gray-50 text-center font-bold text-gray-700 text-xs sm:text-sm">
                    <span id="size_label_'.$index.'">' . $size_name . '</span>
                    <span id="size_qty_'.$index.'" class="block text-[var(--primary-color)] mt-1">' . $size_qty . ' pcs</span>
                  </div>';
    }

    $data = [
        'stock_id' => $stock->id,
        'abaya_code' => $stock->abaya_code,
        'abaya_notes' => $stock->abaya_notes,
        'design_name' => $stock->design_name,
        'image_path' => $stock->images->first() ? $stock->images->first()->image_path : null,
        'barcode' => $stock->barcode,
        'status' => 'Available',
        'sizes_html' => $html,
        'size_color_html' => $htmlSizeColor, 
        'color'=>$color,
    ];

    return response()->json($data);
}

public function get_full_stock_details(Request $request)
{
    $locale = session('locale');
    $stock = Stock::with([
        'colorSizes.size',
        'colorSizes.color',
        'images'
    ])->findOrFail($request->id);

    // Calculate total quantity from all color_sizes
    $totalQuantity = 0;
    $colorSizeDetails = [];
    
    foreach ($stock->colorSizes as $item) {
        $size_name = $locale === 'ar' 
            ? ($item->size?->size_name_ar ?? '-') 
            : ($item->size?->size_name_en ?? '-');
        
        $color_name = $locale === 'ar' 
            ? ($item->color?->color_name_ar ?? '-') 
            : ($item->color?->color_name_en ?? '-');
        
        $qty = (int)($item->qty ?? 0);
        $totalQuantity += $qty;
        
        $colorSizeDetails[] = [
            'size_name' => $size_name,
            'color_name' => $color_name,
            'color_code' => $item->color?->color_code ?? '#000000',
            'quantity' => $qty,
        ];
    }

    // Get all images
    $images = $stock->images->map(function($img) {
        return $img->image_path;
    })->toArray();

    // Get tailor names
    $tailorNames = [];
    if ($stock->tailor_id) {
        $tailorIds = json_decode($stock->tailor_id, true);
        if (is_array($tailorIds)) {
            $tailors = Tailor::whereIn('id', $tailorIds)->get();
            foreach ($tailors as $tailor) {
                $tailorNames[] = $tailor->tailor_name;
            }
        }
    }

    $data = [
        'stock_id' => $stock->id,
        'abaya_code' => $stock->abaya_code ?? '-',
        'design_name' => $stock->design_name ?? '-',
        'barcode' => $stock->barcode ?? '-',
        'abaya_notes' => $stock->abaya_notes ?? '-',
        'cost_price' => $stock->cost_price ?? 0,
        'sales_price' => $stock->sales_price ?? 0,
        'tailor_charges' => $stock->tailor_charges ?? 0,
        'tailor_names' => $tailorNames,
        'total_quantity' => $totalQuantity,
        'images' => $images,
        'color_size_details' => $colorSizeDetails,
    ];

    return response()->json($data);
}

  public function get_stock_quantity(Request $request)
    {

     $id = $request->id ?? null;

$stock = Stock::with(['colors.color', 'sizes.size', 'images', 'colorSizes'])
    ->findOrFail($id);

       $stock_sizes = $stock->sizes;
        $stock_colors = $stock->colors;

       $stock_sizescolor = $stock->colorSizes; // Assuming it has color_id, size_id, qty // Assuming it has color_id, size_id, qty

       $htmlSizeColor = '<div class="row g-4">'; // start row

foreach ($stock_sizescolor as $item) {
    // Get size name based on session locale
    $size_name = session('locale') === 'ar'
        ? ($item->size?->size_name_ar ?? '-')
        : ($item->size?->size_name_en ?? '-');

    $color_name = session('locale') === 'ar'
        ? ($item->color?->color_name_ar ?? '-')
        : ($item->color?->color_name_en ?? '-');

    $color_code = $item->color?->color_code ?? '#000'; // fallback to black if null
    $qty = $item->qty ?? 0;

 $htmlSizeColor .= '
<div class="col-6 col-md-4 col-lg-3">
    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">

        <div class="card-body p-3">

            <!-- Color Circle + Color Name -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-circle border border-2 shadow-sm"
                    style="width:18px;height:18px;background-color:' . htmlspecialchars($color_code) . ';"></div>

                <span class="fw-semibold text-secondary" style="font-size: 0.8rem;">
                    ' . htmlspecialchars($color_name) . '
                </span>
            </div>

            <!-- Size badge -->
            <div class="mb-2 text-center">
                <span class="badge bg-dark text-light px-2 py-1 rounded-pill"
                      style="font-size: 0.75rem;">
                    Size: ' . htmlspecialchars($size_name) . '
                </span>

                <input type="hidden" name="stock_size_id[]" value="' . htmlspecialchars($item->size_id) . '">
                <input type="hidden" name="stock_color_id[]" value="' . htmlspecialchars($item->color_id) . '">
            </div>

            <!-- Previous Qty -->
            <div class="mb-2 text-center">
                <span class="badge bg-info text-dark px-3 py-1"
                      style="font-size: 0.75rem;">
                    Previous: <strong>' . htmlspecialchars($qty) . '</strong>
                </span>
            </div>

            <!-- Add Quantity Title -->
            <p class="text-center text-muted mb-2" style="font-size: 0.75rem;">
                Add Quantity
            </p>

            <!-- Input -->
            <input type="number" 
                min="0" 
                name="size_color_qty[]" 
                class="form-control form-control-sm text-center rounded-pill shadow-sm"
                placeholder="0"
                data-available-qty="' . htmlspecialchars($qty) . '"
                max="' . ($qty > 0 ? htmlspecialchars($qty) : '') . '">
        </div>
    </div>
</div>';

}

$htmlSizeColor .= '</div>'; // end row




      $htmlColor = '<div class="row g-4">';

    foreach ($stock_colors as $stock_color) {
        $color_name = session('locale') === 'ar'
            ? $stock_color->color->color_name_ar
            : $stock_color->color->color_name_en;

        $color_code = $stock_color->color->color_code ?? '#000';
        $qty = $stock_color->qty ?? 0;

        $htmlColor .= '
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                <div class="card-body text-center p-4">
                    <div class="d-flex flex-column align-items-center gap-2 mb-3">
                        <div class="rounded-circle border border-2"
                            style="width:40px;height:40px;background-color:' . htmlspecialchars($color_code) . '"></div>
                        <h6 class="fw-semibold mb-0">' . htmlspecialchars($color_name) . '</h6>
                        <input type="hidden" name="color_id[]" value="' . htmlspecialchars($stock_color->color_id) . '">

                    </div>
                    <input type="number" min="0" value="' . htmlspecialchars($qty) . '"
                        class="form-control form-control-lg text-center rounded-pill" name="color_qty[]"
                        placeholder="0"
                        data-available-qty="' . htmlspecialchars($qty) . '"
                        max="' . ($qty > 0 ? htmlspecialchars($qty) : '') . '">
                </div>
            </div>
        </div>';
    }

$htmlColor .= '</div>';


$html = '<div class="row g-3">'; // start row

foreach ($stock_sizes as $stock_size) {
    $size_name = session('locale') === 'ar'
        ? $stock_size->size->size_name_ar
        : $stock_size->size->size_name_en;

    $qty = $stock_size->qty;

   $html .= '
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100 border-0 shadow-sm hover-shadow transition">
            <div class="card-body text-center p-4">
                <h5 class="fw-bold text-dark mb-3">' 
                    . htmlspecialchars($size_name) . 
                '</h5>

                <input type="hidden" name="size_id[]" value="' . htmlspecialchars($stock_size->size_id) . '">

                <input type="number" min="0" value="' . htmlspecialchars($qty) . '" 
                    name="size_qty[]" class="form-control form-control-lg text-center rounded-pill"
                    placeholder="0"
                    data-available-qty="' . htmlspecialchars($qty) . '"
                    max="' . ($qty > 0 ? htmlspecialchars($qty) : '') . '">
            </div>
        </div>
    </div>';

}

$html .= '</div>'; // end row


        $data = [
            'stock_id' => $stock->id,
         
            'sizes_html' => $html,
            'size_color_html' => $htmlSizeColor,
            'color' => $htmlColor,
        ];

        return response()->json($data);
    }



public function add_quantity(Request $request)
{
    $stock_id = $request->stock_id;
    $isPull   = $request->qtyType === "pull";
    $actionType = $isPull ? 2 : 1;
    
    // Get authenticated user info for pull operations
    $user_id = \Illuminate\Support\Facades\Auth::id();
    $user = \App\Models\User::find($user_id);
    $user_name = $user ? $user->user_name : 'System';
    $pull_reason = $isPull ? ($request->pull_reason ?? '') : null;

    if ($request->filled('stock_size_id')) {

        foreach ($request->stock_size_id as $i => $sizeId) {


            $colorId = $request->stock_color_id[$i];

            $item = ColorSize::where('size_id', $sizeId)
                             ->where('color_id', $colorId)
                             ->first();

            if (!$item) continue;

            $old = $item->qty;
            $change = (int) $request->size_color_qty[$i];
            
            // Validate pull quantity doesn't exceed available
            if ($isPull && $change > $old) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pull quantity (' . $change . ') cannot exceed available quantity (' . $old . ')'
                ], 400);
            }
            
            $new = $isPull ? $old - $change : $old + $change;

            if ($isPull) {
                Stock::where('id', $stock_id)->decrement('quantity', $change);
            } else {
                Stock::where('id', $stock_id)->increment('quantity', $change);
            }
            $item->update(['qty' => $new]);

            StockHistory::create([
                'stock_id'    => $stock_id,
                'size_id'     => $sizeId,
                'color_id'    => $colorId,
                'old_qty'     => $old,
                'changed_qty' => $change,
                'new_qty'     => $new,
                'action_type' => $actionType,
                'pull_notes'  => $pull_reason,
                'user_id'     => $user_id ? (string)$user_id : null,
                'added_by'    => $user_name,
            ]);
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => $isPull ? 'Quantity pulled!' : 'Quantity added!'
    ]);
}

    /**
     * Show stock quantity audit list page
     */
    public function stockAudit()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('stock.stock_audit');
    }

    /**
     * Get stock audit list - all stock items with aggregated quantities
     */
    public function getStockAuditList(Request $request)
    {
        try {
            $locale = session('locale', 'en');
            
            // Get all stocks with their color-size combinations
            $stocks = Stock::with(['colorSizes.color', 'colorSizes.size', 'category'])
                ->whereHas('colorSizes')
                ->orderBy('id', 'DESC')
                ->get();

            $auditList = [];
            
            foreach ($stocks as $stock) {
                // Aggregate totals across all color/size combinations for this stock
                $totalAdded = 0;
                $totalPulled = 0;
                $totalPosSold = 0;
                $totalTransferredOut = 0;
                $totalTransferredIn = 0;
                $totalRemaining = 0;

                foreach ($stock->colorSizes as $colorSize) {
                    $colorId = $colorSize->color_id;
                    $sizeId = $colorSize->size_id;
                    $currentQty = (int)($colorSize->qty ?? 0);
                    $totalRemaining += $currentQty;

                    // Get color and size names for matching
                    $color = $colorSize->color;
                    $size = $colorSize->size;
                    $colorName = $color ? ($locale === 'ar' ? ($color->color_name_ar ?? $color->color_name_en) : ($color->color_name_en ?? $color->color_name_ar)) : '';
                    $sizeName = $size ? ($locale === 'ar' ? ($size->size_name_ar ?? $size->size_name_en) : ($size->size_name_en ?? $size->size_name_ar)) : '';

                    // Total added from history (action_type = 1 means addition)
                    $totalAdded += StockHistory::where('stock_id', $stock->id)
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->where('action_type', 1)
                        ->sum('changed_qty');

                    // Total pulled from history (action_type = 2 means pull)
                    $totalPulled += StockHistory::where('stock_id', $stock->id)
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->where('action_type', 2)
                        ->sum('changed_qty');

                    // Total POS sold
                    $totalPosSold += \App\Models\PosOrdersDetail::where('item_id', $stock->id)
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->sum('item_quantity');

                    // Get transfers OUT (from main/warehouse to channels/boutiques)
                    // Transfers where item_code matches and quantity_pulled > 0
                    $transfersOut = \App\Models\TransferItemHistory::where('item_code', $stock->abaya_code)
                        ->where('quantity_pulled', '>', 0)
                        ->with('transfer')
                        ->get()
                        ->filter(function($transfer) use ($colorName, $sizeName) {
                            $transferColor = $transfer->item_color ?? '';
                            $transferSize = $transfer->item_size ?? '';
                            $colorMatch = empty($transferColor) || empty($colorName) || $transferColor === $colorName;
                            $sizeMatch = empty($transferSize) || empty($sizeName) || $transferSize === $sizeName;
                            return $colorMatch && $sizeMatch;
                        });
                    
                    $totalTransferredOut += $transfersOut->sum('quantity_pulled');

                    // Get transfers IN (from channels/boutiques to main/warehouse)
                    // Transfers where item_code matches and quantity_pushed > 0
                    $transfersIn = \App\Models\TransferItemHistory::where('item_code', $stock->abaya_code)
                        ->where('quantity_pushed', '>', 0)
                        ->with('transfer')
                        ->get()
                        ->filter(function($transferItem) use ($colorName, $sizeName) {
                            $transfer = $transferItem->transfer;
                            if (!$transfer) return false;
                            
                            // Only include transfers TO main warehouse
                            $toLocation = $transfer->to ?? '';
                            if ($toLocation !== 'main') return false;
                            
                            // Match by color and size - be flexible with empty values
                            $transferColor = trim($transferItem->item_color ?? '');
                            $transferSize = trim($transferItem->item_size ?? '');
                            $stockColorName = trim($colorName ?? '');
                            $stockSizeName = trim($sizeName ?? '');
                            
                            // If both color and size are empty in transfer, match this color/size combination
                            if (empty($transferColor) && empty($transferSize)) {
                                return true;
                            }
                            
                            // If transfer has no color but has size, match by size only
                            if (empty($transferColor) && !empty($transferSize)) {
                                return empty($stockSizeName) || strtolower($transferSize) === strtolower($stockSizeName);
                            }
                            
                            // If transfer has color but no size, match by color only
                            if (!empty($transferColor) && empty($transferSize)) {
                                return empty($stockColorName) || strtolower($transferColor) === strtolower($stockColorName);
                            }
                            
                            // Both color and size exist in transfer - both must match
                            $colorMatch = empty($stockColorName) || strtolower($transferColor) === strtolower($stockColorName);
                            $sizeMatch = empty($stockSizeName) || strtolower($transferSize) === strtolower($stockSizeName);
                            
                            return $colorMatch && $sizeMatch;
                        });
                    
                    $totalTransferredIn += $transfersIn->sum('quantity_pushed');
                }

                $auditList[] = [
                    'stock_id' => $stock->id,
                    'barcode' => $stock->barcode ?? '-',
                    'abaya_code' => $stock->abaya_code,
                    'design_name' => $stock->design_name ?? $stock->abaya_code,
                    'quantity_added' => (int)$totalAdded,
                    'stock_addition' => (int)$totalAdded,
                    'quantity_pulled' => (int)$totalPulled,
                    'quantity_sold_pos' => (int)$totalPosSold,
                    'quantity_transferred_out' => (int)$totalTransferredOut,
                    'quantity_received' => (int)$totalTransferredIn,
                    'remaining_quantity' => (int)$totalRemaining,
                ];
            }

            // Paginate results
            $page = $request->input('page', 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $total = count($auditList);
            $paginated = array_slice($auditList, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated,
                'current_page' => (int)$page,
                'last_page' => (int)ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock audit details - transfers and POS sales with names
     */
    public function getStockAuditDetails(Request $request)
    {
        try {
            $stockId = $request->input('stock_id');
            $type = $request->input('type'); // 'added', 'pos', 'transferred'
            $locale = session('locale', 'en');

            $stock = Stock::findOrFail($stockId);
            $details = [];

            if ($type === 'transferred') {
                // Get all transfers OUT (to channels/boutiques) - these are the ones we want to show
                $transfersOut = \App\Models\TransferItemHistory::where('item_code', $stock->abaya_code)
                    ->where('quantity_pulled', '>', 0)
                    ->with('transfer')
                    ->get();

                foreach ($transfersOut as $transferItem) {
                    $transfer = $transferItem->transfer;
                    if (!$transfer) continue;

                    $toLocation = $transfer->to ?? '';
                    $locationName = $this->getLocationName($toLocation, $locale);

                    // Only include if transferred to a channel or boutique (not main)
                    // This shows where items were transferred TO
                    if ($toLocation !== 'main' && !empty($locationName)) {
                        $details[] = [
                            'name' => $locationName,
                            'quantity' => (int)$transferItem->quantity_pulled,
                            'date' => $transfer->date ? $transfer->date->format('Y-m-d') : '',
                            'type' => strpos($toLocation, 'boutique-') === 0 ? 'boutique' : 'channel',
                            'transfer_code' => $transfer->transfer_code ?? ''
                        ];
                    }
                }
                
                // Also get transfers IN (from channels/boutiques) if user clicked on "received" column
                // But for now, we'll show transfers OUT when type is 'transferred'
                // The "received" column can use the same type but we'll differentiate if needed
            } elseif ($type === 'pos') {
                // Get all POS sales with color and size
                $posSales = \App\Models\PosOrdersDetail::where('item_id', $stockId)
                    ->with(['color', 'size', 'order'])
                    ->get();

                // Group by order_no to show order_no and quantity
                foreach ($posSales as $posSale) {
                    $orderNo = $posSale->order ? ($posSale->order->order_no ?? '-') : '-';
                    
                    $details[] = [
                        'name' => $orderNo,
                        'order_no' => $orderNo,
                        'quantity' => (int)$posSale->item_quantity,
                        'date' => $posSale->order && $posSale->order->created_at ? $posSale->order->created_at->format('Y-m-d') : '',
                        'type' => 'pos'
                    ];
                }
                
                // Aggregate by order_no if there are duplicates
                $aggregated = [];
                foreach ($details as $detail) {
                    $key = $detail['order_no'];
                    if (!isset($aggregated[$key])) {
                        $aggregated[$key] = [
                            'name' => $detail['name'],
                            'order_no' => $detail['order_no'],
                            'quantity' => 0,
                            'date' => $detail['date'],
                            'type' => 'pos'
                        ];
                    }
                    $aggregated[$key]['quantity'] += $detail['quantity'];
                }
                $details = array_values($aggregated);
            } elseif ($type === 'received') {
                // Get all transfers IN (from channels/boutiques to main/warehouse)
                $transfersIn = \App\Models\TransferItemHistory::where('item_code', $stock->abaya_code)
                    ->where('quantity_pushed', '>', 0)
                    ->with('transfer')
                    ->get();

                foreach ($transfersIn as $transferItem) {
                    $transfer = $transferItem->transfer;
                    if (!$transfer) continue;

                    $fromLocation = $transfer->from ?? '';
                    $toLocation = $transfer->to ?? '';
                    
                    // Only include if transferred TO main warehouse FROM a channel or boutique
                    if ($toLocation === 'main' && $fromLocation !== 'main') {
                        $locationName = $this->getLocationName($fromLocation, $locale);
                        
                        if (!empty($locationName)) {
                            $details[] = [
                                'name' => $locationName,
                                'quantity' => (int)$transferItem->quantity_pushed,
                                'date' => $transfer->date ? $transfer->date->format('Y-m-d') : '',
                                'type' => strpos($fromLocation, 'boutique-') === 0 ? 'boutique' : 'channel'
                            ];
                        }
                    }
                }
            } elseif ($type === 'pulled') {
                // Get all pulled quantities from history
                $pulledHistory = StockHistory::where('stock_id', $stockId)
                    ->where('action_type', 2)
                    ->with(['size', 'color'])
                    ->orderBy('created_at', 'DESC')
                    ->get();

                foreach ($pulledHistory as $history) {
                    $sizeName = $history->size ? ($locale === 'ar' ? ($history->size->size_name_ar ?? $history->size->size_name_en) : ($history->size->size_name_en ?? $history->size->size_name_ar)) : '-';
                    $colorName = $history->color ? ($locale === 'ar' ? ($history->color->color_name_ar ?? $history->color->color_name_en) : ($history->color->color_name_en ?? $history->color->color_name_ar)) : '-';
                    
                    $details[] = [
                        'name' => ($sizeName !== '-' && $colorName !== '-') ? "{$sizeName} / {$colorName}" : ($sizeName !== '-' ? $sizeName : ($colorName !== '-' ? $colorName : 'N/A')),
                        'quantity' => (int)$history->changed_qty,
                        'date' => $history->created_at ? $history->created_at->format('Y-m-d H:i') : '',
                        'user' => $history->added_by ?? '-',
                        'reason' => $history->pull_notes ?? '-',
                    ];
                }
            } elseif ($type === 'added') {
                // Get all additions from stock history
                $additions = StockHistory::where('stock_id', $stockId)
                    ->where('action_type', 1)
                    ->with(['size', 'color'])
                    ->orderBy('created_at', 'DESC')
                    ->get();

                foreach ($additions as $addition) {
                    $sizeName = $addition->size ? ($locale === 'ar' ? ($addition->size->size_name_ar ?? $addition->size->size_name_en) : ($addition->size->size_name_en ?? $addition->size->size_name_ar)) : '';
                    $colorName = $addition->color ? ($locale === 'ar' ? ($addition->color->color_name_ar ?? $addition->color->color_name_en) : ($addition->color->color_name_en ?? $addition->color->color_name_ar)) : '';
                    
                    // Format name based on what's available
                    $name = '';
                    if (!empty($sizeName) && !empty($colorName)) {
                        $name = "{$sizeName} / {$colorName}";
                    } elseif (!empty($sizeName)) {
                        $name = $sizeName;
                    } elseif (!empty($colorName)) {
                        $name = $colorName;
                    } else {
                        $name = 'N/A';
                    }
                    
                    $details[] = [
                        'name' => $name,
                        'quantity' => (int)$addition->changed_qty,
                        'date' => $addition->created_at ? $addition->created_at->format('Y-m-d H:i') : '',
                        'added_by' => $addition->added_by ?? '-',
                        'added_on' => $addition->created_at ? $addition->created_at->format('Y-m-d H:i') : '',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $details
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to get location name (channel or boutique)
     */
    private function getLocationName($locationId, $locale = 'en')
    {
        if (empty($locationId) || $locationId === 'main') {
            return 'Main Warehouse';
        }

        // Check if it's a boutique
        if (strpos($locationId, 'boutique-') === 0) {
            $boutiqueId = str_replace('boutique-', '', $locationId);
            $boutique = \App\Models\Boutique::find($boutiqueId);
            return $boutique ? $boutique->boutique_name : $locationId;
        }

        // Check if it's a channel
        if (strpos($locationId, 'channel-') === 0) {
            $channelId = str_replace('channel-', '', $locationId);
            $channel = \App\Models\Channel::find($channelId);
            if ($channel) {
                return $locale == 'ar' ? ($channel->channel_name_ar ?? $channel->channel_name_en) : ($channel->channel_name_en ?? $channel->channel_name_ar);
            }
        }

        return $locationId;
    }
}
