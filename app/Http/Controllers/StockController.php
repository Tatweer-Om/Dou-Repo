<?php

namespace App\Http\Controllers;

use App\Models\Size;
use App\Models\Color;
use App\Models\Stock;
use App\Models\Tailor;
use App\Models\ColorSize;
use App\Models\StockSize;
use App\Models\StockColor;
use App\Models\StockImage;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class StockController extends Controller
{
    public function index()
    {

        $tailors = Tailor::all();
        $colors = Color::all();
        $sizes = Size::all();

        return view('stock.add_stock', compact('tailors', 'colors', 'sizes'));
    }

public function edit_stock($id)
{
    $tailors = Tailor::all();
    $colors  = Color::all();
    $sizes   = Size::all();


    $stock = Stock::with([
        'colors',    
        'sizes',        
        'colorSizes',  
        'images'      
    ])->findOrFail($id);

    $selectedTailors = json_decode($stock->tailor_id, true) ?? [];

    return view('stock.edit_stock', compact('tailors', 'colors', 'sizes', 'id', 'stock', 'selectedTailors'));
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
        return view('stock.view_stock');
    }

public function getstock()
{
    // Eager load relationships
    $stocks = Stock::with(['colors.color', 'sizes.size', 'images'])
                   ->orderBy('id', 'DESC')
                   ->paginate(10);

    return response()->json($stocks);
}





public function add_stock(Request $request)
{

    $tailor_ids = $request->input('tailor_id');
    $stock = new Stock();
    $stock->abaya_code         = $request->abaya_code;
    $stock->design_name        = $request->design_name;
    $stock->barcode            = $request->barcode;
    $stock->abaya_notes        = $request->abaya_notes;
    $stock->cost_price         = $request->cost_price;
    $stock->sales_price        = $request->sales_price;
    $stock->tailor_charges     = $request->tailor_charges;
    $stock->tailor_id          = json_encode($tailor_ids);
    $stock->quantity           = $request->total_quantity ?? null;
    $stock->notification_limit = $request->notification_limit;
    $stock->mode               = $request->mode;
    $stock->save();

    // ========= 1️⃣ Save Color Only =========
    if (!empty($request->colors)) {
        foreach ($request->colors as $color) {
            $stockColor = new StockColor();
            $stockColor->stock_id = $stock->id;
            $stockColor->color_id = $color['color_id'];
            $stockColor->qty      = $color['qty'] ?? 0;
            $stockColor->save();
        }
    }

    // ========= 2️⃣ Save Size Only =========
    if (!empty($request->sizes)) {
        foreach ($request->sizes as $size_id => $size) {
            $stockSize = new StockSize();
            $stockSize->stock_id = $stock->id;
            $stockSize->size_id  = $size_id;
            $stockSize->qty      = $size['qty'] ?? 0;
            $stockSize->save();
        }
    }

    // ========= 3️⃣ Save Color + Size =========
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
        <div class="card h-100 border-0 shadow-sm hover-shadow transition">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-light text-dark fs-6"> <strong>' . htmlspecialchars($size_name) . '</strong>
                        <input type="hidden" name="stock_size_id[]" value="' . htmlspecialchars($item->size_id) . '">

                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle border border-2"
                              style="width:40px;height:40px;background-color:' . htmlspecialchars($color_code) . ';"></div>
                        <span class="fw-semibold">' . htmlspecialchars($color_name) . '</span>
                                            <input type="hidden" name="stock_color_id[]" value="' . htmlspecialchars($item->color_id) . '">

                    </div>
                </div>
                <input type="number" min="0" value="' . htmlspecialchars($qty) . '" name="size_color_qty[]"
                       class="form-control form-control-lg text-center rounded-pill"
                       placeholder="0">
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
                        placeholder="0">
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
                    placeholder="0">
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

    // ---------------------------
    // 1) SIZE BASED QTY
    // ---------------------------
    if ($request->filled('size_id')) {
        foreach ($request->size_id as $i => $sizeId) {

            $item = StockSize::where('stock_id', $stock_id)
                             ->where('size_id', $sizeId)
                             ->first();

            if (!$item) continue;

            $old = $item->qty;
            $change = (int) $request->size_qty[$i];
            $new = $isPull ? $old - $change : $old + $change;

            $item->update(['qty' => $new]);

            StockHistory::create([
                'stock_id'    => $stock_id,
                'size_id'     => $sizeId,
                'color_id'    => null,
                'old_qty'     => $old,
                'changed_qty' => $change,
                'new_qty'     => $new,
                'action_type' => $actionType,
            ]);
        }
    }

    // ---------------------------
    // 2) COLOR BASED QTY
    // ---------------------------
    if ($request->filled('color_id')) {
        foreach ($request->color_id as $i => $colorId) {

            $item = StockColor::where('stock_id', $stock_id)
                              ->where('color_id', $colorId)
                              ->first();

            if (!$item) continue;

            $old = $item->qty;
            $change = (int) $request->color_qty[$i];
            $new = $isPull ? $old - $change : $old + $change;

            $item->update(['qty' => $new]);

            StockHistory::create([
                'stock_id'    => $stock_id,
                'size_id'     => null,
                'color_id'    => $colorId,
                'old_qty'     => $old,
                'changed_qty' => $change,
                'new_qty'     => $new,
                'action_type' => $actionType,
            ]);
        }
    }

    // ---------------------------
    // 3) SIZE + COLOR (ColorSize)
    // ---------------------------
    if ($request->filled('stock_size_id')) {

        foreach ($request->stock_size_id as $i => $sizeId) {

            $colorId = $request->stock_color_id[$i];

            $item = ColorSize::where('size_id', $sizeId)
                             ->where('color_id', $colorId)
                             ->first();

            if (!$item) continue;

            $old = $item->qty;
            $change = (int) $request->size_color_qty[$i];
            $new = $isPull ? $old - $change : $old + $change;

            $item->update(['qty' => $new]);

            StockHistory::create([
                'stock_id'    => $stock_id,
                'size_id'     => $sizeId,
                'color_id'    => $colorId,
                'old_qty'     => $old,
                'changed_qty' => $change,
                'new_qty'     => $new,
                'action_type' => $actionType,
            ]);
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => $isPull ? 'Quantity pulled!' : 'Quantity added!'
    ]);
}



}
