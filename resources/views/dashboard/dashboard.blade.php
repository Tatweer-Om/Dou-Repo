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
    :root {
        --bg: #f7f7fb;
        --card: #ffffff;
        --text: #1f2937;
        --muted: #6b7280;
        --border: rgba(0, 0, 0, .06);
        --primary: #b34b8a;
        /* premium rose */
        --primary2: #6d5bd0;
        /* soft violet */
        --gold: #b68a2c;
        /* warm gold */
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

        body {
            background: #fff;
        }

        .print-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>
</head>

<body class="min-h-screen">
    <!-- =========================
       HEADER (included)
  ========================== -->


    <!-- =========================
       PAGE
  ========================== -->
    <main class="flex-1 p-6 space-y-6">

        <!-- Top row: KPI boxes -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Revenue today -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.todays_revenue', [], session('locale')) }}</p>
                        <p class="mt-2 text-2xl font-extrabold">85 {{ trans('messages.currency', [], session('locale')) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trans('messages.last_update', [], session('locale')) }}: 10:32 ص</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(179,75,138,.12);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--primary);">payments</span>
                    </div>
                </div>
            </div>

            <!-- Orders this month -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.current_month_orders', [], session('locale')) }}</p>
                        <p class="mt-2 text-2xl font-extrabold">143</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trans('messages.includes_website_whatsapp', [], session('locale')) }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(109,91,208,.12);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--primary2);">receipt_long</span>
                    </div>
                </div>
            </div>

            <!-- Net today (extra useful) -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.net_today', [], session('locale')) }}</p>
                        <p class="mt-2 text-2xl font-extrabold">72 {{ trans('messages.currency', [], session('locale')) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trans('messages.todays_expenses', [], session('locale')) }}: 13 {{ trans('messages.currency', [], session('locale')) }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl grid place-items-center"
                        style="background: rgba(16,185,129,.12);">
                        <span class="material-symbols-outlined text-[22px]" style="color: var(--ok);">trending_up</span>
                    </div>
                </div>
            </div>

            <!-- Under tailoring count (extra useful) -->
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ trans('messages.orders_under_tailoring_now', [], session('locale')) }}</p>
                        <p class="mt-2 text-2xl font-extrabold">18</p>
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
                        <select class="rounded-xl border border-[var(--border)] bg-white px-3 py-2 text-sm">
                            <option selected>2025</option>
                            <option>2024</option>
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

                    <div class="mt-3 space-y-2 text-sm">
                        <!-- Item -->
                        <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold">طلب #A-1029</p>
                                    <p class="text-xs text-gray-500 mt-1">الخياط: <span class="font-semibold text-gray-700">أم محمد</span></p>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs text-gray-500">{{ trans('messages.delivery_date', [], session('locale')) }}</p>
                                    <p class="font-bold" style="color: var(--danger);">منذ 2 يوم</p>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                {{ trans('messages.scheduled_delivery', [], session('locale')) }}: <span class="font-semibold">14/12/2025</span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold">طلب #A-1041</p>
                                    <p class="text-xs text-gray-500 mt-1">الخياط: <span class="font-semibold text-gray-700">بو حمد</span></p>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs text-gray-500">موعد التسليم</p>
                                    <p class="font-bold" style="color: var(--danger);">منذ 5 ساعات</p>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                التسليم المقرر: <span class="font-semibold">17/12/2025</span>
                            </div>
                        </div>
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

                    <div class="mt-3 space-y-2 text-sm">
                        <div class="rounded-xl border border-[var(--border)] p-3 bg-white">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold">طلب #A-1049</p>
                                <span class="text-xs px-2 py-1 rounded-full" style="background: rgba(109,91,208,.12); color: var(--primary2);">{{ trans('messages.under_tailoring', [], session('locale')) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ trans('messages.tailor', [], session('locale')) }}: <span class="font-semibold text-gray-700">أم خالد</span> • {{ trans('messages.started', [], session('locale')) }}: 16/12/2025</p>
                        </div>

                        <div class="rounded-xl border border-[var(--border)] p-3 bg-white">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold">طلب #A-1050</p>
                                <span class="text-xs px-2 py-1 rounded-full" style="background: rgba(109,91,208,.12); color: var(--primary2);">{{ trans('messages.under_tailoring', [], session('locale')) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">الخياط: <span class="font-semibold text-gray-700">بو سعيد</span> • بدأ: 17/12/2025</p>
                        </div>
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
                        <h3 class="font-bold">تنبيه إيجارات البوتيك</h3>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full" style="background: rgba(179,75,138,.12); color: var(--primary);">شهري</span>
                </div>

                <div class="mt-3 overflow-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500">
                                <th class="text-right py-2">{{ trans('messages.branch', [], session('locale')) }}</th>
                                <th class="text-right py-2">{{ trans('messages.amount', [], session('locale')) }}</th>
                                <th class="text-right py-2">{{ trans('messages.due_date', [], session('locale')) }}</th>
                                <th class="text-right py-2">{{ trans('messages.status', [], session('locale')) }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]">
                            <tr>
                                <td class="py-3 font-semibold">بوتيك السيب</td>
                                <td class="py-3">250 ر.ع</td>
                                <td class="py-3">01/01/2026</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full" style="background: var(--warnSoft); color: var(--warn);">
                                        <span class="material-symbols-outlined text-[16px]">schedule</span> {{ trans('messages.soon', [], session('locale')) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold">بوتيك الخوض</td>
                                <td class="py-3">180 ر.ع</td>
                                <td class="py-3">15/12/2025</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full" style="background: var(--dangerSoft); color: var(--danger);">
                                        <span class="material-symbols-outlined text-[16px]">error</span> متأخر
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold">بوتيك نزوى</td>
                                <td class="py-3">200 ر.ع</td>
                                <td class="py-3">10/01/2026</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full" style="background: var(--okSoft); color: var(--ok);">
                                        <span class="material-symbols-outlined text-[16px]">check_circle</span> {{ trans('messages.paid', [], session('locale')) }}
                                    </span>
                                </td>
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

                <div class="mt-3 space-y-2 text-sm">
                    <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">عباية مخمل - أسود (M)</p>
                            <span class="text-xs px-2 py-1 rounded-full" style="background: var(--dangerSoft); color: var(--danger);">{{ trans('messages.remaining', [], session('locale')) }} 2</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full" style="width: 18%; background: var(--danger);"></div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">عباية كريب - موف (L)</p>
                            <span class="text-xs px-2 py-1 rounded-full" style="background: var(--warnSoft); color: var(--warn);">{{ trans('messages.remaining', [], session('locale')) }} 5</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full" style="width: 35%; background: var(--warn);"></div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">شيلة حرير - بيج</p>
                            <span class="text-xs px-2 py-1 rounded-full" style="background: var(--warnSoft); color: var(--warn);">متبقي 6</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full" style="width: 42%; background: var(--warn);"></div>
                        </div>
                    </div>
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

                    <button
                        onclick="printOrdersTable()"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 hover:bg-gray-100 transition border border-[var(--border)]">
                        <span class="material-symbols-outlined text-[18px]">print</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 bg-gray-50">
                            <th class="py-3 px-4 text-right">رقم الطلب</th>
                            <th class="py-3 px-4 text-right">المصدر</th>
                            <th class="py-3 px-4 text-right">العميل</th>
                            <th class="py-3 px-4 text-right">الحالة</th>
                            <th class="py-3 px-4 text-right">التاريخ</th>
                            <th class="py-3 px-4 text-right">الإجمالي</th>
                            <th class="py-3 px-4 text-right">إجراءات</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[var(--border)]">

                        <!-- Order: Website -->
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-4 font-semibold">A-1052</td>

                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full"
                                    style="background: rgba(109,91,208,.12); color: var(--primary2);">
                                    <span class="material-symbols-outlined text-[14px]">language</span>
                                    {{ trans('messages.website', [], session('locale')) }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-medium">أمينة الحارثية</div>
                                <div class="text-xs text-gray-500">9123 4567</div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full"
                                    style="background: rgba(245,158,11,.14); color: var(--warn);">
                                    <span class="material-symbols-outlined text-[14px]">hourglass_top</span>
                                    {{ trans('messages.in_preparation', [], session('locale')) }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-gray-600">اليوم 10:10 ص</td>

                            <td class="py-3 px-4 font-bold">18 ر.ع</td>

                            <td class="py-3 px-4">
                                <button
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[var(--border)] text-xs hover:bg-gray-100 transition">
                                    <span class="material-symbols-outlined text-[16px]">print</span>
                                    {{ trans('messages.print', [], session('locale')) }}
                                </button>
                            </td>
                        </tr>

                        <!-- Order: WhatsApp -->
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-4 font-semibold">A-1049</td>

                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full"
                                    style="background: rgba(179,75,138,.12); color: var(--primary);">
                                    <span class="material-symbols-outlined text-[14px]">chat</span>
                                    {{ trans('messages.whatsapp', [], session('locale')) }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-medium">مريم البلوشية</div>
                                <div class="text-xs text-gray-500">9988 1122</div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full"
                                    style="background: rgba(109,91,208,.12); color: var(--primary2);">
                                    <span class="material-symbols-outlined text-[14px]">content_cut</span>
                                    {{ trans('messages.under_tailoring', [], session('locale')) }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-gray-600">أمس 7:40 م</td>

                            <td class="py-3 px-4 font-bold">25 ر.ع</td>

                            <td class="py-3 px-4">
                                <button
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[var(--border)] text-xs hover:bg-gray-100 transition">
                                    <span class="material-symbols-outlined text-[16px]">print</span>
                                    {{ trans('messages.print', [], session('locale')) }}
                                </button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </section>





        <!-- Optional extra: quick actions (useful but not required) -->


    </main>
  <script>
        const ctxYearly = document.getElementById('yearlyBarChart');

        new Chart(ctxYearly, {
            type: 'bar',
            data: {
                labels: [
                    '{{ trans('
                    messages.january ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.february ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.march ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.april ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.may ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.june ', [], session('
                    locale ')) }}',
                    '{{ trans('
                    messages.july ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.august ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.september ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.october ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.november ', [], session('
                    locale ')) }}', '{{ trans('
                    messages.december ', [], session('
                    locale ')) }}'
                ],
                datasets: [{
                        label: '{{ trans('
                        messages.revenue ', [], session('
                        locale ')) }}',
                        data: [1200, 1500, 1800, 1400, 2100, 2500, 2300, 2600, 2400, 2800, 3000, 3200],
                        backgroundColor: 'rgba(109, 91, 208, 0.75)',
                        borderRadius: 8,
                        barThickness: 14
                    },
                    {
                        label: '{{ trans('
                        messages.expenses ', [], session('
                        locale ')) }}',
                        data: [700, 800, 950, 820, 1100, 1300, 1200, 1400, 1250, 1500, 1700, 1800],
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderRadius: 8,
                        barThickness: 14
                    }
                ]
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
                                return context.dataset.label + ': ' + context.raw + ' ر.ع';
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
                            callback: (value) => value + ' ر.ع'
                        }
                    }
                }
            }
        });
    </script>
    <!-- =========================
       FOOTER (included)
  ========================== -->
    @include('layouts.footer')
    @endsection
  
