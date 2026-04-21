<?php

namespace App\Http\Controllers;

use App\Models\SalonExpenseCategory;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalonExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!SalonPermission::userHasAny($permissions, [SalonPermission::EXPENSE_CATEGORY])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $categories = SalonExpenseCategory::latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('saloon.expense_category', compact('categories'))->render();
        }

        return view('saloon.expense_category', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'notes'         => 'nullable|string',
        ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        $category = SalonExpenseCategory::create([
            'category_name' => $request->category_name,
            'notes'         => $request->notes,
            'added_by'      => $userName,
            'user_id'       => Auth::id(),
        ]);

        return response()->json([
            'success'      => true,
            'category_id'  => $category->id,
            'message'      => 'Salon expense category added successfully',
        ]);
    }

    public function show(SalonExpenseCategory $salonExpenseCategory)
    {
        return response()->json($salonExpenseCategory);
    }

    public function update(Request $request, SalonExpenseCategory $salonExpenseCategory)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'notes'         => 'nullable|string',
        ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        $salonExpenseCategory->category_name = $request->category_name;
        $salonExpenseCategory->notes = $request->notes;
        $salonExpenseCategory->updated_by = $userName;
        $salonExpenseCategory->save();

        return response()->json([
            'success'  => true,
            'category' => $salonExpenseCategory,
            'message'  => 'Salon expense category updated successfully',
        ]);
    }

    public function destroy(SalonExpenseCategory $salonExpenseCategory)
    {
        $salonExpenseCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salon expense category deleted successfully',
        ]);
    }
}
