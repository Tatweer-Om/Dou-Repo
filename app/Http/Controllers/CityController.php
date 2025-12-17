<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CityController extends Controller
{
    public function index()
    {
        $areas = Area::orderBy('id', 'DESC')->get();
        return view('modules.city', compact('areas'));
    }

    public function getCities()
    {
        return City::with('area')->orderBy('id', 'DESC')->paginate(10);
    }

    /**
     * Return cities for a given area (for dropdowns)
     */
    public function byArea(Request $request)
    {
        $areaId = $request->query('area_id');
        if (!$areaId) {
            return response()->json([]);
        }

        return response()->json(
            City::where('area_id', $areaId)
                ->orderBy('city_name_ar', 'ASC')
                ->get(['id', 'area_id', 'city_name_en', 'city_name_ar'])
        );
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $city = new City();
        $city->area_id = $request->area_id;
        $city->city_name_en = $request->city_name_en;
        $city->city_name_ar = $request->city_name_ar;
        $city->delivery_charges = $request->delivery_charges ?? null;
        $city->notes = $request->notes;
        $city->added_by = $user->name ?? 'system';
        $city->user_id = $user->id ?? 1;

        $city->save();

        return response()->json($city->load('area'));
    }

    public function update(Request $request, City $city)
    {
        $user = Auth::user();

        $city->area_id = $request->area_id;
        $city->city_name_en = $request->city_name_en;
        $city->city_name_ar = $request->city_name_ar;
        $city->delivery_charges = $request->delivery_charges ?? null;
        $city->notes = $request->notes;
        $city->updated_by = $user->name ?? 'system_update';
        $city->save();

        return response()->json($city->load('area'));
    }

    public function show(City $city)
    {
        return response()->json($city->load('area'));
    }

    public function destroy(City $city)
    {
        $city->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

