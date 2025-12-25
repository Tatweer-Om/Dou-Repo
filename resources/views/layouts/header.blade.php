<!DOCTYPE html>
@php
    $currentPath = request()->path();
    $currentRoute = Route::currentRouteName();
    $currentLocale = session('locale', 'ar');
    $htmlDir = $currentLocale === 'en' ? 'ltr' : 'rtl';
@endphp
<html class="light" dir="{{ $htmlDir }}" lang="{{ $currentLocale }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @stack('title')
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="{{asset('css/style.css')}}" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

  <!-- Tailwind Config -->
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#8B5CF6",
            "secondary": "#F1E8FF",
            "accent": "#7C3AED",
            "text-primary": "#1F2937",
            "background-light": "#F6F5F8",
            "background-dark": "#151022",
          },
          fontFamily: {
            "display": ["IBM Plex Sans Arabic", "sans-serif"]
          },
        },
      },
    }
  </script>

</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-primary dark:text-gray-200">

  <div id="overlay" class="overlay" style="display: none;"></div>
  <div class="flex flex-col min-h-screen">
    <div class="flex flex-1">

      <!-- ======================== SIDEBAR ======================== -->
      <aside id="sidebar" class="flex flex-col w-72 bg-white dark:bg-gray-900 text-text-primary dark:text-gray-200 shadow-sm transition-all duration-300">
        <!-- Brand -->
        <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-800 px-4">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">store</span>
            <h1 class="text-sm font-bold">{{ trans('messages.system_name', [], session('locale')) }}</h1>
          </div>
        </div>

        <!-- Nav -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto text-sm">

    <!-- Dashboard -->
    <a href="{{route('dashboard')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ ($currentRoute === 'dashboard' || $currentPath === 'dashboard') ? 'text-blue-500 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">dashboard</span>
        <span class="font-medium text-sm">{{ trans('messages.dashboard', [], session('locale')) }}</span>
    </a>

    <!-- Inventory -->
    <div>
        @php
            $inventoryMenuActive = strpos($currentPath, 'manage_quantity') === 0 || strpos($currentPath, 'inventory') === 0;
        @endphp
        <button onclick="toggleSubmenu('inventoryMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $inventoryMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">inventory_2</span>
                <span class="font-medium text-sm">{{ trans('messages.inventory_management', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-inventoryMenu">expand_more</span>
        </button>

        <div id="inventoryMenu" class="submenu mt-2 pl-8 space-y-1 {{ $inventoryMenuActive ? 'active' : '' }}">
            <a href="{{url('view_stock')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.inventory', [], session('locale')) }}
            </a>

            <a href="{{url('manage_quantity')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'manage_quantity') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.transfer_stock', [], session('locale')) }}
            </a>

            <a href="{{url('send_request')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.send_orders_to_tailors', [], session('locale')) }}
            </a>

            <!-- <a href="#" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.pos_points', [], session('locale')) }}
            </a> -->
        </div>
    </div>

     <div>
        @php
            $stockMenuActive = in_array($currentPath, ['stock', 'view_stock', 'view_material', 'size', 'color', 'categories', 'areas', 'cities']) || strpos($currentPath, 'stock/') === 0;
        @endphp
        <button onclick="toggleSubmenu('stock_menue')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $stockMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">warehouse</span>
                <span class="font-medium text-sm">{{ trans('messages.view_stock_lang', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-stock_menue">expand_more</span>
        </button>

        <div id="stock_menue" class="submenu mt-2 pl-8 space-y-1 {{ $stockMenuActive ? 'active' : '' }}">
            <a href="{{url('stock')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'stock') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_stock_lang', [], session('locale')) }}
            </a>

            <a href="{{url('view_stock')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'view_stock') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.view_stock_lang', [], session('locale')) }}
            </a>
              <a href="{{url('view_material')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'view_material') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.raw_materials', [], session('locale')) }}
            </a>

             <a href="{{url('size')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'size') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.size', [], session('locale')) }}
            </a>
                <a href="{{url('color')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'color') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.color', [], session('locale')) }}
            </a>
                <a href="{{url('categories')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'categories') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.category', [], session('locale')) }}
            </a>
                <a href="{{url('areas')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'areas') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.areas', [], session('locale')) }}
            </a>
                <a href="{{url('cities')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'cities') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.cities', [], session('locale')) }}
            </a>
                <a href="{{url('stock/audit')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'stock/audit') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.stock_audit', [], session('locale')) }}
            </a>
        </div>
    </div>

    <!-- Customers -->
    <div>
        @php
            $customersMenuActive = strpos($currentPath, 'customers') === 0 || strpos($currentPath, 'customer') === 0;
        @endphp
        <button onclick="toggleSubmenu('customersMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $customersMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">people</span>
                <span class="font-medium text-sm">{{ trans('messages.customer_lang', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-customersMenu">expand_more</span>
        </button>

        <div id="customersMenu" class="submenu mt-2 pl-8 space-y-1 {{ $customersMenuActive ? 'active' : '' }}">
            <a href="{{url('customers')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'customers' || strpos(request()->path(), 'customers/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.manage_customers', [], session('locale')) }}
            </a>
        </div>
    </div>
    <!-- Boutiques -->
    <div>
        @php
            $boutiquesMenuActive = in_array($currentPath, ['boutique_list', 'boutique', 'channels', 'settlement']) || strpos($currentPath, 'boutique') === 0;
        @endphp
        <button onclick="toggleSubmenu('boutiquesMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $boutiquesMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">storefront</span>
                <span class="font-medium text-sm">{{ trans('messages.boutique_management', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-boutiquesMenu">expand_more</span>
        </button>

        <div id="boutiquesMenu" class="submenu mt-2 pl-8 space-y-1 {{ $boutiquesMenuActive ? 'active' : '' }}">
            <a href="{{url('boutique_list')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'boutique_list') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.boutique_list', [], session('locale')) }}
            </a>

            <a href="{{url('boutique')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'boutique' || strpos(request()->path(), 'boutique/') === 0 || strpos(request()->path(), 'boutiques/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_boutique', [], session('locale')) }}
            </a>
               <a href="{{url('channels')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'channels') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_new_channel', [], session('locale')) }}
            </a>

            <a href="{{url('settlement')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'settlement') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.settlement_lang', [], session('locale')) }}
            </a>
        </div>
    </div>

    <!-- Tailor Orders -->
    <div>
        @php
            $tailorMenuActive = in_array($currentPath, ['send_request', 'tailor']) || strpos($currentPath, 'tailor') === 0;
        @endphp
        <button onclick="toggleSubmenu('tailorMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $tailorMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">cut</span>
                <span class="font-medium text-sm">{{ trans('messages.tailor_orders', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-tailorMenu">expand_more</span>
        </button>

        <div id="tailorMenu" class="submenu mt-2 pl-8 space-y-1 {{ $tailorMenuActive ? 'active' : '' }}">
            <a href="{{url('send_request')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'send_request') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.tailor_request', [], session('locale')) }}
            </a>

            <a href="{{url('tailor')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'tailor') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.tailors', [], session('locale')) }}
            </a>

            <a href="{{url('tailor-orders-list')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'tailor-orders-list') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.tailor_orders_list', [], session('locale')) }}
            </a>
        </div>
    </div>

     <div>
        @php
            $specialOrdersMenuActive = in_array($currentPath, ['view_special_order', 'spcialorder', 'maintenance']) || strpos($currentPath, 'special-order') === 0 || strpos($currentPath, 'special_orders') === 0 || strpos($currentPath, 'maintenance') === 0;
        @endphp
        <button onclick="toggleSubmenu('specialordersMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $specialOrdersMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">shopping_bag</span>
                <span class="font-medium text-sm">{{ trans('messages.special_orders', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-specialordersMenu">expand_more</span>
        </button>

        <div id="specialordersMenu" class="submenu mt-2 pl-8 space-y-1 {{ $specialOrdersMenuActive ? 'active' : '' }}">
            <a href="{{url('view_special_order')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'view_special_order' || strpos(request()->path(), 'special-order') === 0) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.special_order_list', [], session('locale')) }}
            </a>

            <a href="{{url('spcialorder')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'spcialorder') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_new_order', [], session('locale')) }}
            </a>

            <a href="{{url('maintenance')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'maintenance' || strpos(request()->path(), 'maintenance/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.maintenance', [], session('locale')) }}
            </a>
        </div>
    </div>

    <!-- POS -->
    <div>
        @php
            $posMenuActive = strpos($currentPath, 'pos') === 0;
        @endphp
        <button onclick="toggleSubmenu('posMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $posMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">point_of_sale</span>
                <span class="font-medium text-sm">{{ trans('messages.pos', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-posMenu">expand_more</span>
        </button>

        <div id="posMenu" class="submenu mt-2 pl-8 space-y-1 {{ $posMenuActive ? 'active' : '' }}">
            <a href="{{url('pos')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'pos' || (strpos(request()->path(), 'pos/') === 0 && request()->path() !== 'pos/orders/list')) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.pos', [], session('locale')) }}
            </a>

            <a href="{{url('pos/orders/list')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'pos/orders/list') ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.pos_orders_list', [], session('locale')) }}
            </a>
        </div>
    </div>

    <!-- Other main links -->
<!-- 
    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent">
        <span class="material-symbols-outlined text-xl">assignment_return</span> 
        <span class="font-medium text-sm">{{ trans('messages.returns', [], session('locale')) }}</span>
    </a> -->

    <!-- Expenses -->
    <div>
        @php
            $expensesMenuActive = strpos($currentPath, 'expenses') === 0 || strpos($currentPath, 'expense-categories') === 0;
        @endphp
        <button onclick="toggleSubmenu('expensesMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $expensesMenuActive ? 'text-blue-500 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">receipt_long</span>
                <span class="font-medium text-sm">{{ trans('messages.expenses', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-expensesMenu">expand_more</span>
        </button>

        <div id="expensesMenu" class="submenu mt-2 pl-8 space-y-1 {{ $expensesMenuActive ? 'active' : '' }}">
            <a href="{{url('expenses')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'expenses' || strpos(request()->path(), 'expenses/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_expense', [], session('locale')) }}
            </a>

            <a href="{{url('expense-categories')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'expense-categories' || strpos(request()->path(), 'expense-categories/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.expense_categories', [], session('locale')) }}
            </a>
        </div>
    </div>

    <a href="{{url('accounts')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'accounts' || strpos(request()->path(), 'accounts/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">account_balance</span>
        <span class="font-medium text-sm">{{ trans('messages.accounts', [], session('locale')) }}</span>
    </a>

    <a href="{{url('user')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'user' || strpos(request()->path(), 'user/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">group</span>
        <span class="font-medium text-sm">{{ trans('messages.users', [], session('locale')) }}</span>
    </a>

    <a href="{{url('sms')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'sms' || strpos(request()->path(), 'sms/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">sms</span>
        <span class="font-medium text-sm">{{ trans('messages.sms_panel', [], session('locale')) }}</span>
    </a>

    <a href="{{url('settings')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'settings' || strpos(request()->path(), 'settings/') === 0) ? 'text-blue-500 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">settings</span>
        <span class="font-medium text-sm">{{ trans('messages.settings', [], session('locale')) }}</span>
    </a>

    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent">
        <span class="material-symbols-outlined text-xl">assessment</span>
        <span class="font-medium text-sm">{{ trans('messages.reports', [], session('locale')) }}</span>
    </a>

</nav>


        <!-- Sidebar Profile -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 bg-primary/20 flex items-center justify-center"
                 style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDQi91QSkV-4K0sUKmlCpkHw7UCi1VfGmQ-c05lLV0I4_qyMmxhkJUIZXZMlB8tIrZANsYNJaOhlhyhj1iC-BN8Sgiw_uJXw8pEDNOFVFaBsJNHEADXLw46WplAJvQpldP7TQMotk4xe1F-PgoK5241wnuaPr6-Xo5FDbVryyGeGYn2WK4Sv_dGNkNYyFZqaRZPOB7fMvO7KIaex6b6Ebkg3ELbq6GQO-uaaWpQutOsk7duPdr9FdkpYZZ9dhni3iNHFIMXf2bEmgds");'>
              @if(!auth()->user() || !auth()->user()->user_name)
                <span class="material-symbols-outlined text-primary">person</span>
              @endif
            </div>
            <div class="flex-1">
              <h3 class="text-xs font-semibold">{{ auth()->user()->user_name ?? trans('messages.user_default', [], session('locale')) }}</h3>
              <p class="text-xs text-gray-500">{{ auth()->user()->user_email ?? trans('messages.admin_default', [], session('locale')) }}</p>
            </div>
            <button onclick="logout()" class="text-gray-500 hover:text-accent" title="{{ trans('messages.logout_title', [], session('locale')) }}">
              <span class="material-symbols-outlined text-sm">logout</span>
            </button>
          </div>
        </div>
      </aside>
      <!-- ======================== END SIDEBAR ======================== -->

      <!-- ======================== MAIN ======================== -->
      <div class="flex-1 flex flex-col">
        <!-- HEADER -->
        <header class="flex items-center justify-between bg-white dark:bg-gray-800/50 shadow-sm px-4 py-3 lg:px-6 lg:py-4">
  <!-- زر القائمة يظهر فقط في الموبايل -->
  <div class="flex items-center gap-2">
    <button id="menuBtn"
      class="lg:hidden h-10 w-10 flex items-center justify-center rounded-md hover:bg-secondary transition">
      <span class="material-symbols-outlined text-primary text-3xl">menu</span>
    </button>

    <span class="material-symbols-outlined text-primary text-3xl hidden lg:inline">dashboard</span>
    <h2 class="text-lg font-bold hidden lg:inline">{{ trans('messages.dashboard_title', [], session('locale')) }}</h2>
  </div>

          <!-- Actions (right side in RTL; visually on the left edge) -->
          <div class="flex items-center gap-3">
            <!-- Language Switch (click opens dropdown) -->
            <div class="relative">
              <button id="langBtn" class="h-10 w-10 rounded-full bg-secondary/60 dark:bg-primary/20 flex items-center justify-center hover:bg-secondary dark:hover:bg-primary/30 transition-colors" aria-haspopup="true" aria-expanded="false">
                <!-- Flag changes via JS (🇴🇲 or 🇬🇧) -->
                <span id="langFlag" class="text-lg leading-none">{{ $currentLocale === 'en' ? '🇬🇧' : '🇴🇲' }}</span>
              </button>
              <!-- Language Dropdown -->
              <div id="langMenu" class="dropdown absolute top-full mt-2 {{ $currentLocale === 'en' ? 'left-0' : 'right-0' }} w-40 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg z-20">
                <button data-lang="ar" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20 {{ $currentLocale === 'ar' ? 'bg-secondary/50' : '' }}">
                  <span class="text-base">🇴🇲</span> {{ trans('messages.arabic', [], session('locale')) }}
                </button>
                <button data-lang="en" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20 {{ $currentLocale === 'en' ? 'bg-secondary/50' : '' }}">
                  <span class="text-base">🇬🇧</span> {{ trans('messages.english', [], session('locale')) }}
                </button>
              </div>
            </div>

            <!-- Notifications (click opens dropdown) -->
            <div class="relative">
              <button id="notifBtn" class="relative h-10 w-10 rounded-full bg-secondary/60 dark:bg-primary/20 flex items-center justify-center hover:bg-secondary dark:hover:bg-primary/30 transition-colors" aria-haspopup="true" aria-expanded="false">
                <span class="material-symbols-outlined text-primary text-2xl">notifications</span>
                <span class="absolute top-1.5 {{ $currentLocale === 'en' ? 'left-1.5' : 'right-1.5' }} flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">2</span>
              </button>
              <!-- Notifications Dropdown -->
              <div id="notifMenu" class="dropdown absolute top-full mt-2 {{ $currentLocale === 'en' ? 'left-0' : 'right-0' }} w-80 max-w-[85vw] bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-xl z-20">
                <div class="p-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary">notifications</span>
                  <h4 class="font-semibold text-sm">{{ trans('messages.notifications', [], session('locale')) }}</h4>
                </div>
                <ul class="max-h-80 overflow-auto">
                  <!-- Notification item -->
                  <li class="px-3 py-3 hover:bg-secondary/50 dark:hover:bg-primary/10 transition">
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-amber-500">inventory</span>
                      <div class="flex-1">
                        <p class="text-sm font-medium">{{ trans('messages.low_abaya_stock', [], session('locale')) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                          {{ str_replace(':quantity', '3', trans('messages.remaining_quantity_pieces', [], session('locale'))) }} – {{ str_replace(':minutes', '10', trans('messages.updated_minutes_ago', [], session('locale'))) }}
                        </p>
                      </div>
                    </div>
                  </li>
                  <!-- Notification item -->
                  <li class="px-3 py-3 hover:bg-secondary/50 dark:hover:bg-primary/10 transition">
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-red-500">schedule</span>
                      <div class="flex-1">
                        <p class="text-sm font-medium">{{ trans('messages.abaya_delayed_delivery', [], session('locale')) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                          {{ str_replace([':order_no', ':hours'], ['#A-1029', '2'], trans('messages.order_delayed_hours', [], session('locale'))) }}
                        </p>
                      </div>
                    </div>
                  </li>
                </ul>
                <div class="p-2 border-t border-gray-100 dark:border-gray-700 text-center">
                  <a href="#" class="text-sm text-accent hover:underline">{{ trans('messages.view_all_notifications', [], session('locale')) }}</a>
                </div>
              </div>
            </div>

            <!-- Divider -->
            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>

            <!-- Profile (avatar + dropdown) -->
            <div class="relative">
              <button id="profileBtn" class="flex items-center gap-2">
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 bg-primary/20 flex items-center justify-center"
                     style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAMV8cxyrsD5UEUacXZKG1uOrUd6OzsEMfwBfXgQOZmbd7p6YyokoBslcg1oqcqY76c8ztgCAg9dpxT70RZAni__6Sdm-iZTfhOxMbIXEtwSl4MCoUCDqDyTBo5EJOYTIFZdvlI8Nl6JPqU_JJEyhMWzO11GpP5HTweoTGbGYt2TWdCtKmGd9WwCBzxVeqlhvMLWaP4RGBbymAtu9eOjH-lzowyMre0ADXxfcHJhhtxIqkeMjO3Hsa3iE-vyevTaGk43vmxXPjseZon");'>
                  @if(!auth()->user() || !auth()->user()->user_name)
                    <span class="material-symbols-outlined text-primary">person</span>
                  @endif
                </div>
                <span class="hidden md:inline text-sm font-medium">{{ auth()->user()->user_name ?? trans('messages.user_default', [], session('locale')) }}</span>
                <span class="material-symbols-outlined text-gray-500">expand_more</span>
              </button>
              <!-- Profile Dropdown -->
              <div id="profileMenu" class="dropdown absolute top-full mt-2 {{ $currentLocale === 'en' ? 'left-0' : 'right-0' }} w-48 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg z-20">
                <ul class="py-2">
                  <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20">
                      <span class="material-symbols-outlined text-base">person</span> {{ trans('messages.profile', [], session('locale')) }}
                    </a>
                  </li>
                  <li>
                    <a href="#" onclick="logout(); return false;" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10">
                      <span class="material-symbols-outlined text-base">logout</span> {{ trans('messages.logout_title', [], session('locale')) }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          
        </header>

  @yield('main')

<script>
function logout() {
    Swal.fire({
        title: '{{ trans('messages.confirm_logout', [], session('locale')) ?? 'تأكيد تسجيل الخروج' }}',
        text: '{{ trans('messages.are_you_sure_logout', [], session('locale')) ?? 'هل أنت متأكد من تسجيل الخروج؟' }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '{{ trans('messages.logout', [], session('locale')) ?? 'تسجيل الخروج' }}',
        cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) ?? 'إلغاء' }}'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form and submit it for logout
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('logout') }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>