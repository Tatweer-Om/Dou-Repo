@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.abaya_materials', [], session('locale')) }}</title>
@endpush
<main class="min-h-screen bg-surface">
<!-- TopAppBar (Shared Component) -->
<header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex justify-between items-center w-full px-8 py-4 sticky top-0 z-10">
<div class="flex items-center gap-4">
<span class="material-symbols-outlined text-primary md:hidden">menu</span>
<h1 class="text-2xl font-bold text-[#8a4853] dark:text-[#a6606b] tracking-tighter font-headline">Lumière Salon Pro</h1>
</div>
<div class="flex items-center gap-6">
<button class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-headline font-semibold text-sm transition-transform active:scale-95 duration-200">
                    Add New Booking
                </button>
</div>
</header>
<div class="px-8 py-10 space-y-8">
<!-- Summary Area - Bento Grid -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-6">
<div class="bg-surface-container-lowest p-6 rounded-xl editorial-shadow flex flex-col justify-between h-32 border-l-4 border-primary">
<span class="text-xs uppercase tracking-widest text-on-surface-variant font-label font-semibold">Total Bookings</span>
<span class="text-3xl font-headline font-bold text-on-surface">1,284</span>
</div>
<div class="bg-surface-container-lowest p-6 rounded-xl editorial-shadow flex flex-col justify-between h-32">
<span class="text-xs uppercase tracking-widest text-on-surface-variant font-label font-semibold">Deposits Amount</span>
<span class="text-3xl font-headline font-bold text-primary">$12,450.00</span>
</div>
<div class="bg-surface-container-lowest p-6 rounded-xl editorial-shadow flex flex-col justify-between h-32">
<span class="text-xs uppercase tracking-widest text-on-surface-variant font-label font-semibold">Amount Received</span>
<span class="text-3xl font-headline font-bold text-tertiary">$45,200.00</span>
</div>
<div class="bg-surface-container-lowest p-6 rounded-xl editorial-shadow flex flex-col justify-between h-32">
<span class="text-xs uppercase tracking-widest text-on-surface-variant font-label font-semibold">Amount Remaining</span>
<span class="text-3xl font-headline font-bold text-on-surface-variant">$3,150.00</span>
</div>
</section>
<!-- Filters Section -->
<section class="flex flex-col md:flex-row items-end gap-6 bg-surface-container-low p-8 rounded-xl">
<div class="w-full md:w-64 space-y-2">
<label class="block text-xs font-bold text-primary uppercase tracking-wider ml-1">Choose Team</label>
<div class="relative group">
<select class="w-full bg-surface-container-lowest border-none rounded-lg px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-primary/20 appearance-none">
<option>All Locations</option>
<option>Ghobra Branch</option>
<option>Seeb Branch</option>
</select>
<span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
</div>
</div>
<div class="w-full md:w-80 space-y-2">
<label class="block text-xs font-bold text-primary uppercase tracking-wider ml-1">Date Range</label>
<div class="flex items-center bg-surface-container-lowest rounded-lg px-4 py-3 group focus-within:ring-2 focus-within:ring-primary/20">
<span class="material-symbols-outlined text-on-surface-variant text-lg mr-2">calendar_today</span>
<input class="w-full bg-transparent border-none p-0 text-sm font-medium focus:ring-0 placeholder:text-outline-variant" placeholder="Oct 01, 2023 - Oct 31, 2023" type="text"/>
</div>
</div>
<div class="w-full md:w-auto md:ml-auto">
<div class="flex items-center bg-surface-container-lowest rounded-full px-6 py-3 shadow-sm border border-outline-variant/10">
<span class="material-symbols-outlined text-on-surface-variant mr-3">search</span>
<input class="bg-transparent border-none p-0 text-sm w-full md:w-64 focus:ring-0" placeholder="Search clients, services..." type="text"/>
</div>
</div>
</section>
<!-- Bookings Data Table -->
<section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low">
<th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Booking Date &amp; Time</th>
<th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Client Details</th>
<th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Services</th>
<th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Price (Total)</th>
<th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">Deposit</th>
<th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">Status</th>
</tr>
</thead>
<tbody class="divide-y divide-surface">
<!-- Row 1 -->
<tr class="hover:bg-surface-container-low transition-colors group">
<td class="px-6 py-6">
<div class="flex flex-col">
<span class="text-sm font-semibold text-on-surface">Oct 24, 2023</span>
<span class="text-xs text-on-surface-variant mt-1">10:30 AM</span>
</div>
</td>
<td class="px-6 py-6">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="professional headshot of a woman with a confident smile against a neutral background" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDgsz6Uak5sLyYRGAUU6rcvOJZdfsVg4W46k5OiPbnMIn_dqFib7osk-8aCQdHEvwviYzKDs9T7hcQ4xfNYgkoulOIn1LxWEd2A8aQKzdEaStV1RlUxm4eHje2Ed1XKSPlqS4GO7NSBggNwQPlNYj45KSzLC7TtgjqKwJhHjHxz4qzxuQxjz4YFHyTT_UeqR8OLMzVtpaIo4FIuDoUVIDWXUj3BJHrdusw_jHb386Is2MSIKU_3dRC0F4T9aojW0iy1KfMRgSUu4sgw"/>
</div>
<div class="flex flex-col">
<span class="text-sm font-bold text-on-surface">Elena Rodriguez</span>
<span class="text-xs text-on-surface-variant">+968 9234 5678</span>
</div>
</div>
</td>
<td class="px-6 py-6">
<div class="flex flex-wrap gap-1.5">
<span class="px-2 py-1 bg-secondary-container text-on-secondary-fixed-variant text-[10px] font-bold rounded uppercase">Balayage</span>
<span class="px-2 py-1 bg-secondary-container text-on-secondary-fixed-variant text-[10px] font-bold rounded uppercase">Blow Dry</span>
</div>
</td>
<td class="px-6 py-6 text-right">
<span class="text-sm font-bold text-on-surface">$180.00</span>
</td>
<td class="px-6 py-6 text-right">
<span class="text-sm font-medium text-tertiary">$50.00</span>
</td>
<td class="px-6 py-6 text-center">
<span class="material-symbols-outlined text-tertiary text-lg" style="font-variation-settings: 'FILL' 1;">check_circle</span>
</td>
</tr>
<!-- Row 2 -->
<tr class="hover:bg-surface-container-low transition-colors group bg-surface">
<td class="px-6 py-6">
<div class="flex flex-col">
<span class="text-sm font-semibold text-on-surface">Oct 24, 2023</span>
<span class="text-xs text-on-surface-variant mt-1">01:00 PM</span>
</div>
</td>
<td class="px-6 py-6">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="close up portrait of a man with styled hair in professional studio lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDu5LNCvDFPvPQdnbxzIyu-kiwviBDYNntlkGljI8WgxawpneGyZtYhVLbhQSHk7NNy753M2Wk8yYQgX_btvOMz4jam0__GaNaGTZLUegBijP_ZfBi1700MPrrGt_a29hql22ZoAt7N8n6HNUfDgcE6Q-f_p56jhUubGcRo-QSwOFy3W4gESBCIwBAXCTe76lcpyCz7GR0TWGGAvokeJ5jPBim-WVA6NZ1UCiV9lbpPx6fyyztFdSn-1XxioW-em81H1cSlF-b-kwxn"/>
</div>
<div class="flex flex-col">
<span class="text-sm font-bold text-on-surface">Marcus Chen</span>
<span class="text-xs text-on-surface-variant">+968 9876 5432</span>
</div>
</div>
</td>
<td class="px-6 py-6">
<div class="flex flex-wrap gap-1.5">
<span class="px-2 py-1 bg-secondary-container text-on-secondary-fixed-variant text-[10px] font-bold rounded uppercase">Gents Trim</span>
<span class="px-2 py-1 bg-secondary-container text-on-secondary-fixed-variant text-[10px] font-bold rounded uppercase">Beard Sculpt</span>
</div>
</td>
<td class="px-6 py-6 text-right">
<span class="text-sm font-bold text-on-surface">$45.00</span>
</td>
<td class="px-6 py-6 text-right">
<span class="text-sm font-medium text-on-surface-variant">$15.00</span>
</td>
<td class="px-6 py-6 text-center">
<span class="material-symbols-outlined text-primary-container text-lg">pending</span>
</td>
</tr>
<!-- Row 3 -->
<tr class="hover:bg-surface-container-low transition-colors group">
<td class="px-6 py-6">
<div class="flex flex-col">
<span class="text-sm font-semibold text-on-surface">Oct 25, 2023</span>
<span class="text-xs text-on-surface-variant mt-1">09:00 AM</span>
</div>
</td>
<td class="px-6 py-6">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="side profile of a woman with elegant aesthetic in soft natural morning light" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC5TQ49WpO9gzsQDSDMAx-i5mybn8QeA1DmhkfKuIWEJLv1gJk3ls9nkGem5sA-O5nu8oU6-EtShYVEx5edZ9BMWw0NS0el0FQRrevJnYhG9R39hcytDLc8NV-Yji3tjSNanqVhefnFhs7F8C-aYOx05vcijRk-ycp7AypFUQxv4HobIvZwmBAzeXEAlP0QM6G5iimms0qDzmmjRnpXdl0smVCxwIP1uwfFxpA0u0UNMYDSecA-5b_Ud5kAFBTpIp4juVlRpUYAJwOL"/>
</div>
<div class="flex flex-col">
<span class="text-sm font-bold text-on-surface">Sophia Al-Zadjali</span>
<span class="text-xs text-on-surface-variant">+968 9112 2334</span>
</div>
</div>
</td>
<td class="px-6 py-6">
<div class="flex flex-wrap gap-1.5">
<span class="px-2 py-1 bg-secondary-container text-on-secondary-fixed-variant text-[10px] font-bold rounded uppercase">HydraFacial</span>
</div>
</td>
<td class="px-6 py-6 text-right">
<span class="text-sm font-bold text-on-surface">$120.00</span>
</td>
<td class="px-6 py-6 text-right">
<span class="text-sm font-medium text-tertiary">$120.00</span>
</td>
<td class="px-6 py-6 text-center">
<span class="material-symbols-outlined text-tertiary text-lg" style="font-variation-settings: 'FILL' 1;">verified</span>
</td>
</tr>
</tbody>
</table>
</div>
<!-- Pagination -->
<div class="px-8 py-5 flex items-center justify-between border-t border-surface">
<span class="text-xs text-on-surface-variant font-medium">Showing 1 to 10 of 284 bookings</span>
<div class="flex items-center gap-2">
<button class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant">
<span class="material-symbols-outlined text-sm">chevron_left</span>
</button>
<button class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">1</button>
<button class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant">2</button>
<button class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant">3</button>
<span class="text-xs text-on-surface-variant mx-1">...</span>
<button class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant">29</button>
<button class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant">
<span class="material-symbols-outlined text-sm">chevron_right</span>
</button>
</div>
</div>
</section>
</div>
</main>
@include('layouts.salon_footer') 
@endsection