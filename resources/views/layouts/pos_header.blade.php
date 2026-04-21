<!DOCTYPE html>
<html class="light" dir="{{ session('locale', 'ar') === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ session('locale', 'ar') }}">

<head>
  <meta charset="utf-8" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>{{ trans('messages.pos_system', [], session('locale')) }}</title>
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "var(--color-primary)",
            "primary-dark": "var(--color-primary-dark)",
            "accent-gold": "var(--color-accent-gold)",
            "background-light": "#fbfbf8", // Lighter, more neutral background
            "background-dark": "#1a1a1a",
          },
          fontFamily: {
            "display": ["IBM Plex Sans Arabic", "sans-serif"],
            "body": ["IBM Plex Sans Arabic", "sans-serif"]
          },
          borderRadius: {
            "DEFAULT": "1rem",
            "lg": "1.5rem",
            "xl": "2rem",
            "full": "9999px"
          },
          boxShadow: {
            "soft": "0 4px 20px -2px rgba(0, 0, 0, 0.05)",
            "card": "0 0 0 1px rgba(0,0,0,0.02), 0 4px 12px rgba(0,0,0,0.06)",
            "premium": "0 8px 30px rgba(0,0,0,0.1)",
            "glow-primary": "0 0 15px rgba(var(--color-primary-rgb), 0.3)",
            "glow-accent": "0 0 12px rgba(var(--color-accent-gold-rgb), 0.4)",
          }
        },
      },
    }
  </script>
  <style type="text/tailwindcss">
    :root {
            --color-primary: #1F6F67;--color-primary-dark: #1A5C55;
            --color-primary-rgb: 31, 111, 103;
            --color-accent-gold: #B8860B;--color-accent-gold-rgb: 184, 134, 11;
        }
.pay-btn {
  @apply flex flex-col items-center justify-center gap-1 h-16 rounded-xl border
         text-sm font-bold bg-white text-gray-700
         hover:bg-primary hover:text-white transition;
}
.pay-btn.active {
  @apply bg-primary text-white border-primary shadow-md;
}

/* Order type buttons */
.order-type-btn {
  @apply px-6 py-3 rounded-xl border font-bold text-sm
         bg-white text-gray-700
         hover:bg-primary hover:text-white transition;
}

.order-type-btn.active {
  @apply bg-primary text-white border-primary shadow-md;
}
    </style>
  <style>
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }

    ::-webkit-scrollbar-track {
      background: transparent;
    }

    ::-webkit-scrollbar-thumb {
      background: rgba(var(--color-primary-rgb), 0.2);
      border-radius: 99px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: rgba(var(--color-primary-rgb), 0.4);
    }

    body {
      font-family: 'IBM Plex Sans Arabic', sans-serif;
      letter-spacing: -0.02em;
    }

    .order-type-btn {
      @apply px-6 py-3 rounded-xl border font-bold text-sm hover:bg-primary hover:text-white transition;
    }

    .order-type-btn.active {
      @apply bg-primary text-white;
    }

    /* Flying cart item animation */
    .fly-item {
      position: fixed;
      z-index: 9999;
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background-size: cover;
      background-position: center;
      transition: transform 0.9s cubic-bezier(.4, 1.4, .6, 1), opacity 0.9s;
    }

    /* Notification shake */
    @keyframes shake {
      0% {
        transform: rotate(0);
      }

      25% {
        transform: rotate(-10deg);
      }

      50% {
        transform: rotate(10deg);
      }

      75% {
        transform: rotate(-6deg);
      }

      100% {
        transform: rotate(0);
      }
    }

    .shake {
      animation: shake 0.6s ease;
    }

    .category-tab {
      @apply h-12 px-7 rounded-full bg-gray-100 text-gray-700 font-bold transition-all duration-200 outline-none;
    }

    /* Hover */
    .category-tab:hover {
      @apply bg-primary/10 text-primary;
    }

    .category-tab:active {
      transform: scale(0.96);
    }

    /* Keyboard / focus ring */
    .category-tab:focus-visible {
      box-shadow: 0 0 0 3px rgba(31, 111, 103, 0.35);
    }

    /* Active (selected tab) */
    .category-tab.active {
      background-color: var(--color-primary);
      color: white;
      box-shadow: 0 6px 16px rgba(31, 111, 103, 0.35);
      transform: translateY(-1px);
    }

    /* Keyboard / programmatic focus */
    .category-tab:focus-visible {
      @apply ring-2 ring-primary ring-offset-2;
    }

    body.modal-open {
      overflow: hidden;
      position: fixed;
      width: 100%;
    }
  </style>
