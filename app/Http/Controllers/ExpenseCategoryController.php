<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        return view('modules.expense_category');
    }

    public function getExpenseCategories()
    {
        return ExpenseCategory::orderBy('id', 'DESC')->paginate(10);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $expenseCategory = new ExpenseCategory();
        $expenseCategory->category_name = $request->category_name;
        $expenseCategory->notes = $request->notes;
        $expenseCategory->added_by = $user->user_name ?? 'system';
        $expenseCategory->user_id = $user->id ?? 1;

        $expenseCategory->save();

        return response()->json($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $user = Auth::user();

        $expenseCategory->category_name = $request->category_name;
        $expenseCategory->notes = $request->notes;
        $expenseCategory->updated_by = $user->user_name ?? 'system_update';
        $expenseCategory->save();

        return response()->json($expenseCategory);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

