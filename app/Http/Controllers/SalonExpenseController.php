<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Balance;
use App\Models\SalonExpense;
use App\Models\SalonExpenseCategory;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SalonExpenseController extends Controller
{
    /**
     * @param  int[]  $requiredAny
     */
    private function ensureSalonPermission(array $requiredAny)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, $requiredAny)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return null;
    }

    private function buildExpenseDateFilter(Request $request): array
    {
        $monthInput = (string) $request->get('month', now()->format('Y-m'));
        $fromInput = trim((string) $request->get('from_date', ''));
        $toInput = trim((string) $request->get('to_date', ''));
        $fromDate = null;
        $toDate = null;

        if ($fromInput !== '' && $toInput !== '') {
            try {
                $fromDate = Carbon::parse($fromInput)->startOfDay();
                $toDate = Carbon::parse($toInput)->endOfDay();
                if ($toDate->lt($fromDate)) {
                    [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
                }
            } catch (\Throwable $e) {
                $fromDate = null;
                $toDate = null;
                $fromInput = '';
                $toInput = '';
            }
        }

        if (!$fromDate || !$toDate) {
            try {
                $monthStart = Carbon::parse($monthInput.'-01')->startOfMonth();
            } catch (\Throwable $e) {
                $monthStart = now()->startOfMonth();
                $monthInput = $monthStart->format('Y-m');
            }
            $fromDate = $monthStart->copy()->startOfDay();
            $toDate = $monthStart->copy()->endOfMonth()->endOfDay();
            $fromInput = '';
            $toInput = '';
        } else {
            $fromInput = $fromDate->toDateString();
            $toInput = $toDate->toDateString();
        }

        return [
            'month' => $monthInput,
            'from_input' => $fromInput,
            'to_input' => $toInput,
            'from' => $fromDate,
            'to' => $toDate,
            'label' => $fromDate->toDateString().' to '.$toDate->toDateString(),
        ];
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureSalonPermission([SalonPermission::EXPENSE])) {
            return $guard;
        }

        $categories = SalonExpenseCategory::orderBy('category_name', 'ASC')->get();
        $accounts = Account::orderBy('account_name', 'ASC')->get();

        $expenses = SalonExpense::with(['category', 'account', 'user'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('saloon.expense', compact('categories', 'accounts', 'expenses'))->render();
        }

        return view('saloon.expense', compact('categories', 'accounts', 'expenses'));
    }

    public function report(Request $request)
    {
        if ($guard = $this->ensureSalonPermission([SalonPermission::EXPENSE_REPORT])) {
            return $guard;
        }

        $filter = $this->buildExpenseDateFilter($request);

        $query = SalonExpense::query()
            ->with(['category', 'account', 'user'])
            ->whereBetween('expense_date', [$filter['from']->toDateString(), $filter['to']->toDateString()]);

        $grandTotal = round((float) (clone $query)->sum('amount'), 3);
        $expenses = $query
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('saloon.expense_report', [
            'expenses' => $expenses,
            'monthInput' => $filter['month'],
            'fromDate' => $filter['from_input'],
            'toDate' => $filter['to_input'],
            'selectedRangeLabel' => $filter['label'],
            'grandTotal' => $grandTotal,
        ]);
    }

    public function reportExportExcel(Request $request)
    {
        if ($guard = $this->ensureSalonPermission([SalonPermission::EXPENSE_REPORT])) {
            return $guard;
        }

        $filter = $this->buildExpenseDateFilter($request);
        $rows = SalonExpense::query()
            ->with(['category', 'account', 'user'])
            ->whereBetween('expense_date', [$filter['from']->toDateString(), $filter['to']->toDateString()])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $filename = 'saloon_expense_report_'.$filter['from']->format('Ymd').'_to_'.$filter['to']->format('Ymd').'.csv';
        $total = round((float) $rows->sum('amount'), 3);

        return response()->streamDownload(function () use ($rows, $filter, $total) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Saloon Expense Report']);
            fputcsv($out, ['Date Range', $filter['label']]);
            fputcsv($out, []);
            fputcsv($out, ['#', 'Expense Name', 'Category', 'Amount', 'Account', 'Expense Date', 'Receipt No', 'Notes', 'Added By']);

            foreach ($rows as $idx => $exp) {
                fputcsv($out, [
                    $idx + 1,
                    (string) ($exp->expense_name ?? ''),
                    (string) ($exp->category->category_name ?? ''),
                    number_format((float) $exp->amount, 3, '.', ''),
                    (string) ($exp->account->account_name ?? ''),
                    $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '',
                    (string) ($exp->reciept_no ?? ''),
                    (string) ($exp->notes ?? ''),
                    (string) ($exp->added_by ?? ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Total Expense', number_format($total, 3, '.', '')]);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('salon_expense_category_id') === '' || $request->input('salon_expense_category_id') === null) {
            $request->merge(['salon_expense_category_id' => null]);
        }

        $request->validate([
            'expense_name'               => 'required|string|max:255',
            'salon_expense_category_id'  => 'nullable|exists:salon_expense_categories,id',
            'amount'                     => 'required|numeric|min:0.001',
            'expense_date'               => 'required|date',
            'account_id'                 => 'required|exists:accounts,id',
            'reciept_no'                 => 'nullable|string|max:255',
            'notes'                      => 'nullable|string',
            'expense_file'               => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,bmp,pdf|max:2048',
        ]);

        $user_id = Auth::id();
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        $expense = new SalonExpense();
        $expense_file = '';

        if ($request->hasFile('expense_file')) {
            $folderPath = public_path('uploads/salon_expense_files');

            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            $expense_file = time() . '_' . uniqid() . '.' . $request->file('expense_file')->extension();
            $request->file('expense_file')->move($folderPath, $expense_file);
        }

        $expense->salon_expense_category_id = $request->salon_expense_category_id ?: null;
        $expense->supplier_id = $request->supplier_id ?: null;
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

        $account_data = Account::where('id', $request->account_id)->first();
        if ($account_data) {
            $opening_balance = $account_data->opening_balance ?? 0;
            $new_amount = $opening_balance - $request->amount;

            $account_data->opening_balance = $new_amount;
            $account_data->updated_by = $user_name;
            $account_data->save();

            $blnc = new Balance();
            $blnc->account_name = $account_data->account_name ?? '';
            $blnc->account_id = $account_data->id;
            $blnc->account_no = $account_data->account_no;
            $blnc->previous_balance = $opening_balance;
            $blnc->new_total_amount = $new_amount;
            $blnc->source = 'SalonExpense';
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
            'success'    => true,
            'expense_id' => $expense->id,
            'message'    => 'Salon expense added successfully',
        ]);
    }

    public function show(SalonExpense $salonExpense)
    {
        $salonExpense->load(['category', 'account', 'user']);

        return response()->json($salonExpense);
    }

    public function update(Request $request, SalonExpense $salonExpense)
    {
        if ($request->input('salon_expense_category_id') === '' || $request->input('salon_expense_category_id') === null) {
            $request->merge(['salon_expense_category_id' => null]);
        }

        $request->validate([
            'expense_name'               => 'required|string|max:255',
            'salon_expense_category_id'  => 'nullable|exists:salon_expense_categories,id',
            'amount'                     => 'required|numeric|min:0.001',
            'expense_date'               => 'required|date',
            'account_id'                 => 'required|exists:accounts,id',
            'reciept_no'                 => 'nullable|string|max:255',
            'notes'                      => 'nullable|string',
            'expense_file'               => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,bmp,pdf|max:2048',
        ]);

        $user_id = Auth::id();
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        $old_amount = $salonExpense->amount;
        $old_account_id = $salonExpense->payment_method;

        if ($request->hasFile('expense_file')) {
            if ($salonExpense->expense_image) {
                $oldFilePath = public_path('uploads/salon_expense_files/' . $salonExpense->expense_image);
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }

            $folderPath = public_path('uploads/salon_expense_files');
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            $expense_file = time() . '_' . uniqid() . '.' . $request->file('expense_file')->extension();
            $request->file('expense_file')->move($folderPath, $expense_file);
            $salonExpense->expense_image = $expense_file;
        }

        $salonExpense->salon_expense_category_id = $request->salon_expense_category_id ?: null;
        $salonExpense->supplier_id = $request->supplier_id ?: null;
        $salonExpense->reciept_no = $request->reciept_no;
        $salonExpense->expense_name = $request->expense_name;
        $salonExpense->payment_method = $request->account_id;
        $salonExpense->amount = $request->amount;
        $salonExpense->expense_date = $request->expense_date;
        $salonExpense->notes = $request->notes;
        $salonExpense->updated_by = $user_name;
        $salonExpense->save();

        if ($old_account_id != $request->account_id) {
            $old_account = Account::find($old_account_id);
            if ($old_account) {
                $old_account->opening_balance = ($old_account->opening_balance ?? 0) + $old_amount;
                $old_account->save();
            }

            $new_account = Account::find($request->account_id);
            if ($new_account) {
                $new_account->opening_balance = ($new_account->opening_balance ?? 0) - $request->amount;
                $new_account->save();
            }
        } else {
            $account = Account::find($request->account_id);
            if ($account) {
                $current_balance = $account->opening_balance ?? 0;
                $account->opening_balance = $current_balance + $old_amount - $request->amount;
                $account->save();
            }
        }

        return response()->json([
            'success' => true,
            'expense' => $salonExpense,
            'message' => 'Salon expense updated successfully',
        ]);
    }

    public function destroy(SalonExpense $salonExpense)
    {
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        $account = Account::find($salonExpense->payment_method);
        if ($account) {
            $account->opening_balance = ($account->opening_balance ?? 0) + $salonExpense->amount;
            $account->updated_by = $user_name;
            $account->save();
        }

        if ($salonExpense->expense_image) {
            $filePath = public_path('uploads/salon_expense_files/' . $salonExpense->expense_image);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $salonExpense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salon expense deleted successfully',
        ]);
    }
}
