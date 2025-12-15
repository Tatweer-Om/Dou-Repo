<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\History;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class MaterialController extends Controller
{
     


   public function edit_material($id) {
    $material = Material::where('id', $id)->first();
    return view('stock.edit_material', compact('material'));
}

public function getmaterial()
{
    // Eager load relationships
    $material = Material::
                   orderBy('id', 'DESC')
                   ->paginate(10);

    return response()->json($material);
}


  public function add_material(Request $request)
{

    $user = Auth::user();

    $material_image = null;
    if ($request->hasFile('material_image')) {
        $folderPath = public_path('images/materials');

       if (!File::isDirectory($folderPath)) { File::makeDirectory($folderPath, 0777, true, true); }

        $material_image = time() . '.' . $request->file('material_image')->extension();
        $request->file('material_image')->move($folderPath, $material_image);
    }

    // Save material
    $material = new Material();
    $material->material_name   = $request->material_name;
    $material->description     = $request->material_notes;
    $material->unit            = $request->material_unit;
    $material->category        = $request->material_category;
    $material->buy_price       = $request->purchase_price;
    $material->sell_price      = $request->sale_price;
    $material->rolls_count     = $request->roll_count;
    $material->meters_per_roll = $request->meter_per_roll;
    $material->material_image  = $material_image;
    $material->added_by        = 'system';
    $material->user_id         = 1;

    $material->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Material added successfully!',
        'material_id' => $material->id
    ]);
}



     public function view_material()
    {
        return view('stock.view_material');
    }
 
 public function update_material(Request $request)
{

    $material_id = $request->material_id;
    $material = Material::findOrFail($material_id);
    $user = Auth::user();

    // Update material image only if uploaded
    if ($request->hasFile('material_image')) {
        $folderPath = public_path('images/materials');
        $material_image = time() . '.' . $request->file('material_image')->extension();
        $request->file('material_image')->move($folderPath, $material_image);
        $material->material_image = $material_image;
    }

    // Update fields
    $material->material_name   = $request->material_name;
    $material->description     = $request->material_notes;
    $material->unit            = $request->material_unit;
    $material->category        = $request->material_category;
    $material->buy_price       = $request->purchase_price;
    $material->sell_price      = $request->sale_price;
    $material->rolls_count     = $request->roll_count;
    $material->meters_per_roll = $request->meter_per_roll;

    // Keep track of user
    $material->added_by = $user->name ?? 'system';
    $material->user_id  = $user->id ?? 1;

    $material->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Material updated successfully!',
        'material_id' => $material->id
    ]);
}


public function delete_material($id)
{


    $material = Material::find($id);

    if (!$material) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Material not found'
        ], 404);
    }

    // Delete material
    $material->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'Material deleted successfully!'
    ]);
}

public function getAllMaterials()
{
    $materials = Material::select('id', 'material_name', 'unit', 'category')
        ->orderBy('material_name', 'ASC')
        ->get();
    
    return response()->json($materials);
}

public function getMaterial33($id)
{
    $material = Material::find($id);
    
    if (!$material) {
        return response()->json([
            'status' => 'error',
            'message' => 'Material not found'
        ], 404);
    }
    
    return response()->json([
        'status' => 'success',
        'material' => $material
    ]);
}
}
