@extends('layouts.salon_header')

@section('main')
@push('title')
<title>Saloon Income Expense Report</title>
@endpush

<main class="min-h-screen bg-surface">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex flex-wrap justify-between items-center gap-3 w-full px-8 py-4 sticky top-0 z-10">
        <h1 class="text-lg font-headline font-bold text-on-surface">Income Expense Report</h1>
        <a href="{{ route('saloon_income_expense_report.export_excel', request()->query()) }}"
            class="rounded-full bg-emerald-600 text-white px-5 py-2 text-sm font-semibold hover:bg-emerald-700">
            Export Excel
        </a>
    </header>

    <div class="px-8 py-8 space-y-6">
        <section class="bg-white rounded-xl border border-outline-variant/20 p-4">
            <form method="GET" action="{{ route('saloon_income_expense_report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
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
                    <a href="{{ route('saloon_income_expense_report') }}" class="rounded-lg border border-outline-variant/25 px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4 md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Selected Date Range</p>
                <p class="mt-1 text-base font-semibold">{{ $selectedRangeLabel }}</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Amount Received</p>
                <p class="mt-1 text-base font-semibold text-emerald-700">{{ number_format((float) $summary['received_amount'], 3) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Amount Pending</p>
                <p class="mt-1 text-base font-semibold text-amber-700">{{ number_format((float) $summary['pending_amount'], 3) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Total Amount</p>
                <p class="mt-1 text-base font-semibold">{{ number_format((float) $summary['total_amount'], 3) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Expense</p>
                <p class="mt-1 text-base font-semibold text-red-600">{{ number_format((float) $summary['expense_amount'], 3) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant/20 p-4 md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">Total Income (Received - Expense)</p>
                <p class="mt-1 text-base font-semibold {{ $summary['income_amount'] < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ number_format((float) $summary['income_amount'], 3) }}</p>
            </div>
        </section>

        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1120px]">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Date</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Total Bookings</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Total Bookings Amount</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Amount Received</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Remaining Amount</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Expense</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Total Income</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface">
                        @forelse($rows as $r)
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-4 py-4 text-sm font-medium">{{ $r['date'] }}</td>
                                <td class="px-4 py-4 text-sm text-right">{{ $r['bookings_count'] }}</td>
                                <td class="px-4 py-4 text-sm text-right">{{ number_format((float) $r['total_amount'], 3) }}</td>
                                <td class="px-4 py-4 text-sm text-right text-emerald-700">{{ number_format((float) $r['received_amount'], 3) }}</td>
                                <td class="px-4 py-4 text-sm text-right text-amber-700">{{ number_format((float) $r['remaining_amount'], 3) }}</td>
                                <td class="px-4 py-4 text-sm text-right text-red-600">{{ number_format((float) $r['expense_amount'], 3) }}</td>
                                <td class="px-4 py-4 text-sm text-right font-semibold {{ $r['income_amount'] < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ number_format((float) $r['income_amount'], 3) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-6 text-center text-on-surface-variant">No records found for selected dates.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-8 py-4 border-t border-surface bg-white flex justify-end">
                {{ $rows->links() }}
            </div>
        </section>
    </div>
</main>

@include('layouts.salon_footer')
@endsection
