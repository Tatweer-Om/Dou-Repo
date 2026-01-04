<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ExpenseController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(3, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $categories = ExpenseCategory::orderBy('category_name', 'ASC')->get();
        $accounts = Account::orderBy('account_name', 'ASC')->get();
        
        return view('modules.expense', compact('categories', 'accounts'));
    }

    public function getExpenses()
    {
        $expenses = Expense::with(['category', 'account', 'branch', 'user'])
            ->orderBy('id', 'DESC')
            ->paginate(10);
        
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $user_id = Auth::id();
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        $expense = new Expense();
        $expense_file = "";

        // Handle the file upload
        if ($request->hasFile('expense_file')) {
            $folderPath = public_path('uploads/expense_files');

            // Check if the folder exists, if not create it
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            // Create a unique filename
            $expense_file = time() . '.' . $request->file('expense_file')->extension();
            $request->file('expense_file')->move($folderPath, $expense_file);
        }

        // Save expense details
        $expense->category_id = $request->category_id;
        $expense->supplier_id = $request->supplier_id;
        $expense->reciept_no = $request->reciept_no;
        $expense->expense_name = $request->expense_name;
        $expense->payment_method = $request->account_id;
        $expense->amount = $request->amount;
        $expense->expense_date = $request->expense_date;
        $expense->notes = $request->notes;
        $expense->expense_image = $expense_file;
        $expense->added_by = $user_name;
        $expense->user_id = $user_id;
        $expense->save();

        // Handle account data
        $account_data = Account::where('id', $request->account_id)->first();
        if ($account_data) {
            $opening_balance = $account_data->opening_balance ?? 0;
            // Subtract the amount from account balance
            $new_amount = $opening_balance - $request->amount;

            // Update account balance
            $account_data->opening_balance = $new_amount;
            $account_data->updated_by = $user_name;
            $account_data->save();

            // Create a balance entry
            $blnc = new Balance();
            $blnc->account_name = $account_data->account_name ?? '';
            $blnc->account_id = $account_data->id;
            $blnc->account_no = $account_data->account_no;
            $blnc->previous_balance = $opening_balance;
            $blnc->new_total_amount = $new_amount;
            $blnc->source = 'Expense';
            $blnc->expense_amount = $expense->amount;
            $blnc->expense_name = $expense->expense_name;
            $blnc->expense_date = $expense->expense_date;
            $blnc->expense_added_by = $user_name;
            $blnc->expense_image = $expense->expense_image;
            $blnc->notes = $expense->notes;
            $blnc->added_by = $user_name;
            $blnc->user_id = $user_id;
            $blnc->save();
        }

        return response()->json([
            'success' => true,
            'expense_id' => $expense->id,
            'message' => 'Expense added successfully'
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $user_id = Auth::id();
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        // Get old amount for account balance adjustment
        $old_amount = $expense->amount;
        $old_account_id = $expense->payment_method;

        // Handle the file upload
        if ($request->hasFile('expense_file')) {
            // Delete old file if exists
            if ($expense->expense_image) {
                $oldFilePath = public_path('uploads/expense_files/' . $expense->expense_image);
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }

            $folderPath = public_path('uploads/expense_files');
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            $expense_file = time() . '.' . $request->file('expense_file')->extension();
            $request->file('expense_file')->move($folderPath, $expense_file);
            $expense->expense_image = $expense_file;
        }

        // Update expense details
        $expense->category_id = $request->category_id;
        $expense->supplier_id = $request->supplier_id;
        $expense->reciept_no = $request->reciept_no;
        $expense->expense_name = $request->expense_name;
        $expense->payment_method = $request->account_id;
        $expense->amount = $request->amount;
        $expense->expense_date = $request->expense_date;
        $expense->notes = $request->notes;
        $expense->updated_by = $user_name;
        $expense->save();

        // Handle account balance adjustment
        // If account changed, reverse old transaction and apply new one
        if ($old_account_id != $request->account_id) {
            // Reverse old account balance
            $old_account = Account::find($old_account_id);
            if ($old_account) {
                $old_account->opening_balance = ($old_account->opening_balance ?? 0) + $old_amount;
                $old_account->save();
            }

            // Apply to new account
            $new_account = Account::find($request->account_id);
            if ($new_account) {
                $new_account->opening_balance = ($new_account->opening_balance ?? 0) - $request->amount;
                $new_account->save();
            }
        } else {
            // Same account, adjust balance
            $account = Account::find($request->account_id);
            if ($account) {
                $current_balance = $account->opening_balance ?? 0;
                // Reverse old amount, subtract new amount
                $account->opening_balance = $current_balance + $old_amount - $request->amount;
                $account->save();
            }
        }

        return response()->json([
            'success' => true,
            'expense' => $expense,
            'message' => 'Expense updated successfully'
        ]);
    }

    public function show(Expense $expense)
    {
        $expense->load(['category', 'account', 'branch', 'user']);
        return response()->json($expense);
    }

    public function destroy(Expense $expense)
    {
        $user_id = Auth::id();
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        // Reverse account balance
        $account = Account::find($expense->payment_method);
        if ($account) {
            $account->opening_balance = ($account->opening_balance ?? 0) + $expense->amount;
            $account->updated_by = $user_name;
            $account->save();
        }

        // Delete file if exists
        if ($expense->expense_image) {
            $filePath = public_path('uploads/expense_files/' . $expense->expense_image);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
    }
}

