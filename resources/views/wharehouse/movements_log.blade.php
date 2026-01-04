@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.movements_log', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6">
  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.movements_log', [], session('locale')) }}</h2>
        <p class="text-gray-500 text-sm">{{ trans('messages.search_by_operation_number', [], session('locale')) }}</p>
      </div>
      <a href="{{url('manage_quantity')}}"
         class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white hover:opacity-90 font-semibold">
        {{ trans('messages.back_to_inventory', [], session('locale')) }}
      </a>
    </div>

    <!-- Filters -->
    <section class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <form method="GET" action="{{url('movements_log')}}" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px]">
          <input type="search" 
                 name="search" 
                 value="{{ $search }}"
                 class="w-full h-10 px-3 border border-pink-200 rounded-lg"
                 placeholder="{{ trans('messages.search_by_operation_number', [], session('locale')) }}">
        </div>
        <div>
          <input type="date" 
                 name="date_from" 
                 value="{{ $dateFrom }}"
                 class="h-10 px-2 border border-pink-200 rounded-lg">
        </div>
        <div>
          <input type="date" 
                 name="date_to" 
                 value="{{ $dateTo }}"
                 class="h-10 px-2 border border-pink-200 rounded-lg">
        </div>
        <button type="submit" 
                class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white hover:opacity-90 font-semibold">
          {{ trans('messages.search', [], session('locale')) }}
        </button>
        <a href="{{url('movements_log')}}" 
           class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold">
          {{ trans('messages.filter', [], session('locale')) }}
        </a>
      </form>
    </section>

    <!-- Movements Log Table -->
    <section class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
            <tr>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.operation_number', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.date', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.from', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.to', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.number_of_items', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.details', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            @if($transfers->count() > 0)
              @foreach($transfers as $transfer)
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 font-semibold">{{ $transfer['no'] }}</td>
                  <td class="px-3 py-2">{{ $transfer['date'] }}</td>
                  <td class="px-3 py-2">{{ $transfer['from'] }}</td>
                  <td class="px-3 py-2">{{ $transfer['to'] }}</td>
                  <td class="px-3 py-2">{{ $transfer['total'] }}</td>
                  <td class="px-3 py-2 text-center">
                    <button onclick="openDetails({{ json_encode($transfer) }})"
                            class="px-3 py-1 rounded-lg bg-pink-100 hover:bg-pink-200 text-[var(--primary-color)] text-xs font-semibold">
                      {{ trans('messages.view_details', [], session('locale')) }}
                    </button>
                  </td>
                </tr>
              @endforeach
            @else
              <tr>
                <td colspan="6" class="text-center text-gray-400 py-8">
                  <span class="material-symbols-outlined text-4xl">history</span>
                  <div>{{ trans('messages.no_movements_yet', [], session('locale')) }}</div>
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600">
          {{ trans('messages.showing', [], session('locale')) }} 
          {{ $transfers->firstItem() ?? 0 }} 
          {{ trans('messages.of', [], session('locale')) }} 
          {{ $transfers->total() }}
        </div>
        <div class="flex gap-2">
          @if($transfers->onFirstPage())
            <span class="px-4 py-2 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
              {{ trans('messages.previous', [], session('locale')) }}
            </span>
          @else
            <a href="{{ $transfers->previousPageUrl() }}" 
               class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold">
              {{ trans('messages.previous', [], session('locale')) }}
            </a>
          @endif

          @php
            $currentPage = $transfers->currentPage();
            $lastPage = $transfers->lastPage();
            $startPage = max(1, $currentPage - 2);
            $endPage = min($lastPage, $currentPage + 2);
          @endphp

          @if($startPage > 1)
            <a href="{{ $transfers->url(1) }}" 
               class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold">
              1
            </a>
            @if($startPage > 2)
              <span class="px-2 text-gray-400">...</span>
            @endif
          @endif

          @for($page = $startPage; $page <= $endPage; $page++)
            @if($page == $currentPage)
              <span class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white font-semibold">
                {{ $page }}
              </span>
            @else
              <a href="{{ $transfers->url($page) }}" 
                 class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold">
                {{ $page }}
              </a>
            @endif
          @endfor

          @if($endPage < $lastPage)
            @if($endPage < $lastPage - 1)
              <span class="px-2 text-gray-400">...</span>
            @endif
            <a href="{{ $transfers->url($lastPage) }}" 
               class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold">
              {{ $lastPage }}
            </a>
          @endif

          @if($transfers->hasMorePages())
            <a href="{{ $transfers->nextPageUrl() }}" 
               class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold">
              {{ trans('messages.next', [], session('locale')) }}
            </a>
          @else
            <span class="px-4 py-2 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
              {{ trans('messages.next', [], session('locale')) }}
            </span>
          @endif
        </div>
      </div>
    </section>

  </div>
</main>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" onclick="closeDetails()">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden" onclick="event.stopPropagation()">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold text-[var(--primary-color)]">
          {{ trans('messages.operation_details', [], session('locale')) }}
        </h3>
        <button onclick="closeDetails()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
      </div>
      <div class="p-4 max-h-[70vh] overflow-y-auto">
        <div id="detailsContent"></div>
      </div>
    </div>
  </div>
</div>



@endsection

