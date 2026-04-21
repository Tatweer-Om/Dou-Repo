@extends('layouts.salon_header')

@section('main')
@push('title')
<title>Saloon Expense Report</title>
@endpush

<main class="min-h-screen bg-surface">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex flex-wrap justify-between items-center gap-3 w-full px-8 py-4 sticky top-0 z-10">
        <h1 class="text-lg font-headline font-bold text-on-surface">Saloon Expense Report</h1>
        <a href="{{ route('saloon_expense.index') }}"
            class="rounded-full border border-outline-variant/20 bg-white px-5 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low">
            Manage Expenses
        </a>
    </header>

    <div class="px-8 py-8 space-y-6">
        <section class="bg-white rounded-xl border border-outline-variant/20 p-4">
            <form method="GET" action="{{ route('saloon_expense.report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-on-surface-variant mb-1">Month</label>
                    <input type="month" name="month" value="{{ $monthInput }}" class="w-full rounded-lg border border-outline-variant/25 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-on-surface-variant mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="w-full rounded-lg border border-outline-variant/25 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-on-surface-variant mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="w-full rounded-lg border border-outline-variant/25 px-3 py-2 text-sm">
                </div>
                <div class="flex gap-2 md:col-span-2">
                    <button type="submit" class="rounded-lg bg-primary text-white px-4 py-2 text-sm font-bold">Apply Filter</button>
                    <a href="{{ route('saloon_expense.report') }}" class="rounded-lg border border-outline-variant/25 px-4 py-2 text-sm font-semibold">Reset</a>
                    <a href="{{ route('saloon_expense.report_export_excel', request()->query()) }}"
                        class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-bold">Export Excel</a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Selected Date Range</p>
                <p class="mt-1 text-base font-semibold">{{ $selectedRangeLabel }}</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Total Expense</p>
                <p class="mt-1 text-base font-semibold text-red-600">{{ number_format((float) $grandTotal, 3) }}</p>
            </div>
        </section>

        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[980px]">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">#</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Expense Name</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Category</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Amount</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Account</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Expense Date</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Receipt No</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Notes</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Added By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface">
                        @forelse($expenses as $index => $exp)
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-4 py-4 text-sm">{{ $expenses->firstItem() + $index }}</td>
                                <td class="px-4 py-4 text-sm font-medium">{{ $exp->expense_name }}</td>
                                <td class="px-4 py-4 text-sm">{{ $exp->category->category_name ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-right font-semibold text-red-600">{{ number_format((float) $exp->amount, 3) }}</td>
                                <td class="px-4 py-4 text-sm">{{ $exp->account->account_name ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm">{{ $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '—' }}</td>
                                <td class="px-4 py-4 text-sm">{{ $exp->reciept_no ?: '—' }}</td>
                                <td class="px-4 py-4 text-sm max-w-[220px] truncate" title="{{ $exp->notes }}">{{ $exp->notes ?: '—' }}</td>
                                <td class="px-4 py-4 text-sm">{{ $exp->added_by ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-6 text-center text-on-surface-variant">No expense records found for selected filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-4 border-t border-surface bg-white flex flex-wrap justify-between items-center gap-3">
                <div class="text-sm font-semibold">Total Expense: <span class="text-red-600">{{ number_format((float) $grandTotal, 3) }}</span></div>
                <div>{{ $expenses->links() }}</div>
            </div>
        </section>
    </div>
</main>

@include('layouts.salon_footer')
@endsection
