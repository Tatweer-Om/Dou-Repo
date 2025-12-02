<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\History;
use App\Models\Wharehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

public function manage_quantity(){
    return view ('wharehouse.manage_quantity');

}

public function settlement(){
    return view ('wharehouse.settlement');
}

}