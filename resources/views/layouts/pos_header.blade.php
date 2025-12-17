<!DOCTYPE html>
<html class="light" dir="rtl" lang="ar">

<head>
  <meta charset="utf-8" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
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
          class="size-12 flex items-center justify-center rounded-full hover:bg-gray-50 transition-colors">
          <span class="material-symbols-outlined text-3xl text-gray-700">menu</span>
        </button>

        <div class="h-10 w-px bg-gray-200"></div>

        <div class="flex items-center gap-4">
          <div class="bg-primary/10 rounded-full size-11 flex items-center justify-center text-primary-dark">
            <span class="material-symbols-outlined text-2xl">person</span>
          </div>
          <h2 class="text-base font-bold text-gray-800">{{ trans('messages.user_name', [], session('locale')) }}</h2>
        </div>
      </div>

      <!-- Center -->
      <h1 class="text-xl font-extrabold text-primary-dark">
        {{ trans('messages.direct_sale', [], session('locale')) }}
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
          class="flex items-center gap-2 h-12 px-5 rounded-full
               bg-accent-gold text-white font-bold shadow-md hover:opacity-90">
          {{ trans('messages.arabic', [], session('locale')) }}
        </button>

        <!-- English -->
        <button
          id="lang-en"
          class="flex items-center gap-2 h-12 px-5 rounded-full
               bg-gray-800 text-white font-bold shadow-md hover:bg-gray-700">
          {{ trans('messages.english', [], session('locale')) }}
        </button>

      </div>
    </div>

    <!-- ===================== -->
    <!-- 📱 Mobile Header -->
    <!-- ===================== -->
    <div class="lg:hidden h-16 px-4 flex items-center justify-between">

      <!-- Menu -->
      <button
        class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
        <span class="material-symbols-outlined text-2xl">menu</span>
      </button>

      <!-- Title -->
      <h1 class="text-base font-extrabold text-primary-dark truncate">
        {{ trans('messages.direct_sale', [], session('locale')) }}
      </h1>

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
          AR / EN
        </button>


      </div>
    </div>

  </header>

    @yield('main_pos')