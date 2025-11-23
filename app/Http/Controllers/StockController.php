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

    // Load stock with related data
    $stock = Stock::with([
        'colors',       // StockColor
        'sizes',        // StockSize
        'colorSizes',   // Color + Size combinations
        'images'        // Stock images
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

// app/Http/Controllers/StockController.php
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
$stock->tailor_id = json_encode($request->tailor_id);
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



public function details(Request $request)
{

    $stock = Stock::with(['colors.color', 'sizes.size', 'images'])
                  ->findOrFail($request->id);

    return response()->json([
        'data' => $stock
    ]);
}


}
