<?php

namespace App\Http\Controllers;

use App\Models\SalonService;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SalonServiceController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!SalonPermission::userHasAny($permissions, [SalonPermission::SERVICE])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $service = SalonService::latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('saloon.service', compact('service'))->render();
        }

        return view('saloon.service', compact('service'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'price'      => 'required', 
            'notes'    => 'nullable|string',
         ]);

        $userId = Auth::id();
        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        

        $service = SalonService::create([
            'name'        => $request->name,
            'price'       => $request->price, 
            'notes'     => $request->notes,
            'added_by'    => $userName,
            'user_id'     => $userId,
        ]);

        return response()->json([
            'success'  => true,
            'service_id' => $service->id,
            'message'  => 'service added successfully',
        ]);
    }

    public function show(SalonService $salonservice)
    {
        return response()->json($salonservice);
    }

    public function update(Request $request, Salonservice $salonservice)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'price'      => 'required', 
            'notes'    => 'nullable|string',
         ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        

        $salonservice->name = $request->name;
        $salonservice->price = $request->price; 
        $salonservice->notes = $request->notes;
        $salonservice->updated_by = $userName;
        $salonservice->save();

        return response()->json([
            'success' => true,
            'service'   => $salonservice,
            'message' => 'service updated successfully',
        ]);
    }

    public function destroy(SalonService $salonservice)
    {
         

        $salonservice->delete();

        return response()->json([
            'success' => true,
            'message' => 'service deleted successfully',
        ]);
    }
}