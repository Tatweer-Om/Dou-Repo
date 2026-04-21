@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.orders_sent_to_tailor', [], session('locale')) ?: 'Orders Sent to Tailor' }}</title>
@endpush

<main class="flex-1 p-6 bg-background-light dark:bg-background-dark overflow-y-auto">
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl sm:text-3xl font-bold text-[var(--text-primary)]">
        {{ trans('messages.orders_sent_to_tailor', [], session('locale')) ?: 'Orders Sent to Tailor' }}
      </h2>
    </div>

    <form method="GET" action="{{ route('orders_sent_to_tailor') }}"
          class="bg-white border border-[var(--border-color)] rounded-2xl shadow-sm p-4 sm:p-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">{{ trans('messages.sending_summary_number', [], session('locale')) }}</label>
          <input type="text" name="sending_summary_number"
                 value="{{ request('sending_summary_number') }}"
                 class="w-full h-10 rounded-lg border-gray-300 focus:ring-2 focus:ring-primary/50"
                 placeholder="{{ trans('messages.sending_summary_number', [], session('locale')) }}">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">{{ trans('messages.list_number', [], session('locale')) }}</label>
          <input type="text" name="list_number"
                 value="{{ request('list_number') }}"
                 class="w-full h-10 rounded-lg border-gray-300 focus:ring-2 focus:ring-primary/50"
                 placeholder="{{ trans('messages.list_number', [], session('locale')) }}">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">{{ trans('messages.tailor', [], session('locale')) }}</label>
          <select name="tailor_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-2 focus:ring-primary/50">
            <option value="">{{ trans('messages.select_tailor', [], session('locale')) }}</option>
            @foreach($tailors as $tailor)
              <option value="{{ $tailor->id }}" {{ (string) request('tailor_id') === (string) $tailor->id ? 'selected' : '' }}>
                {{ $tailor->tailor_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">{{ trans('messages.date', [], session('locale')) }}</label>
          <input type="date" name="date"
                 value="{{ request('date') }}"
                 class="w-full h-10 rounded-lg border-gray-300 focus:ring-2 focus:ring-primary/50">
        </div>

        <div class="flex items-end gap-2">
          <button type="submit" class="h-10 px-4 rounded-lg bg-primary text-white font-semibold hover:bg-primary/90">
            {{ trans('messages.search', [], session('locale')) }}
          </button>
          <a href="{{ route('orders_sent_to_tailor') }}" class="h-10 px-4 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 inline-flex items-center">
            {{ trans('messages.reset', [], session('locale')) ?: 'Reset' }}
          </a>
        </div>
      </div>
    </form>

    <div class="bg-white border border-[var(--border-color)] rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-right">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 font-semibold">{{ trans('messages.sending_summary_number', [], session('locale')) }}</th>
              <th class="px-4 py-3 font-semibold">{{ trans('messages.list_number', [], session('locale')) }}</th>
              <th class="px-4 py-3 font-semibold">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="px-4 py-3 font-semibold">{{ trans('messages.quantity', [], session('locale')) }}</th>
              <th class="px-4 py-3 font-semibold">{{ trans('messages.date', [], session('locale')) }}</th>
              <th class="px-4 py-3 font-semibold">{{ trans('messages.actions', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $row)
              <tr class="border-t">
                <td class="px-4 py-3 font-medium">{{ $row->sending_summary_number_display ?? ($row->sending_summary_number ?: 'No sending summary number') }}</td>
                <td class="px-4 py-3">{{ $row->list_number_display ?? ($row->list_number ?: 'No list number') }}</td>
                <td class="px-4 py-3">{{ $row->tailor_name ?: '-' }}</td>
                <td class="px-4 py-3">
                  <span class="font-semibold">{{ (int) $row->total_quantity }}</span>
                  <span class="text-xs text-gray-500">({{ (int) $row->items_count }} {{ trans('messages.items', [], session('locale')) ?: 'items' }})</span>
                </td>
                <td class="px-4 py-3">{{ $row->sent_at_formatted }}</td>
                <td class="px-4 py-3">
                  <a href="{{ $row->print_url }}" target="_blank"
                     class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    <span class="material-symbols-outlined text-sm">print</span>
                    {{ trans('messages.print', [], session('locale')) }}
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                  {{ trans('messages.no_orders_found', [], session('locale')) ?: 'No orders found' }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($rows->lastPage() > 1)
      @php
        $current = $rows->currentPage();
        $last = $rows->lastPage();
        $window = 2;
        $start = max(1, $current - $window);
        $end = min($last, $current + $window);
      @endphp
      <ul class="flex flex-wrap justify-center items-center gap-1.5 mt-4 list-none pl-0 max-w-full">
        <li class="shrink-0">
          @if($rows->onFirstPage())
            <span class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 opacity-40 pointer-events-none bg-gray-200 border-gray-200">
              &laquo; Prev
            </span>
          @else
            <a href="{{ $rows->appends(request()->query())->url($current - 1) }}"
               class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 bg-white hover:bg-gray-100 border-gray-200">
              &laquo; Prev
            </a>
          @endif
        </li>

        @if($last <= 7)
          @for($i = 1; $i <= $last; $i++)
            <li class="shrink-0">
              <a href="{{ $rows->appends(request()->query())->url($i) }}"
                 class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 {{ $current === $i ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' : 'bg-white hover:bg-gray-100 border-gray-200' }}">
                {{ $i }}
              </a>
            </li>
          @endfor
        @else
          @if($start > 1)
            <li class="shrink-0">
              <a href="{{ $rows->appends(request()->query())->url(1) }}"
                 class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 {{ $current === 1 ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' : 'bg-white hover:bg-gray-100 border-gray-200' }}">
                1
              </a>
            </li>
            @if($start > 2)
              <li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>
            @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
            <li class="shrink-0">
              <a href="{{ $rows->appends(request()->query())->url($i) }}"
                 class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 {{ $current === $i ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' : 'bg-white hover:bg-gray-100 border-gray-200' }}">
                {{ $i }}
              </a>
            </li>
          @endfor

          @if($end < $last)
            @if($end < $last - 1)
              <li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>
            @endif
            <li class="shrink-0">
              <a href="{{ $rows->appends(request()->query())->url($last) }}"
                 class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 {{ $current === $last ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' : 'bg-white hover:bg-gray-100 border-gray-200' }}">
                {{ $last }}
              </a>
            </li>
          @endif
        @endif

        <li class="shrink-0">
          @if($current >= $last)
            <span class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 opacity-40 pointer-events-none bg-gray-200 border-gray-200">
              Next &raquo;
            </span>
          @else
            <a href="{{ $rows->appends(request()->query())->url($current + 1) }}"
               class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 bg-white hover:bg-gray-100 border-gray-200">
              Next &raquo;
            </a>
          @endif
        </li>
      </ul>
    @endif
  </div>
</main>

@include('layouts.footer')
@endsection

