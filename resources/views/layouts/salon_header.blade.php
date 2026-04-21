<!DOCTYPE html>
@php
    $salonLocale = session('locale', 'en');
    $salonHtmlDir = $salonLocale === 'en' ? 'ltr' : 'rtl';
    $salonSidebarEdge = $salonLocale === 'ar' ? 'right-0 border-l' : 'left-0 border-r';
    $salonMainGutterMd = $salonLocale === 'ar' ? 'md:mr-64' : 'md:ml-64';
@endphp
<html class="light" lang="{{ $salonLocale }}" dir="{{ $salonHtmlDir }}"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Plume Salon Pro - Bookings Management</title>
<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<!-- Icons -->
 <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "tertiary-container": "#48805a",
                        "surface-container-low": "#f3f3f3",
                        "on-tertiary-fixed": "#00210e",
                        "on-secondary-container": "#446a74",
                        "surface-container-high": "#e8e8e8",
                        "secondary": "#3e646e",
                        "on-primary-container": "#fffbff",
                        "error": "#ba1a1a",
                        "inverse-on-surface": "#f1f1f1",
                        "surface-variant": "#e2e2e2",
                        "on-surface": "#1a1c1c",
                        "inverse-primary": "#ffb2bc",
                        "surface-container-highest": "#e2e2e2",
                        "primary-fixed": "#ffd9dd",
                        "secondary-container": "#c1e9f5",
                        "on-secondary-fixed-variant": "#254c55",
                        "secondary-fixed-dim": "#a5cdd8",
                        "surface-tint": "#8c4b55",
                        "on-secondary": "#ffffff",
                        "on-tertiary": "#ffffff",
                        "on-primary-fixed": "#3a0915",
                        "surface-container-lowest": "#ffffff",
                        "on-background": "#1a1c1c",
                        "surface": "#f9f9f9",
                        "tertiary": "#2e6743",
                        "on-error": "#ffffff",
                        "outline": "#857374",
                        "background": "#f9f9f9",
                        "tertiary-fixed": "#b4f1c3",
                        "on-tertiary-fixed-variant": "#16512f",
                        "surface-container": "#eeeeee",
                        "tertiary-fixed-dim": "#98d4a8",
                        "primary-fixed-dim": "#ffb2bc",
                        "on-tertiary-container": "#f6fff4",
                        "on-error-container": "#93000a",
                        "on-primary-fixed-variant": "#70343e",
                        "surface-dim": "#dadada",
                        "inverse-surface": "#2f3131",
                        "on-surface-variant": "#524345",
                        "on-secondary-fixed": "#001f26",
                        "error-container": "#ffdad6",
                        "primary-container": "#a6606b",
                        "outline-variant": "#d7c1c3",
                        "secondary-fixed": "#c1e9f5",
                        "primary": "#8a4853",
                        "surface-bright": "#f9f9f9",
                        "on-primary": "#ffffff"
                    },
                    fontFamily: {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
                },
            },
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
        }
        .editorial-shadow {
            box-shadow: 0px 20px 40px rgba(138, 72, 83, 0.04);
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
    /* Match public/css/style.css so custom.js dropdown toggles work */
    .dropdown { visibility: hidden; opacity: 0; transform: translateY(6px); transition: .18s ease; pointer-events: none; }
    .dropdown.show { visibility: visible; opacity: 1; transform: translateY(0); pointer-events: auto; }
  </style>
  </head>
<body class="text-on-surface">
<!-- Navigation Drawer (Shared Component) — LTR: left, RTL (Arabic): right -->
<aside class="h-screen w-64 fixed top-0 z-30 {{ $salonSidebarEdge }} bg-[#f3f3f3] dark:bg-[#1a1c1c] flex flex-col gap-6 p-6 hidden md:flex border-outline-variant/15">
<div class="font-['Manrope'] text-xl font-bold text-[#8a4853]">Plume Pro</div>
@php
    $salonPermIds = auth()->check() ? array_map('intval', auth()->user()->permissions ?? []) : [];
@endphp
<nav class="flex flex-col gap-2 mt-4">
@if(in_array(14, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_dashboard') }}">
<span class="material-symbols-outlined">dashboard</span>
<span class="font-['Inter'] text-sm">Dashboard</span>
</a>
@endif
@if(in_array(15, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_bookings') }}">
<span class="material-symbols-outlined">list_alt</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.perm_salon_bookings_list', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(16, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_booking_page') }}">
<span class="material-symbols-outlined">add_circle</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.perm_salon_booking_page', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(18, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('booking_management') }}">
<span class="material-symbols-outlined">calendar_month</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.booking_management_title', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(17, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('view_bookings') }}">
<span class="material-symbols-outlined">event_note</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.view_bookings_title', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(19, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('salon_team.index') }}">
<span class="material-symbols-outlined">groups</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.salon_team_menu', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(20, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('salonstaff.index') }}">
<span class="material-symbols-outlined">badge</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.view_staff_lang', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(21, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('salontool.index') }}">
<span class="material-symbols-outlined">handyman</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.view_tools_lang', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(22, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_expense_category.index') }}">
<span class="material-symbols-outlined">category</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.saloon_expense_category_menu', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(23, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_expense.index') }}">
<span class="material-symbols-outlined">receipt_long</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.saloon_expense_menu', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(24, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_expense.report') }}">
<span class="material-symbols-outlined">assessment</span>
<span class="font-['Inter'] text-sm">Expense Report</span>
</a>
@endif
@if(in_array(25, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('salonservice.index') }}">
<span class="material-symbols-outlined">spa</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.view_service_lang', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(26, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloncustomer.index') }}">
<span class="material-symbols-outlined">person</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.view_customer_lang', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(27, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_monthly_income_report') }}">
<span class="material-symbols-outlined">bar_chart</span>
<span class="font-['Inter'] text-sm">{{ trans('messages.mir_menu', [], session('locale')) }}</span>
</a>
@endif
@if(in_array(28, $salonPermIds))
<a class="flex items-center gap-3 text-[#524345] dark:text-[#d7c1c3] opacity-80 p-2 hover:text-[#8a4853] transition-all" href="{{ route('saloon_income_expense_report') }}">
<span class="material-symbols-outlined">monitoring</span>
<span class="font-['Inter'] text-sm">Income Expense Report</span>
</a>
@endif
</nav>
</aside>
<div class="flex flex-col min-h-screen min-w-0 {{ $salonMainGutterMd }}">
    @include('layouts._salon_account_bar')
    @yield('main')
</div>