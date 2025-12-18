<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Area;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index()
    {
        // Get areas and cities for the form dropdowns
        $areas = Area::orderBy('area_name_ar', 'ASC')->get(['id', 'area_name_ar', 'area_name_en']);
        $cities = City::orderBy('city_name_ar', 'ASC')->get(['id', 'city_name_ar', 'city_name_en', 'area_id']);
        
        return view('customers.customer', compact('areas', 'cities'));
    }

    public function getCustomers()
    {
        return Customer::with(['city', 'area'])
            ->orderBy('id', 'DESC')
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'notes' => 'nullable|string',
        ]);

        $customer = new Customer();
        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->city_id = $request->city_id;
        $customer->area_id = $request->area_id;
        $customer->notes = $request->notes;
        $customer->save();

        // Load relationships for response
        $customer->load(['city', 'area']);

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'notes' => 'nullable|string',
        ]);

        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->city_id = $request->city_id;
        $customer->area_id = $request->area_id;
        $customer->notes = $request->notes;
        $customer->save();

        // Load relationships for response
        $customer->load(['city', 'area']);

        return response()->json($customer);
    }

    public function show(Customer $customer)
    {
        $customer->load(['city', 'area']);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
