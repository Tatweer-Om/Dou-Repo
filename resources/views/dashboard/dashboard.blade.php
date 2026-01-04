@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.dashboard', [], session('locale')) }}</title>
@endpush
<script>
    // English comments only (as requested)
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    arabic: ["IBM Plex Sans Arabic", "system-ui", "sans-serif"],
                },
                boxShadow: {
                    soft: "0 10px 30px rgba(0,0,0,.06)",
                }
            }
        }
    }
</script>

<style>
      @media (prefers-color-scheme: dark) {
        .dark\:text-gray-200 {
            --tw-text-opacity: 1;
            color: #1F2937!important;
        }
    }
   

    /* Blinking alert border */
    .blink-danger {
        animation: blinkBorder 1.1s ease-in-out infinite;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, .0);
    }

    @keyframes blinkBorder {
        0% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .0);
            border-color: rgba(239, 68, 68, .35);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(239, 68, 68, .14);
            border-color: rgba(239, 68, 68, .95);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .0);
            border-color: rgba(239, 68, 68, .35);
        }
    }

    /* Hide elements during print */
    @media print {
        .no-print {
            display: none !important;
        }

        /*body {*/
        /*    background: #fff;*/
        /*}*/

        .print-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
    :root {
        --bg: #f7f7fb;
        --card: #ffffff;
        --text: #333;
        --muted: #6b7280;
        --border: rgba(0, 0, 0, .06);
        --primary: #b34b8a;
         premium rose 
        --primary2: #6d5bd0;
         soft violet 
        --gold: #b68a2c;
         warm gold 
        --danger: #ef4444;
        --dangerSoft: rgba(239, 68, 68, .12);
        --ok: #10b981;
        --okSoft: rgba(16, 185, 129, .12);
        --warn: #f59e0b;
        --warnSoft: rgba(245, 158, 11, .14);
    }

    body {
        font-family: var(--tw-fontFamily-arabic);
        background: var(--bg);
        color: var(--text);
    }

    /* Blinking alert border */
    .blink-danger {
        animation: blinkBorder 1.1s ease-in-out infinite;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, .0);
    }

    @keyframes blinkBorder {
        0% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .0);
            border-color: rgba(239, 68, 68, .35);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(239, 68, 68, .14);
            border-color: rgba(239, 68, 68, .95);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .0);
            border-color: rgba(239, 68, 68, .35);
        }
    }

    /* Hide elements during print */
    @media print {
        .no-print {
            display: none !important;
        }

        /*body {*/
        /*    background: #fff;*/
        /*}*/

        .print-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>
<!--</head>-->

<!--<body class="min-h-screen">-->
    <!-- =========================
       HEADER (included)
  ========================== -->


    <!-- =========================
       PAGE
  ========================== -->
    <main class="flex-1 p-6 space-y-6">

        <!-- Top row: KPI boxes -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Net Profit Today -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.net_profit_today', [], session('locale')) ?: 'Net Profit Today' }}</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ number_format($todayNetProfit ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ trans('messages.revenue', [], session('locale')) ?: 'Revenue' }}: {{ number_format($todayRevenue ?? 0, 3) }} • 
                            {{ trans('messages.expenses', [], session('locale')) ?: 'Expenses' }}: {{ number_format($todayExpenses ?? 0, 3) }}
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(16,185,129,.12);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--ok);">trending_up</span>
                    </div>
                </div>
            </div>

            <!-- Net Profit This Month -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.net_profit_this_month', [], session('locale')) ?: 'Net Profit This Month' }}</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ number_format($monthNetProfit ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trans('messages.current_month', [], session('locale')) ?: 'Current Month' }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(109,91,208,.12);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--primary2);">account_balance</span>
                    </div>
                </div>
            </div>

            <!-- Revenue - Expense -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.revenue_minus_expense', [], session('locale')) ?: 'Revenue - Expense' }}</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ number_format($revenueMinusExpense ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ trans('messages.total_revenue', [], session('locale')) ?: 'Total Revenue' }}: {{ number_format($totalRevenue ?? 0, 3) }} • 
                            {{ trans('messages.total_expenses', [], session('locale')) ?: 'Total Expenses' }}: {{ number_format($totalExpenses ?? 0, 3) }}
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(179,75,138,.12);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--primary);">payments</span>
                    </div>
                </div>
            </div>

            <!-- Orders with Tailor -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.orders_with_tailor', [], session('locale')) ?: 'Orders with Tailor' }}</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ $ordersWithTailor ?? 0 }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trans('messages.in_progress_at_tailors', [], session('locale')) }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(182,138,44,.14);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--gold);">content_cut</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Chart + Right panels -->
        <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <!-- Yearly Revenue vs Expenses -->
            <div class="xl:col-span-2 bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">

                <!-- Header -->
                <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--primary2);">
                            bar_chart
                        </span>
                        <div>
                            <h2 class="font-bold text-base sm:text-lg">{{ trans('messages.annual_revenue_expenses', [], session('locale')) }}</h2>
                            <p class="text-xs text-gray-500">{{ trans('messages.monthly_financial_performance', [], session('locale')) }}</p>
                        </div>
                    </div>

                    <div class="no-print flex items-center gap-2">
                        <select id="yearSelector" class="rounded-xl border border-[var(--border)] bg-white px-3 py-2 text-sm" onchange="updateChart()">
                            <option value="{{ $currentYear }}" selected>{{ $currentYear }}</option>
                            <option value="{{ $currentYear - 1 }}">{{ $currentYear - 1 }}</option>
                            @if($currentYear > 2023)
                            <option value="{{ $currentYear - 2 }}">{{ $currentYear - 2 }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Chart -->
                <div class="relative h-[360px]">
                    <canvas id="yearlyBarChart"></canvas>
                </div>

            </div>


            <!-- Side panels -->
            <div class="space-y-4">
                <!-- Late deliveries (blinking red) -->
                <div class="bg-[var(--card)] border rounded-2xl p-4 shadow-soft print-card blink-danger"
                    style="border-color: rgba(239,68,68,.55); background: linear-gradient(0deg, rgba(239,68,68,.06), rgba(255,255,255,1));">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]" style="color: var(--danger);">error</span>
                            <h3 class="font-bold">{{ trans('messages.late_delivery', [], session('locale')) }}</h3>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full" style="background: var(--dangerSoft); color: var(--danger);">{{ trans('messages.urgent_alert', [], session('locale')) }}</span>
                    </div>

                    <div class="mt-3 space-y-2 text-sm" id="lateDeliveriesList">
                        <!-- Items will be loaded dynamically -->
                        <div class="text-center text-gray-500 py-4">{{ trans('messages.loading', [], session('locale')) }}...</div>
                    </div>
                </div>

                <!-- Under tailoring list -->
                <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]" style="color: var(--gold);">content_cut</span>
                            <h3 class="font-bold">{{ trans('messages.abayas_under_tailoring', [], session('locale')) }}</h3>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full" style="background: var(--warnSoft); color: var(--warn);">{{ trans('messages.live', [], session('locale')) }}</span>
                    </div>

                    <div class="mt-3 space-y-2 text-sm" id="abayasUnderTailoringList">
                        <div class="text-center text-gray-500 py-4">{{ trans('messages.loading', [], session('locale')) }}...</div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Rent reminders + Low stock -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <!-- Rent reminders -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]" style="color: var(--primary);">apartment</span>
                        <h3 class="font-bold">{{ trans('messages.boutique_rent_reminders', [], session('locale')) }}</h3>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full" style="background: rgba(179,75,138,.12); color: var(--primary);">{{ trans('messages.monthly', [], session('locale')) }}</span>
                </div>

                <div class="mt-3 overflow-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500">
                                <th class="text-center py-2">{{ trans('messages.branch', [], session('locale')) }}</th>
                                <th class="text-center py-2">{{ trans('messages.month', [], session('locale')) }}</th>
                                <th class="text-center py-2">{{ trans('messages.amount', [], session('locale')) }}</th>
                                <th class="text-center py-2">{{ trans('messages.payment_date', [], session('locale')) }}</th>
                                <th class="text-center py-2">{{ trans('messages.days_remaining', [], session('locale')) ?: 'Days Remaining' }}</th>
                                <th class="text-center py-2">{{ trans('messages.status', [], session('locale')) }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]" id="boutiqueRentRemindersList">
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-500">{{ trans('messages.loading', [], session('locale')) }}...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Low stock alerts -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]" style="color: var(--warn);">inventory_2</span>
                        <h3 class="font-bold">{{ trans('messages.low_stock_alert', [], session('locale')) }}</h3>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full" style="background: var(--warnSoft); color: var(--warn);">{{ trans('messages.important', [], session('locale')) }}</span>
                </div>

                <div class="mt-3 space-y-2 text-sm" id="lowStockItemsList">
                    <div class="text-center text-gray-500 py-4">{{ trans('messages.loading', [], session('locale')) }}...</div>
                </div>
            </div>
        </section>

        <!-- Main table: all current orders (full width) -->

        <section class="bg-[var(--card)] border border-[var(--border)] rounded-2xl shadow-soft print-card">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-[var(--border)]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]" style="color: var(--primary2);">table_view</span>
                    <h2 class="font-bold text-base sm:text-lg">{{ trans('messages.current_orders', [], session('locale')) }}</h2>
                    <span class="text-xs text-gray-500 hidden sm:inline">
                        ({{ trans('messages.website', [], session('locale')) }} / {{ trans('messages.whatsapp', [], session('locale')) }})
                    </span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-[260px]">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">
                            search
                        </span>
                        <input
                            oninput="filterOrders()"
                            class="w-full rounded-xl border border-[var(--border)] bg-white pr-9 pl-3 py-2 text-sm"
                            placeholder="{{ trans('messages.quick_search', [], session('locale')) }}..." />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 bg-gray-50">
                            <th class="py-3 px-4 text-center">{{ trans('messages.order_number', [], session('locale')) }}</th>
                            <th class="py-3 px-4 text-center">{{ trans('messages.source', [], session('locale')) }}</th>
                            <th class="py-3 px-4 text-center">{{ trans('messages.customer', [], session('locale')) }}</th>
                            <th class="py-3 px-4 text-center">{{ trans('messages.status', [], session('locale')) }}</th>
                            <th class="py-3 px-4 text-center">{{ trans('messages.date', [], session('locale')) }}</th>
                            <th class="py-3 px-4 text-center">{{ trans('messages.total', [], session('locale')) }}</th>
                            <th class="py-3 px-4 text-center">{{ trans('messages.actions', [], session('locale')) }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[var(--border)]" id="recentOrdersTableBody">
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">{{ trans('messages.loading', [], session('locale')) }}...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>





        <!-- Optional extra: quick actions (useful but not required) -->


    </main>
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <script>
        // Wait for Chart.js to load and DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            const ctxYearly = document.getElementById('yearlyBarChart');
            
            if (!ctxYearly) {
                console.error('Chart canvas element not found');
                return;
            }
            
            // Monthly data from PHP
            @php
                $defaultMonthlyData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                $monthlyRevenueData = $monthlyData['revenue'] ?? $defaultMonthlyData;
                $monthlyExpensesData = $monthlyData['expenses'] ?? $defaultMonthlyData;
            @endphp
            const monthlyRevenue = @json($monthlyRevenueData);
            const monthlyExpenses = @json($monthlyExpensesData);
            
            // Month labels
            const monthLabels = [
                '{{ trans('messages.january', [], session('locale')) }}',
                '{{ trans('messages.february', [], session('locale')) }}',
                '{{ trans('messages.march', [], session('locale')) }}',
                '{{ trans('messages.april', [], session('locale')) }}',
                '{{ trans('messages.may', [], session('locale')) }}',
                '{{ trans('messages.june', [], session('locale')) }}',
                '{{ trans('messages.july', [], session('locale')) }}',
                '{{ trans('messages.august', [], session('locale')) }}',
                '{{ trans('messages.september', [], session('locale')) }}',
                '{{ trans('messages.october', [], session('locale')) }}',
                '{{ trans('messages.november', [], session('locale')) }}',
                '{{ trans('messages.december', [], session('locale')) }}'
            ];
            
            // Initialize chart
            window.yearlyChart = new Chart(ctxYearly, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: '{{ trans('messages.revenue', [], session('locale')) }}',
                        data: monthlyRevenue,
                        backgroundColor: 'rgba(109, 91, 208, 0.75)',
                        borderRadius: 8,
                        barThickness: 14
                    },
                    {
                        label: '{{ trans('messages.expenses', [], session('locale')) }}',
                        data: monthlyExpenses,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderRadius: 8,
                        barThickness: 14
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            rtl: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + parseFloat(context.raw).toFixed(3) + ' ر.ع';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => parseFloat(value).toFixed(3) + ' ر.ع'
                            }
                        }
                    }
                }
            });
        });
        
        // Function to update chart when year changes
        function updateChart() {
            if (!window.yearlyChart) {
                console.error('Chart not initialized');
                return;
            }
            
            const selectedYear = document.getElementById('yearSelector').value;
            
            // Show loading state
            window.yearlyChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            window.yearlyChart.data.datasets[1].data = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            window.yearlyChart.update();
            
            // Fetch data for selected year
            fetch(`/dashboard/monthly-data?year=${selectedYear}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.yearlyChart.data.datasets[0].data = data.revenue;
                    window.yearlyChart.data.datasets[1].data = data.expenses;
                    window.yearlyChart.update();
                }
            })
            .catch(error => {
                console.error('Error fetching monthly data:', error);
            });
        }
        
        // Load late deliveries
        async function loadLateDeliveries() {
            try {
                // First check and mark late deliveries
                await fetch('/special-orders/check-late-deliveries', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
                
                // Then fetch late deliveries
                const response = await fetch('/special-orders/late-deliveries', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                const container = document.getElementById('lateDeliveriesList');
                
                if (data.success && data.items && data.items.length > 0) {
                    container.innerHTML = data.items.slice(0, 5).map(item => `
                        <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold">${item.order_no}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ trans('messages.tailor', [], session('locale')) }}: <span class="font-semibold text-gray-700">${item.tailor_name}</span></p>
                                    <p class="text-xs text-gray-500 mt-1">{{ trans('messages.customer', [], session('locale')) }}: <span class="font-semibold text-gray-700">${item.customer_name}</span></p>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs text-gray-500">{{ trans('messages.days_late', [], session('locale')) ?: 'Days Late' }}</p>
                                    <p class="font-bold" style="color: var(--danger);">${item.days_late} {{ trans('messages.days', [], session('locale')) }}</p>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                {{ trans('messages.sent_date', [], session('locale')) }}: <span class="font-semibold">${item.sent_date || '—'}</span>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `<div class="text-center text-gray-500 py-4">{{ trans('messages.no_late_deliveries', [], session('locale')) ?: 'No late deliveries' }}</div>`;
                }
            } catch (error) {
                console.error('Error loading late deliveries:', error);
                const container = document.getElementById('lateDeliveriesList');
                if (container) {
                    container.innerHTML = `<div class="text-center text-red-500 py-4">{{ trans('messages.error_loading_data', [], session('locale')) }}</div>`;
                }
            }
        }
        
        // Load late deliveries on page load
        loadLateDeliveries();
        
        // Refresh late deliveries every 5 minutes
        setInterval(loadLateDeliveries, 5 * 60 * 1000);
        
        // Load abayas under tailoring
        async function loadAbayasUnderTailoring() {
            try {
                const response = await fetch('/dashboard/abayas-under-tailoring', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                const container = document.getElementById('abayasUnderTailoringList');
                
                if (data.success && data.items && data.items.length > 0) {
                    container.innerHTML = data.items.map(item => `
                        <div class="rounded-xl border border-[var(--border)] p-3 bg-white">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold">${item.order_no}</p>
                                <span class="text-xs px-2 py-1 rounded-full" style="background: rgba(109,91,208,.12); color: var(--primary2);">{{ trans('messages.under_tailoring', [], session('locale')) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ trans('messages.tailor', [], session('locale')) }}: <span class="font-semibold text-gray-700">${item.tailor_name}</span> • 
                                {{ trans('messages.started', [], session('locale')) }}: <span class="font-semibold">${item.sent_date_formatted || item.sent_date || '—'}</span>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ trans('messages.customer', [], session('locale')) }}: <span class="font-semibold text-gray-700">${item.customer_name}</span>
                            </p>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `<div class="text-center text-gray-500 py-4">{{ trans('messages.no_abayas_under_tailoring', [], session('locale')) ?: 'No abayas under tailoring' }}</div>`;
                }
            } catch (error) {
                console.error('Error loading abayas under tailoring:', error);
                const container = document.getElementById('abayasUnderTailoringList');
                if (container) {
                    container.innerHTML = `<div class="text-center text-red-500 py-4">{{ trans('messages.error_loading_data', [], session('locale')) }}</div>`;
                }
            }
        }
        
        // Load abayas under tailoring on page load
        loadAbayasUnderTailoring();
        
        // Refresh abayas under tailoring every 5 minutes
        setInterval(loadAbayasUnderTailoring, 5 * 60 * 1000);
        
        // Load low stock items
        async function loadLowStockItems() {
            try {
                const response = await fetch('/dashboard/low-stock-items', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                const container = document.getElementById('lowStockItemsList');
                
                if (data.success && data.items && data.items.length > 0) {
                    container.innerHTML = data.items.map(item => {
                        const isCritical = item.remaining <= 1; // Critical if 1 or less
                        const badgeClass = isCritical 
                            ? 'background: var(--dangerSoft); color: var(--danger);'
                            : 'background: var(--warnSoft); color: var(--warn);';
                        const barColor = isCritical ? 'var(--danger)' : 'var(--warn)';
                        const percentage = Math.max(item.percentage, 5); // Minimum 5% for visibility
                        
                        return `
                            <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold">${item.design_name}</p>
                                    <span class="text-xs px-2 py-1 rounded-full" style="${badgeClass}">
                                        {{ trans('messages.remaining', [], session('locale')) }} ${item.remaining}
                                    </span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full" style="width: ${percentage}%; background: ${barColor};"></div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = `<div class="text-center text-gray-500 py-4">{{ trans('messages.no_low_stock_items', [], session('locale')) ?: 'No low stock items' }}</div>`;
                }
            } catch (error) {
                console.error('Error loading low stock items:', error);
                const container = document.getElementById('lowStockItemsList');
                if (container) {
                    container.innerHTML = `<div class="text-center text-red-500 py-4">{{ trans('messages.error_loading_data', [], session('locale')) }}</div>`;
                }
            }
        }
        
        // Load boutique rent reminders
        async function loadBoutiqueRentReminders() {
            try {
                const response = await fetch('/dashboard/boutique-rent-reminders', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                const container = document.getElementById('boutiqueRentRemindersList');
                
                if (data.success && data.reminders && data.reminders.length > 0) {
                    container.innerHTML = data.reminders.map(reminder => {
                        let statusClass = '';
                        let statusIcon = '';
                        
                        if (reminder.status === 'unpaid') {
                            statusClass = 'background: rgba(239, 68, 68, 0.1); color: #ef4444;';
                            statusIcon = 'cancel';
                        } else if (reminder.status === 'pending') {
                            statusClass = 'background: var(--warnSoft); color: var(--warn);';
                            statusIcon = 'schedule';
                        } else if (reminder.status === 'upcoming') {
                            statusClass = 'background: rgba(59, 130, 246, 0.1); color: #3b82f6;';
                            statusIcon = 'event';
                        } else if (reminder.status === 'paid') {
                            statusClass = 'background: var(--okSoft); color: var(--ok);';
                            statusIcon = 'check_circle';
                        } else {
                            statusClass = 'background: var(--okSoft); color: var(--ok);';
                            statusIcon = 'check_circle';
                        }
                        
                        let daysRemainingText = '-';
                        if (reminder.days_remaining !== null && reminder.days_remaining !== undefined) {
                            // Ensure days_remaining is an integer and at least 1 if positive
                            let days = Math.max(1, Math.ceil(reminder.days_remaining));
                            
                            if (reminder.days_remaining <= 0) {
                                // For overdue payments, show absolute value
                                days = Math.abs(Math.floor(reminder.days_remaining));
                                daysRemainingText = `${days} {{ trans("messages.days_overdue", [], session("locale")) ?: "days overdue" }}`;
                            } else if (days === 0 || reminder.days_remaining === 0) {
                                daysRemainingText = '{{ trans("messages.today", [], session("locale")) }}';
                            } else if (days === 1 && reminder.days_remaining <= 1) {
                                daysRemainingText = '{{ trans("messages.tomorrow", [], session("locale")) ?: "Tomorrow" }}';
                            } else {
                                daysRemainingText = `${days} {{ trans("messages.days", [], session("locale")) ?: "days" }}`;
                            }
                        }
                        
                        return `
                            <tr>
                                <td class="py-3 font-semibold text-center">${reminder.boutique_name}</td>
                                <td class="py-3 text-center text-gray-700">${reminder.month_name || reminder.month}</td>
                                <td class="py-3 text-center">${reminder.amount.toFixed(3)} ر.ع</td>
                                <td class="py-3 text-center text-gray-600">${reminder.payment_date_formatted}</td>
                                <td class="py-3 text-center text-gray-600">${daysRemainingText}</td>
                                <td class="py-3 text-center">
                                    <span class="inline-flex items-center justify-center gap-1 text-xs px-2 py-1 rounded-full" style="${statusClass}">
                                        <span class="material-symbols-outlined text-[16px]">${statusIcon}</span> ${reminder.status_text}
                                    </span>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-gray-500">{{ trans('messages.no_rent_reminders', [], session('locale')) ?: 'No rent reminders' }}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading boutique rent reminders:', error);
                const container = document.getElementById('boutiqueRentRemindersList');
                if (container) {
                    container.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-red-500">{{ trans('messages.error_loading_data', [], session('locale')) }}</td></tr>`;
                }
            }
        }
        
        // Load low stock and rent reminders on page load
        loadLowStockItems();
        loadBoutiqueRentReminders();
        
        // Refresh every 5 minutes
        setInterval(() => {
            loadLowStockItems();
            loadBoutiqueRentReminders();
        }, 5 * 60 * 1000);
        
        // Load recent special orders
        async function loadRecentSpecialOrders() {
            try {
                const response = await fetch('/dashboard/recent-special-orders', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                const container = document.getElementById('recentOrdersTableBody');
                
                if (data.success && data.orders && data.orders.length > 0) {
                    container.innerHTML = data.orders.map(order => {
                        return `
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-4 font-semibold text-center">${order.order_no}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center justify-center gap-1 text-xs px-2 py-1 rounded-full" style="${order.source_info.class}">
                                        <span class="material-symbols-outlined text-[14px]">${order.source_info.icon}</span>
                                        ${order.source_info.text}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="font-medium">${order.customer_name}</div>
                                    <div class="text-xs text-gray-500">${order.customer_phone}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center justify-center gap-1 text-xs px-2 py-1 rounded-full" style="${order.status_info.class}">
                                        <span class="material-symbols-outlined text-[14px]">${order.status_info.icon}</span>
                                        ${order.status_info.text}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 text-center">${order.date_formatted}</td>
                                <td class="py-3 px-4 font-bold text-center">${order.total.toFixed(3)} {{ trans('messages.currency', [], session('locale')) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        onclick="printSpecialOrderBill(${order.id})"
                                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg border border-[var(--border)] text-xs hover:bg-gray-100 transition">
                                        <span class="material-symbols-outlined text-[16px]">print</span>
                                        {{ trans('messages.print', [], session('locale')) }}
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">{{ trans('messages.no_orders_found', [], session('locale')) ?: 'No orders found' }}</td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading recent special orders:', error);
                const container = document.getElementById('recentOrdersTableBody');
                if (container) {
                    container.innerHTML = `
                        <tr>
                            <td colspan="7" class="py-4 text-center text-red-500">{{ trans('messages.error_loading_data', [], session('locale')) }}</td>
                        </tr>
                    `;
                }
            }
        }
        
        // Print special order bill
        function printSpecialOrderBill(orderId) {
            const billUrl = '{{ url("special-order-bill") }}/' + orderId;
            window.open(billUrl, '_blank');
        }
        
        // Load recent orders on page load
        loadRecentSpecialOrders();
        
        // Refresh recent orders every 5 minutes
        setInterval(loadRecentSpecialOrders, 5 * 60 * 1000);
        
        // Filter orders function (for search)
        function filterOrders() {
            const searchInput = event.target.value.toLowerCase();
            const rows = document.querySelectorAll('#recentOrdersTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchInput)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Print orders table function
        function printOrdersTable() {
            window.print();
        }
    </script>
    <!-- =========================
       FOOTER (included)
  ========================== -->
    @include('layouts.footer')
    @endsection
  
