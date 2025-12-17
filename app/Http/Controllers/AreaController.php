<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    public function index()
    {
        return view('modules.area');
    }

    public function getAreas()
    {
        return Area::orderBy('id', 'DESC')->paginate(10);
    }

    /**
     * Return all areas (for dropdowns)
     */
    public function all()
    {
        return response()->json(
            Area::orderBy('area_name_ar', 'ASC')
                ->get(['id', 'area_name_en', 'area_name_ar'])
        );
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $area = new Area();
        $area->area_name_en = $request->area_name_en;
        $area->area_name_ar = $request->area_name_ar;
        $area->notes = $request->notes;
        $area->added_by = $user->name ?? 'system';
        $area->user_id = $user->id ?? 1;

        $area->save();

        return response()->json($area);
    }

    public function update(Request $request, Area $area)
    {
        $user = Auth::user();

        $area->area_name_en = $request->area_name_en;
        $area->area_name_ar = $request->area_name_ar;
        $area->notes = $request->notes;
        $area->updated_by = $user->name ?? 'system_update';
        $area->save();

        return response()->json($area);
    }

    public function show(Area $area)
    {
        return response()->json($area);
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

