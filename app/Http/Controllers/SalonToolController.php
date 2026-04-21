<?php

namespace App\Http\Controllers;

use App\Models\SalonTool;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalonToolController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!SalonPermission::userHasAny($permissions, [SalonPermission::TOOLS])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $tools = SalonTool::latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('saloon.tools', compact('tools'))->render();
        }

        return view('saloon.tools', compact('tools'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        $tool = SalonTool::create([
            'name'     => $request->name,
            'price'    => $request->price,
            'notes'    => $request->notes,
            'added_by' => $userName,
            'user_id'  => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'tool_id' => $tool->id,
            'message' => 'Tool added successfully',
        ]);
    }

    public function show(SalonTool $salontool)
    {
        return response()->json($salontool);
    }

    public function update(Request $request, SalonTool $salontool)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        $salontool->name = $request->name;
        $salontool->price = $request->price;
        $salontool->notes = $request->notes;
        $salontool->updated_by = $userName;
        $salontool->save();

        return response()->json([
            'success' => true,
            'tool'    => $salontool,
            'message' => 'Tool updated successfully',
        ]);
    }

    public function destroy(SalonTool $salontool)
    {
        $salontool->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tool deleted successfully',
        ]);
    }
}
