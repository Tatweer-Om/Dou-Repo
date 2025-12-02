<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use Illuminate\Http\Request;

class BoutiqueController extends Controller
{
 public function index() {
        return view('boutique.boutique');
    }


     public function boutique_profile() {
        return view('boutique.boutique_profile');
    }


public function getboutiques() {
    return Boutique::orderBy('id', 'DESC')->paginate(10);
}

public function show($id) {
    $boutique = Boutique::findOrFail($id);
    return response()->json($boutique);
}

  public function add_boutique(Request $request)
    {
        $boutique = new Boutique();

        $boutique->boutique_name = $request->boutique_name;
        $boutique->shelf_no = $request->shelf_no;
        $boutique->monthly_rent = $request->monthly_rent;
        $boutique->rent_date = $request->rent_date;
        $boutique->status = $request->status;
        $boutique->boutique_address = $request->boutique_address;
        $boutique->added_by_by = 'system';
        $boutique->user_id = 1;

        $boutique->save();
        return response()->json([
            'success' => true,
            'message' => 'Boutique saved successfully!'
        ]);
    }

    public function destroy($id)
{

    $boutique = Boutique::find($id);

    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ]);
    }

    try {
        $boutique->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.boutique_deleted')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('messages.delete_error')
        ]);
    }
}

    
    public function boutique_list(Request $request)
    {
       return view ('boutique.view_boutique');
    }

      public function edit_boutique($id)
    {
       
        $boutique = Boutique::find($id);

    return view('boutique.edit_boutique', compact('boutique'));
    }


  public function update_boutique(Request $request)
{
    $boutique_id = $request->boutique_id;
    $boutique = Boutique::find($boutique_id);

    $boutique->boutique_name = $request->boutique_name;
    $boutique->shelf_no = $request->shelf_no;
    $boutique->monthly_rent = $request->monthly_rent;
    $boutique->rent_date = $request->rent_date;
    $boutique->status = $request->status;
    $boutique->boutique_address = $request->boutique_address;
    $boutique->updated_by = 'system_update';

    $boutique->save();

    return response()->json([
        'success' => true,
        'message' => $request->boutique_id 
            ? __('messages.update_boutique') 
            : __('messages.add_boutique'),
        'boutique' => $boutique // ✅ include this
    ]);
}

}
