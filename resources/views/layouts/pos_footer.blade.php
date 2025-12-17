



      @include('custom_js.custom_js')
@php

    $routeName = Route::currentRouteName();
    $segments = explode('.', $routeName);
    $route_name = isset($segments[0]) ? $segments[0] : null;

@endphp

 
    
            @if ($route_name == 'pos')
         @include('custom_js.pos_js')
    @endif
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js"></script>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          const wilayaSelect = document.getElementById('deliveryWilayah');
          const priceEl = document.getElementById('deliveryPrice');
          const updatePrice = () => {
            if (!wilayaSelect || !priceEl) return;
            const opt = wilayaSelect.options[wilayaSelect.selectedIndex];
            const charge = opt ? parseFloat(opt.dataset.charge || '0') : 0;
            priceEl.textContent = `${(charge || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}`;
          };
          wilayaSelect?.addEventListener('change', updatePrice);
          updatePrice();
        });
      </script>

</body>

</html>