</head>

<body class="bg-background-light dark:bg-background-dark h-screen overflow-x-hidden text-[#181811] flex flex-col">
  <header class="bg-white shadow-premium z-20">

    <!-- ===================== -->
    <!-- 🖥️ Desktop Header -->
    <!-- ===================== -->
    <div class="hidden lg:flex h-20 px-8 items-center justify-between">

      <!-- Left -->
      <div class="flex items-center gap-6">
        <button
          id="sidebarToggle"
          class="size-12 flex items-center justify-center rounded-full hover:bg-gray-50 transition-colors">
          <span class="material-symbols-outlined text-3xl text-gray-700">menu</span>
        </button>

        <div class="h-10 w-px bg-gray-200"></div>

        <div class="flex items-center gap-4">
          <div class="bg-primary/10 rounded-full size-11 flex items-center justify-center text-primary-dark">
            <span class="material-symbols-outlined text-2xl">person</span>
          </div>
          <h2 class="text-base font-bold text-gray-800">{{ auth()->user()->user_name ?? auth()->user()->name ?? trans('messages.user_name', [], session('locale')) }}</h2>
        </div>
      </div>

      <!-- Center -->
      <h1 class="text-xl font-extrabold text-primary-dark">
        POS 
        @if(isset($selectedChannel) && $selectedChannel)
          ({{ session('locale') === 'ar' ? ($selectedChannel->channel_name_ar ?? $selectedChannel->channel_name_en) : ($selectedChannel->channel_name_en ?? $selectedChannel->channel_name_ar) }})
        @else
          ({{ trans('messages.all_stock', [], session('locale')) }})
        @endif
      </h1>

      <!-- Right -->
      <div class="flex items-center gap-4">

        <!-- Notifications -->
        <button
          id="notificationBtn"
          onclick="openSuspendedModal()"
          class="relative size-12 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center">
          <span class="material-symbols-outlined text-gray-600 text-2xl">notifications</span>

          <span
            id="suspendedBadge"
            class="hidden absolute -top-1 -right-1 size-5 rounded-full
                 bg-red-500 text-white text-[11px] font-bold
                 flex items-center justify-center">
            0
          </span>
        </button>

        <!-- Arabic -->
        <button
          id="lang-ar"
          onclick="changeLanguage('ar')"
          class="flex items-center gap-2 h-12 px-5 rounded-full font-bold shadow-md hover:opacity-90 {{ session('locale', 'ar') === 'ar' ? 'bg-accent-gold text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
          {{ trans('messages.arabic', [], 'ar') }}
        </button>

        <!-- English -->
        <button
          id="lang-en"
          onclick="changeLanguage('en')"
          class="flex items-center gap-2 h-12 px-5 rounded-full font-bold shadow-md {{ session('locale', 'ar') === 'en' ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
          {{ trans('messages.english', [], 'en') }}
        </button>

      </div>
    </div>

    <!-- ===================== -->
    <!-- 📱 Mobile Header -->
    <!-- ===================== -->
    <div class="lg:hidden h-16 px-4 flex items-center justify-between">

      <!-- Menu -->
      <button
        id="sidebarToggleMobile"
        class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
        <span class="material-symbols-outlined text-2xl">menu</span>
      </button>

      <!-- Title -->
      <h1 class="text-base font-extrabold text-primary-dark truncate">
        POS 
        @if(isset($selectedChannel) && $selectedChannel)
          ({{ session('locale') === 'ar' ? ($selectedChannel->channel_name_ar ?? $selectedChannel->channel_name_en) : ($selectedChannel->channel_name_en ?? $selectedChannel->channel_name_ar) }})
        @else
          ({{ trans('messages.all_stock', [], session('locale')) }})
        @endif
      </h1>

      <!-- User Name (Mobile) -->
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-lg text-gray-600">person</span>
        <span class="text-sm font-bold text-gray-700">{{ auth()->user()->user_name ?? auth()->user()->name ?? '' }}</span>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">

        <!-- Notifications -->
        <button
          onclick="openSuspendedModal()"
          class="relative size-10 rounded-full bg-gray-50 flex items-center justify-center">
          <span class="material-symbols-outlined text-xl">notifications</span>

          <span
            id="suspendedBadgeMobile"
            class="hidden absolute -top-1 -right-1 size-5 rounded-full
                 bg-red-500 text-white text-[11px] font-bold
                 flex items-center justify-center">
            0
          </span>
        </button>

        <!-- Language -->
        <button
          id="langMobile"
          onclick="toggleLanguage()"
          class="h-9 px-3 rounded-full border border-gray-300
         text-xs font-extrabold text-primary bg-white">
          {{ strtoupper(session('locale', 'ar')) }} / {{ session('locale', 'ar') === 'ar' ? 'EN' : 'AR' }}
        </button>


      </div>
    </div>

  </header>

  <!-- Sidebar -->
  <div id="sidebar" class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out overflow-hidden flex flex-col">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
      <h2 id="sidebarTitle" class="text-xl font-bold text-gray-800">{{ trans('messages.active_channels', [], session('locale')) ?: 'Active Channels' }}</h2>
      <button id="sidebarClose" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
        <span class="material-symbols-outlined text-2xl text-gray-700">close</span>
      </button>
    </div>

    <!-- Sidebar Content -->
    <div class="flex-1 overflow-y-auto p-4">
      <div id="channelsList" class="space-y-3">
        <!-- Channels will be loaded here -->
        <div class="text-center text-gray-500 py-8">
          <span class="material-symbols-outlined text-4xl mb-2 block">sync</span>
          <p>{{ trans('messages.loading', [], session('locale')) ?: 'Loading...' }}</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar Overlay -->
  <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

    @yield('main_pos')

  <script>
    // Store current locale
    let currentLocale = '{{ session("locale", "ar") }}';
    
    // Function to change language
    async function changeLanguage(locale) {
      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
          console.error('CSRF token not found');
          return;
        }

        const response = await fetch('{{ url("change-locale") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({ locale: locale })
        });

        const data = await response.json();
        
        if (data.success) {
          // Update current locale
          currentLocale = locale;
          
          // Reload the page to apply language and direction changes
          window.location.reload();
        } else {
          console.error('Failed to change language:', data.message);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Failed to change language'
            });
          }
        }
      } catch (error) {
        console.error('Error changing language:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while changing language'
          });
        }
      }
    }
    
    // Function to toggle language (for mobile button)
    function toggleLanguage() {
      const newLocale = currentLocale === 'ar' ? 'en' : 'ar';
      changeLanguage(newLocale);
    }
    
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
    const sidebarClose = document.getElementById('sidebarClose');

    let channelsLoaded = false;
    function openSidebar() {
      sidebar.classList.remove('translate-x-full');
      sidebarOverlay.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      
      // Load channels when sidebar is opened
      loadActiveChannels();
    }

    function closeSidebar() {
      sidebar.classList.add('translate-x-full');
      sidebarOverlay.classList.add('hidden');
      document.body.style.overflow = '';
    }

    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', openSidebar);
    }
    if (sidebarToggleMobile) {
      sidebarToggleMobile.addEventListener('click', openSidebar);
    }
    if (sidebarClose) {
      sidebarClose.addEventListener('click', closeSidebar);
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Translation helper - uses Laravel translations from messages files
    function getTranslation(key, fallback) {
      const translations = {
        'ar': {
          'pos_stock_active': '{{ trans("messages.pos_stock_active", [], "ar") }}',
          'pos_stock_inactive': '{{ trans("messages.pos_stock_inactive", [], "ar") }}',
          'no_active_channels': '{{ trans("messages.no_active_channels", [], "ar") }}',
          'error_loading_channels': '{{ trans("messages.error_loading_channels", [], "ar") }}',
          'active_channels': '{{ trans("messages.active_channels", [], "ar") }}',
          'loading': '{{ trans("messages.loading", [], "ar") }}',
          'success': '{{ trans("messages.success", [], "ar") }}',
          'error': '{{ trans("messages.error", [], "ar") }}',
          'status_updated_successfully': '{{ trans("messages.status_updated_successfully", [], "ar") }}',
          'failed_to_update_status': '{{ trans("messages.failed_to_update_status", [], "ar") }}',
          'all_stock': '{{ trans("messages.all_stock", [], "ar") }}',
          'channel_cleared': '{{ trans("messages.channel_cleared", [], "ar") }}',
          'channel_selected': '{{ trans("messages.channel_selected", [], "ar") }}',
          'channel_not_found': '{{ trans("messages.channel_not_found", [], "ar") }}',
          'showing_all_stock': '{{ trans("messages.showing_all_stock", [], "ar") }}',
          'all_stock_items_will_be_displayed': '{{ trans("messages.all_stock_items_will_be_displayed", [], "ar") }}',
          'stock_items_available': '{{ trans("messages.stock_items_available", [], "ar") }}',
          'items': '{{ trans("messages.items", [], "ar") }}'
        },
        'en': {
          'pos_stock_active': '{{ trans("messages.pos_stock_active", [], "en") }}',
          'pos_stock_inactive': '{{ trans("messages.pos_stock_inactive", [], "en") }}',
          'no_active_channels': '{{ trans("messages.no_active_channels", [], "en") }}',
          'error_loading_channels': '{{ trans("messages.error_loading_channels", [], "en") }}',
          'active_channels': '{{ trans("messages.active_channels", [], "en") }}',
          'loading': '{{ trans("messages.loading", [], "en") }}',
          'success': '{{ trans("messages.success", [], "en") }}',
          'error': '{{ trans("messages.error", [], "en") }}',
          'status_updated_successfully': '{{ trans("messages.status_updated_successfully", [], "en") }}',
          'failed_to_update_status': '{{ trans("messages.failed_to_update_status", [], "en") }}',
          'all_stock': '{{ trans("messages.all_stock", [], "en") }}',
          'channel_cleared': '{{ trans("messages.channel_cleared", [], "en") }}',
          'channel_selected': '{{ trans("messages.channel_selected", [], "en") }}',
          'channel_not_found': '{{ trans("messages.channel_not_found", [], "en") }}',
          'showing_all_stock': '{{ trans("messages.showing_all_stock", [], "en") }}',
          'all_stock_items_will_be_displayed': '{{ trans("messages.all_stock_items_will_be_displayed", [], "en") }}',
          'stock_items_available': '{{ trans("messages.stock_items_available", [], "en") }}',
          'items': '{{ trans("messages.items", [], "en") }}'
        }
      };
      return translations[currentLocale]?.[key] || translations['en'][key] || fallback || key;
    }

    // Load active channels
    async function loadActiveChannels() {
      try {
        const channelsList = document.getElementById('channelsList');
        channelsList.innerHTML = `
          <div class="text-center text-gray-500 py-8">
            <span class="material-symbols-outlined text-4xl mb-2 block animate-spin">sync</span>
            <p>${getTranslation('loading', 'Loading...')}</p>
          </div>
        `;

        const response = await fetch('{{ url("pos/active-channels") }}');
        const data = await response.json();
        
        if (data.success && data.channels && data.channels.length > 0) {
          channelsList.innerHTML = '';
          
          // Add "All Stock" option
          const allStockItem = document.createElement('div');
          const isAllSelected = !data.selected_channel_id;
          if (isAllSelected) {
            allStockItem.className = 'bg-primary rounded-xl p-4 border-2 border-primary cursor-pointer transition-all hover:border-primary-dark';
            allStockItem.innerHTML = `
              <div class="flex items-center justify-between d-none">
                <div class="flex items-center gap-3">
                  <span class="material-symbols-outlined text-white">inventory_2</span>
                  <h3 class="text-base font-bold text-white">${getTranslation('all_stock', 'All Stock')}</h3>
                </div>
                <span class="material-symbols-outlined text-white">check_circle</span>
              </div>
            `;
          } else {
            allStockItem.className = 'bg-gray-50 rounded-xl p-4 border-2 border-gray-200 cursor-pointer transition-all hover:border-primary';
            allStockItem.innerHTML = `
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="material-symbols-outlined text-gray-800">inventory_2</span>
                  <h3 class="text-base font-bold text-gray-800">${getTranslation('all_stock', 'All Stock')}</h3>
                </div>
              </div>
            `;
          }
          allStockItem.onclick = () => selectChannel(null);
          channelsList.appendChild(allStockItem);
          
          // Add divider
          const divider = document.createElement('div');
          divider.className = 'my-3 border-t border-gray-200';
          channelsList.appendChild(divider);
          
          // Add channels
          data.channels.forEach(channel => {
            // Use current locale dynamically
            const channelName = currentLocale === 'ar' 
              ? (channel.channel_name_ar || channel.channel_name_en) 
              : (channel.channel_name_en || channel.channel_name_ar);
            
            const isSelected = data.selected_channel_id == channel.id;
            const channelItem = document.createElement('div');
            if (isSelected) {
              channelItem.className = 'bg-primary rounded-xl p-4 border-2 border-primary cursor-pointer transition-all hover:border-primary-dark';
              channelItem.innerHTML = `
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-white">store</span>
                    <h3 class="text-base font-bold text-white">${channelName}</h3>
                  </div>
                  <span class="material-symbols-outlined text-white">check_circle</span>
                </div>
              `;
            } else {
              channelItem.className = 'bg-gray-50 rounded-xl p-4 border-2 border-gray-200 cursor-pointer transition-all hover:border-primary';
              channelItem.innerHTML = `
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-gray-800">store</span>
                    <h3 class="text-base font-bold text-gray-800">${channelName}</h3>
                  </div>
                </div>
              `;
            }
            channelItem.onclick = () => selectChannel(channel.id);
            channelsList.appendChild(channelItem);
          });
        } else {
          channelsList.innerHTML = `
            <div class="text-center text-gray-500 py-8">
              <span class="material-symbols-outlined text-4xl mb-2 block">inbox</span>
              <p>${getTranslation('no_active_channels', 'No active channels found')}</p>
            </div>
          `;
        }
      } catch (error) {
        console.error('Error loading channels:', error);
        const channelsList = document.getElementById('channelsList');
        channelsList.innerHTML = `
          <div class="text-center text-red-500 py-8">
            <span class="material-symbols-outlined text-4xl mb-2 block">error</span>
            <p>${getTranslation('error_loading_channels', 'Error loading channels')}</p>
          </div>
        `;
      }
    }

    // Select channel for stock filtering
    async function selectChannel(channelId) {
      try {
        const response = await fetch('{{ url("pos/select-channel") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            channel_id: channelId
          })
        });

        const data = await response.json();
        
        if (data.success) {
          // Show SweetAlert with channel and stock information
          if (typeof Swal !== 'undefined') {
            if (channelId === null) {
              // Channel cleared - show all stock
              Swal.fire({
                icon: 'info',
                title: getTranslation('channel_cleared', 'Channel Cleared'),
                html: `<div style="text-align: center;">
                  <p style="font-size: 16px; margin-bottom: 10px;">${getTranslation('showing_all_stock', 'Showing All Stock')}</p>
                  <p style="font-size: 14px; color: #666;">${getTranslation('all_stock_items_will_be_displayed', 'All stock items will be displayed.')}</p>
                </div>`,
                timer: 2000,
                showConfirmButton: false
              });
            } else {
              // Channel selected - show channel name and stock count
              Swal.fire({
                icon: 'success',
                title: getTranslation('channel_selected', 'Channel Selected'),
                html: `<div style="text-align: center;">
                  <p style="font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #1F6F67;">${data.channel_name}</p>
                  <p style="font-size: 16px; margin-bottom: 5px;">${getTranslation('stock_items_available', 'Stock Items Available')}</p>
                  <p style="font-size: 24px; font-weight: bold; color: #1F6F67;">${data.stock_count || 0} ${getTranslation('items', 'items')}</p>
                </div>`,
                timer: 2500,
                showConfirmButton: false
              });
            }
          }
          
          // Reload the page to show filtered stocks after a short delay
          setTimeout(() => {
            window.location.reload();
          }, channelId === null ? 500 : 800);
        } else {
          throw new Error(data.message || 'Failed to select channel');
        }
      } catch (error) {
        console.error('Error selecting channel:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: getTranslation('error', 'Error'),
            text: error.message || 'Failed to select channel'
          });
        }
      }
    }

    // Update sidebar title when locale changes
    function updateSidebarTitle() {
      const sidebarTitle = document.getElementById('sidebarTitle');
      if (sidebarTitle) {
        sidebarTitle.textContent = getTranslation('active_channels', 'Active Channels');
      }
    }

    // Listen for language changes (if language buttons trigger a reload or event)
    // This will be called when language changes
    function onLanguageChange(newLocale) {
      currentLocale = newLocale;
      updateSidebarTitle();
      // Reload channels to update language
      if (!sidebar.classList.contains('translate-x-full')) {
        loadActiveChannels();
      }
    }

    // Add language button handlers if they exist
    document.addEventListener('DOMContentLoaded', function() {
      const langAr = document.getElementById('lang-ar');
      const langEn = document.getElementById('lang-en');
      const langMobile = document.getElementById('langMobile');

      // Note: Language buttons now use onclick="changeLanguage()" which reloads the page
      // So we don't need to add event listeners here anymore
    });

  </script>