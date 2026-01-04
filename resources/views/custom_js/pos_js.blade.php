<script>
        // Translations
        const translations = {
          selectSizeColor: "{{ trans('messages.select_size_color', [], session('locale')) }}",
          cartEmpty: "{{ trans('messages.cart_empty', [], session('locale')) }}",
          productNotFound: "{{ trans('messages.product_not_found', [], session('locale')) }}",
          noSuspendedInvoices: "{{ trans('messages.no_suspended_invoices', [], session('locale')) }}",
          items: "{{ trans('messages.items', [], session('locale')) }}",
          restore: "{{ trans('messages.restore', [], session('locale')) }}",
          size: "{{ trans('messages.size', [], session('locale')) }}",
          unitPrice: "{{ trans('messages.unit_price', [], session('locale')) }}",
          total: "{{ trans('messages.total', [], session('locale')) }}",
          omr: "{{ trans('messages.omr', [], session('locale')) }}",
          loading: "{{ trans('messages.loading', [], session('locale')) }}",
          errorLoadingData: "{{ trans('messages.errorLoadingData', [], session('locale')) }}",
          noStockAvailable: "{{ trans('messages.noStockAvailable', [], session('locale')) }}",
          available: "{{ trans('messages.available', [], session('locale')) }}",
          quantityError: "{{ trans('messages.quantityError', [], session('locale')) }}",
          quantityAvailable: "{{ trans('messages.quantityAvailable', [], session('locale')) }}",
          ok: "{{ trans('messages.ok', [], session('locale')) }}"
        };

        /* =========================================================
   POS SYSTEM - CLEAN FULL VERSION (NO FEATURE REMOVED)
   - Product Modal (size/color/qty + add)
   - Cart render (unit + total + qty controls)
   - Empty cart state
   - Cart qty count in header (total qty)
   - Discount box (percent/amount) + live total update (NO TAX)
   - Payment modal (methods + partial + order type + delivery section + address)
   - Customer autocomplete
   - Suspend invoices (fly animation + badge + list + restore)
   - Barcode enter opens product modal (demo mapping)
   - Category tabs filtering + active focus
========================================================= */

        /* ===============================
           SOUND
        ================================ */

        function syncMobileCart() {
          const desktop = document.getElementById("cartItems");
          const mobile = document.getElementById("cartMobileContent");
          if (desktop && mobile) {
            mobile.innerHTML = desktop.innerHTML;
          }
        }

        function playBeep() {
          const ctx = new(window.AudioContext || window.webkitAudioContext)();
          const oscillator = ctx.createOscillator();
          const gain = ctx.createGain();

          oscillator.type = "square";
          oscillator.frequency.value = 1200;
          gain.gain.value = 0.08;

          oscillator.connect(gain);
          gain.connect(ctx.destination);

          oscillator.start();
          oscillator.stop(ctx.currentTime + 0.12);
        }

        /* ===============================
           STATE
        ================================ */
        let cart = [];
        let currentProduct = {};
        let selectedSize = null;
        let selectedSizeId = null;
        let selectedColor = null;
        let modalQty = 1;

        // Discount state (global discount for whole cart)
        let discount = {
          type: "percent", // percent | amount
          value: 0
        };

        // Suspended invoices
        let suspendedInvoices = [];

        // Accounts (payment / partial)
        let accountsList = [];
        let selectedPayMethod = null; // account id or 'partial'

        /* ===============================
           HELPERS
        ================================ */
        function $(id) {
          return document.getElementById(id);
        }

        function parseMoneyFromText(text) {
          // Extract number from something like "450.00 ر.ع"
          const num = parseFloat(String(text).replace(/[^\d.]/g, ""));
          return isNaN(num) ? 0 : num;
        }

        function formatMoney(value) {
          return `${Number(value || 0).toFixed(2)} ${translations.omr}`;
        }

        /* ===============================
           PRODUCT MODAL LOGIC
        ================================ */
        function resetProductSelectionUI() {
          selectedSize = null;
          selectedSizeId = null;
          selectedColor = null;

          // Reset old size/color buttons if they exist
          document.querySelectorAll(".size-btn").forEach((btn) => {
            btn.classList.remove("bg-primary", "text-white");
          });

          document.querySelectorAll(".color-btn").forEach((btn) => {
            btn.classList.remove("ring-2", "ring-primary");
          });

          // Reset color-size items
          document.querySelectorAll(".color-size-item").forEach((item) => {
            item.classList.remove("border-primary", "bg-primary/5", "shadow-md");
            item.classList.add("border-gray-200");
          });
        }

        function changeQty(change) {
          modalQty = Math.max(1, modalQty + change);
          $("modalQty").innerText = modalQty;
        }

        function openProductModal(product) {
          // Reset selection every open (fixes the "must change size/color to add again" bug)
          resetProductSelectionUI();

          currentProduct = {
            id: product.id,
            name: product.name,
            price: Number(product.price),
            image: product.image,
            barcode: product.barcode || '',
            abaya_code: product.abaya_code || ''
          };

          modalQty = 1;
          $("modalName").innerText = currentProduct.name;
          $("modalPrice").innerText = formatMoney(currentProduct.price);
          $("modalImage").style.backgroundImage = `url('${currentProduct.image}')`;
          $("modalQty").innerText = "1";

          // Show loading state
          const container = document.getElementById("colorSizeContainer");
          container.innerHTML = `
    <div class="text-center text-gray-400 py-4">
      <span class="material-symbols-outlined text-4xl mb-2 block animate-pulse">inventory_2</span>
      <p class="text-sm">${translations.loading}...</p>
    </div>
  `;

          const modal = $("productModal");
          modal.classList.remove("hidden");
          modal.classList.add("flex");

          // Fetch stock details with colors and sizes
          fetch(`{{ url('pos/stock') }}/${product.id}`)
            .then(response => response.json())
            .then(data => {
              displayColorSizes(data.colorSizes);
            })
            .catch(error => {
              console.error('Error fetching stock details:', error);
              container.innerHTML = `
        <div class="text-center text-red-400 py-4">
          <span class="material-symbols-outlined text-4xl mb-2 block">error</span>
          <p class="text-sm">${translations.errorLoadingData}</p>
        </div>
      `;
            });
        }

        function displayColorSizes(colorSizes) {
          const container = document.getElementById("colorSizeContainer");

          if (!colorSizes || colorSizes.length === 0) {
            container.innerHTML = `
      <div class="text-center text-gray-400 py-4">
        <span class="material-symbols-outlined text-4xl mb-2 block">inventory</span>
        <p class="text-sm">${translations.noStockAvailable}</p>
      </div>
    `;
            return;
          }

          let html = '';

          colorSizes.forEach((item) => {
            const isAvailable = item.quantity > 0;
            const quantityClass = isAvailable ? 'text-primary font-bold' : 'text-gray-400';
            const cardClass = isAvailable ?
              'bg-white border border-gray-200 hover:border-primary hover:shadow-sm transition-all cursor-pointer' :
              'bg-gray-50 border border-gray-200 opacity-50 cursor-not-allowed';

            html += `
      <div class="color-size-item rounded-lg p-2 ${cardClass}" 
           data-size-id="${item.size_id}" 
           data-color-id="${item.color_id}"
           data-size-name="${item.size_name}"
           data-color-name="${item.color_name}"
           data-color-code="${item.color_code}"
           data-quantity="${item.quantity}"
           ${isAvailable ? 'onclick="selectColorSize(this)"' : ''}>
        <div class="flex items-center gap-2">
          <!-- Color Circle with Name -->
          <div class="flex flex-col items-center gap-1 flex-shrink-0">
            <div class="w-8 h-8 rounded-full border border-gray-300 shadow-sm" 
                 style="background-color: ${item.color_code}"></div>
            <span class="text-[10px] text-gray-600 text-center leading-tight max-w-[50px] truncate">${item.color_name}</span>
          </div>
          
          <!-- Size Name -->
          <div class="flex-1 min-w-0">
            <div class="text-xs font-semibold text-gray-800 mb-0.5">${item.size_name}</div>
            <div class="text-[10px] text-gray-500">
              ${translations.available}: <span class="${quantityClass}">${item.quantity}</span>
            </div>
          </div>
          
          <!-- Quantity Badge -->
          <div class="flex-shrink-0">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full ${isAvailable ? 'bg-primary/10 text-primary' : 'bg-gray-200 text-gray-400'} font-bold text-[11px]">
              ${item.quantity}
            </span>
          </div>
        </div>
      </div>
    `;
          });

          container.innerHTML = html;
        }

        function selectColorSize(element) {
          // Remove previous selection
          document.querySelectorAll('.color-size-item').forEach(item => {
            item.classList.remove('border-primary', 'bg-primary/5', 'shadow-sm');
            if (!item.classList.contains('opacity-50')) {
              item.classList.add('border-gray-200');
            }
          });

          // Add selection to clicked item
          element.classList.remove('border-gray-200');
          element.classList.add('border-primary', 'bg-primary/5', 'shadow-sm');

          // Set selected size and color
          selectedSize = element.dataset.sizeName;
          selectedColor = element.dataset.colorId;

          // Update size and color button styles (if they exist)
          updateSelectionUI(element);
        }

        function updateSelectionUI(selectedElement) {
          // This function can be used to update any additional UI elements
          // For now, the selection is handled by the card styling
        }

        function closeModal() {
          const modal = $("productModal");
          modal.classList.add("hidden");
          modal.classList.remove("flex");

          // Reset selection when closing
          resetProductSelectionUI();
          modalQty = 1;
          selectedSizeId = null;
        }

        function confirmAddToCart() {
          if (!selectedSize || !selectedColor) {
            alert(translations.selectSizeColor);
            return;
          }

          // Get selected color-size element to get quantity
          const selectedElement = document.querySelector('.color-size-item.border-primary');
          if (!selectedElement) {
            alert(translations.selectSizeColor);
            return;
          }

          const availableQty = parseInt(selectedElement.dataset.quantity) || 0;

          // Check if requested quantity is available
          if (modalQty > availableQty) {
            Swal.fire({
              icon: 'error',
              title: translations.quantityError || 'Quantity Error',
              text: `${translations.quantityAvailable} ${availableQty}`,
              confirmButtonText: translations.ok || 'OK',
              confirmButtonColor: '#1F6F67'
            });
            return;
          }

          // Get color and size names for display
          const colorName = selectedElement.dataset.colorName || '';
          const sizeName = selectedElement.dataset.sizeName || selectedSize;

          // Get color code from data attribute
          const colorCode = selectedElement.dataset.colorCode || '#000000';

          // Build cart item
          const sizeId = selectedElement.dataset.sizeId || selectedSizeId;
          const item = {
            id: currentProduct.id,
            name: currentProduct.name,
            price: Number(currentProduct.price),
            image: currentProduct.image,
            barcode: currentProduct.barcode || '',
            size: sizeName,
            sizeId: sizeId,
            color: colorName,
            colorId: selectedColor,
            colorCode: colorCode,
            availableQty: availableQty,
            qty: modalQty
          };

          // Merge logic - match by id, size id, and color id
          const existing = cart.find((i) =>
            i.id === item.id &&
            i.sizeId === item.sizeId &&
            i.colorId === item.colorId
          );

          if (existing) {
            const newQty = existing.qty + item.qty;
            if (newQty > availableQty) {
              Swal.fire({
                icon: 'error',
                title: translations.quantityError || 'Quantity Error',
                text: (translations.quantityAvailable || 'Available quantity is') + ' ' + availableQty,
                confirmButtonText: translations.ok || 'OK',
                confirmButtonColor: '#1F6F67'
              });
              return;
            }
            existing.qty = newQty;
            // Update availableQty in case it changed
            existing.availableQty = availableQty;
          } else {
            cart.push(item);
          }

          playBeep();
          renderCart();
          recalculateTotals();
          closeModal(); // Ensure it closes after add
        }

        /* ===============================
           CART RENDER + TOTALS
        ================================ */
        function getCartSubtotal() {
          return cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        }

        function getDiscountAmount(subtotal) {
          let amount = 0;

          if (discount.type === "percent") {
            amount = subtotal * (discount.value / 100);
          } else {
            amount = discount.value;
          }

          // Never exceed subtotal
          amount = Math.min(amount, subtotal);
          return amount;
        }

        function getDeliveryCharge() {
          // Check if order type is delivery
          const orderType = selectedOrderType || 'direct';
          if (orderType !== 'delivery') return 0;
          
          // Get delivery charge from selected wilayah
          const wilayahSelect = document.getElementById('deliveryWilayah');
          const deliveryPaidCheckbox = document.getElementById('deliveryPaid');
          const deliverySection = document.getElementById('deliverySection');
          
          // If delivery section is hidden, no delivery charge
          if (!deliverySection || deliverySection.classList.contains('hidden')) return 0;
          
          if (!wilayahSelect || !wilayahSelect.value) return 0;
          
          const selectedOption = wilayahSelect.options[wilayahSelect.selectedIndex];
          const charge = parseFloat(selectedOption.dataset.charge || 0);
          
          // If delivery is NOT paid (checkbox not checked), add to total
          if (!deliveryPaidCheckbox || !deliveryPaidCheckbox.checked) {
            return charge;
          }
          
          return 0; // If paid, don't add to total
        }

        function recalculateTotals() {
          let subtotal = 0;
          let totalQty = 0;

          cart.forEach((item) => {
            subtotal += item.price * item.qty;
            totalQty += item.qty;
          });

          // Calculate discount
          const discountAmount = getDiscountAmount(subtotal);
          
          // Get delivery charge (only if not paid)
          const deliveryCharge = getDeliveryCharge();
          
          // Calculate total: subtotal - discount + delivery (if not paid)
          const total = subtotal - discountAmount + deliveryCharge;

          // ===== Desktop total (shows payable amount after discount) =====
          const totalEl = document.getElementById("cartTotal");
          if (totalEl) {
            totalEl.innerHTML = `${total.toFixed(2)} <span class="text-base font-medium text-gray-500">${translations.omr}</span>`;
          }

          // ===== Update payment modal amounts =====
          updatePaymentModalAmounts(subtotal, discountAmount, total, deliveryCharge);

          // ===== Cart count (العنوان) =====
          const countEl = document.getElementById("cartCount");
          if (countEl) {
            countEl.innerText = `(${totalQty} ${translations.items})`;
          }

          // ===== Mobile badge =====
          const mobileBadge = document.getElementById("cartMobileBadge");
          if (mobileBadge) {
            if (totalQty > 0) {
              mobileBadge.innerText = totalQty;
              mobileBadge.classList.remove("hidden");
            } else {
              mobileBadge.classList.add("hidden");
            }
          }

          // ===== Mobile total (لو موجود) =====
          const mobileTotal = document.getElementById("cartMobileTotal");
          if (mobileTotal) {
            mobileTotal.innerText = total.toFixed(2) + " " + translations.omr;
          }

          // ===== Empty state (desktop) =====
          const emptyState = document.getElementById("emptyCart");
          if (emptyState) {
            if (cart.length === 0) {
              emptyState.classList.remove("hidden");
            } else {
              emptyState.classList.add("hidden");
            }
          }

          // ===== Sync mobile cart =====
          syncMobileCart();
        }

        function renderCart() {
          const container = document.getElementById("cartItems");
          const emptyState = document.getElementById("emptyCart");

          container.innerHTML = "";

          if (cart.length === 0) {
            emptyState.classList.remove("hidden");
            recalculateTotals();
            return;
          }

          emptyState.classList.add("hidden");

          cart.forEach((item, index) => {
            const itemTotal = item.price * item.qty;

            container.innerHTML += `
     <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">

  <!-- Top row -->
  <div class="flex items-center gap-4">
    <!-- Image -->
    <div class="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden shrink-0">
      <div class="w-full h-full bg-cover bg-center"
           style="background-image:url('${item.image}')"></div>
    </div>

    <!-- Name + size + color -->
    <div class="flex-1 min-w-0">
      <h4 class="font-bold text-gray-800 truncate">${item.name}</h4>
      <div class="flex items-center gap-2 mt-1">
        <p class="text-xs text-gray-500">${translations.size}: ${item.size}</p>
        ${item.color ? `
          <span class="text-gray-400">•</span>
          <div class="flex items-center gap-1.5">
            <div class="w-4 h-4 rounded-full border border-gray-300" style="background-color: ${item.colorCode || '#000000'}"></div>
            <p class="text-xs text-gray-500">${item.color}</p>
          </div>
        ` : ''}
      </div>
    </div>
  </div>

  <!-- Prices -->
  <div class="flex justify-between text-sm text-gray-600">
    <span>${translations.unitPrice}</span>
    <span class="font-bold">${item.price.toFixed(2)} ${translations.omr}</span>
  </div>

  <div class="flex justify-between items-center">
    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-3 py-1">
      <button onclick="updateQty(${index}, -1)"
        class="w-7 h-7 rounded-full bg-white text-gray-600 hover:bg-gray-200">−</button>

      <span class="w-6 text-center font-bold">${item.qty}</span>

      <button onclick="updateQty(${index}, 1)"
        class="w-7 h-7 rounded-full bg-primary text-white">+</button>
    </div>

    <div class="text-right">
      <p class="text-xs text-gray-500">${translations.total}</p>
      <p class="font-extrabold text-primary">
        ${(item.price * item.qty).toFixed(2)} ${translations.omr}
      </p>
    </div>
  </div>

</div>





        
        </div>
      </div>
    `;
          });

          recalculateTotals();
        }

        function updateQty(index, change) {
          if (!cart[index]) return;

          const item = cart[index];
          const newQty = item.qty + change;

          // Check if trying to increase quantity beyond available
          if (change > 0) {
            const availableQty = parseInt(item.availableQty) || 0;
            if (availableQty > 0 && newQty > availableQty) {
              Swal.fire({
                icon: 'error',
                title: translations.quantityError || 'Quantity Error',
                text: (translations.quantityAvailable || 'Available quantity is') + ' ' + availableQty,
                confirmButtonText: translations.ok || 'OK',
                confirmButtonColor: '#1F6F67'
              });
              return;
            }
          }

          // Decrease quantity or remove if 0
          if (newQty <= 0) {
            cart.splice(index, 1);
          } else {
            item.qty = newQty;
          }

          renderCart();
          recalculateTotals();
        }

        function clearCart() {
          cart = [];
          renderCart();
          recalculateTotals();
        }

        /* ===============================
           DISCOUNT
        ================================ */
        function toggleDiscount() {
          const box = $("discountBox");
          const btn = $("discountBtn");
          if (!box) return;

          box.classList.toggle("hidden");

          // Focus style on the button
          if (btn) {
            btn.classList.toggle("bg-primary");
            btn.classList.toggle("text-white");
          }
        }

        function initDiscountHandlers() {
          const type = $("discountType");
          const value = $("discountValue");

          if (type) {
            type.onchange = function() {
              discount.type = this.value;
              recalculateTotals();
              updateDiscountDisplay();
            };
          }

          if (value) {
            value.oninput = function() {
              discount.value = parseFloat(this.value) || 0;
              recalculateTotals();
              updateDiscountDisplay();
            };
          }
        }

        function updateDiscountDisplay() {
          const subtotal = getCartSubtotal();
          const discountAmount = getDiscountAmount(subtotal);
          const discountAmountEl = $("discountAmount");

          if (discountAmountEl) {
            discountAmountEl.innerText = discountAmount.toFixed(2) + " " + translations.omr;
          }
        }

        /* ===============================
           PAYMENT MODAL
        ================================ */
        function openPaymentModal() {
          // Reset selected payment method when opening modal
          selectedPayMethod = null;
          
          if (!cart.length) {
            if (typeof Swal !== 'undefined') {
            Swal.fire({
  icon: 'error',
  title: translations.cartEmpty || "{{ trans('messages.cart_empty', [], session('locale')) }}",
  confirmButtonColor: '#1F6F67'
});
            } else if (typeof show_notification === 'function') {
              show_notification('error', translations.cartEmpty);
            } else {
              alert(translations.cartEmpty);
            }
            return;
          }

          // Set payment total from calculated total (not raw text)
          recalculateTotals();

          const modal = $("paymentModal");
          modal.classList.remove("hidden");
          modal.classList.add("flex");

          renderPaymentAccounts();
          renderPartialInputs();

          // Note: bindPaymentAccountButtons() is called inside renderPaymentAccounts()
          // so we don't need to call initPaymentButtons() here
          initOrderTypeButtons();
          initCustomerAutocomplete();
          initPartialPaymentInputs();
          
          // Always initialize area change listener when modal opens
          // Use a small delay to ensure DOM is ready
          setTimeout(() => {
            const areaSelect = document.getElementById('deliveryArea');
            if (areaSelect) {
              // Remove existing listener if any
              areaSelect.removeEventListener('change', handleAreaChange);
              // Attach the event listener
              areaSelect.addEventListener('change', handleAreaChange);
              console.log('Area change listener attached on modal open');
            } else {
              console.warn('deliveryArea select not found when trying to attach listener');
            }
          }, 50);
          
          // Initialize delivery charge listeners if delivery section is visible
          if (selectedOrderType === 'delivery') {
            initDeliveryChargeListeners();
          }
        }

        function updatePaymentModalAmounts(subtotal, discountAmount, payableAmount, deliveryCharge = 0) {
          // Update subtotal
          const subtotalEl = document.getElementById("paymentSubtotal");
          if (subtotalEl) {
            subtotalEl.innerText = subtotal.toFixed(3) + " " + translations.omr;
          }

          // Update discount (show/hide based on discount amount)
          const discountRow = document.getElementById("paymentDiscountRow");
          const discountEl = document.getElementById("paymentDiscount");
          if (discountRow && discountEl) {
            if (discountAmount > 0) {
              discountRow.classList.remove("hidden");
              discountEl.innerText = "-" + discountAmount.toFixed(3) + " " + translations.omr;
            } else {
              discountRow.classList.add("hidden");
            }
          }

          // Update delivery charge display (show raw charge, not the one added to total)
          const deliveryPriceEl = document.getElementById("deliveryPrice");
          if (deliveryPriceEl) {
            const orderType = selectedOrderType || 'direct';
            const wilayahSelect = document.getElementById('deliveryWilayah');
            let rawCharge = 0;
            if (orderType === 'delivery' && wilayahSelect && wilayahSelect.value) {
              const selectedOption = wilayahSelect.options[wilayahSelect.selectedIndex];
              rawCharge = parseFloat(selectedOption.dataset.charge || 0);
            }
            deliveryPriceEl.innerText = rawCharge.toFixed(3) + " " + translations.omr;
          }

          // Update payable amount (includes delivery if not paid)
          const paymentTotalEl = document.getElementById("paymentTotal");
          if (paymentTotalEl) {
            paymentTotalEl.innerText = payableAmount.toFixed(3) + " " + translations.omr;
          }
        }

        function closePaymentModal() {
          const modal = $("paymentModal");
          modal.classList.add("hidden");
          modal.classList.remove("flex");
        }

        /* Payment method buttons (cash/visa/transfer/partial) */
        function initPaymentButtons() {
          const buttons = document.querySelectorAll(".pay-btn");
          const partialBox = $("partialPaymentBox");

          buttons.forEach((btn) => {
            btn.onclick = () => {
              buttons.forEach((b) => b.classList.remove("active"));
              btn.classList.add("active");

              if (btn.dataset.method === "partial") {
                partialBox?.classList.remove("hidden");
              } else {
                partialBox?.classList.add("hidden");
              }
            };
          });

          // Default focus to visa
          document.querySelector('.pay-btn[data-method="visa"]')?.classList.add("active");
        }

        /* Order type buttons (direct/delivery) */
        function initOrderTypeButtons() {
          const buttons = document.querySelectorAll(".order-type-btn");
          const delivery = $("deliverySection");

          buttons.forEach((btn) => {
            btn.onclick = () => {
              buttons.forEach((b) => b.classList.remove("active"));
              btn.classList.add("active");

              if (btn.dataset.type === "delivery") {
                delivery?.classList.remove("hidden");
                // Initialize delivery charge listeners
                initDeliveryChargeListeners();
                // Also ensure area change listener is attached
                setTimeout(() => {
                  const areaSelect = document.getElementById('deliveryArea');
                  if (areaSelect) {
                    areaSelect.removeEventListener('change', handleAreaChange);
                    areaSelect.addEventListener('change', handleAreaChange);
                    console.log('Area change listener attached when delivery type selected');
                  }
                }, 50);
              } else {
                delivery?.classList.add("hidden");
              }
              // Recalculate totals when order type changes
              recalculateTotals();
            };
          });

          // Default direct
          document.querySelector('.order-type-btn[data-type="direct"]')?.classList.add("active");
          $("deliverySection")?.classList.add("hidden");
        }

        /* Initialize delivery charge listeners */
        function initDeliveryChargeListeners() {
          const wilayahSelect = document.getElementById('deliveryWilayah');
          const deliveryPaidCheckbox = document.getElementById('deliveryPaid');
          const areaSelect = document.getElementById('deliveryArea');

          // Update delivery charge when wilayah changes
          if (wilayahSelect) {
            wilayahSelect.removeEventListener('change', handleDeliveryChargeChange);
            wilayahSelect.addEventListener('change', handleDeliveryChargeChange);
          }

          // Update total when delivery paid checkbox changes
          if (deliveryPaidCheckbox) {
            deliveryPaidCheckbox.removeEventListener('change', handleDeliveryChargeChange);
            deliveryPaidCheckbox.addEventListener('change', handleDeliveryChargeChange);
          }

          // Load cities when area changes
          if (areaSelect) {
            // Remove existing listener to avoid duplicates
            areaSelect.removeEventListener('change', handleAreaChange);
            // Attach the event listener
            areaSelect.addEventListener('change', handleAreaChange);
            console.log('Area change listener attached in initDeliveryChargeListeners');
          }
        }
        
        // Make handleAreaChange globally accessible
        window.handleAreaChange = handleAreaChange;

        function handleDeliveryChargeChange() {
          recalculateTotals();
        }

        async function handleAreaChange() {
          const areaSelect = document.getElementById('deliveryArea');
          const wilayahSelect = document.getElementById('deliveryWilayah');
          
          if (!areaSelect || !wilayahSelect) {
            console.error('Area or Wilayah select not found');
            return;
          }
          
          const areaId = areaSelect.value;
          if (!areaId) {
            wilayahSelect.innerHTML = '<option value="">{{ trans('messages.select_wilayah', [], session('locale')) }}</option>';
            return;
          }

          // Show loading state
          wilayahSelect.disabled = true;
          wilayahSelect.innerHTML = '<option value="">{{ trans('messages.loading', [], session('locale')) }}...</option>';

          try {
            const baseUrl = '{{ url('pos/cities') }}';
            const url = `${baseUrl}?area_id=${encodeURIComponent(areaId)}`;
            console.log('Fetching cities from:', url);
            const res = await fetch(url, {
              method: 'GET',
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const cities = await res.json();
            console.log('Cities received:', cities);
            
            if (!Array.isArray(cities)) {
              console.error('Invalid response format:', cities);
              wilayahSelect.innerHTML = '<option value="">{{ trans('messages.select_wilayah', [], session('locale')) }}</option>';
              wilayahSelect.disabled = false;
              return;
            }
            
            wilayahSelect.innerHTML = '<option value="">{{ trans('messages.select_wilayah', [], session('locale')) }}</option>';
            
            if (cities.length === 0) {
              const noCitiesOption = document.createElement('option');
              noCitiesOption.value = '';
              noCitiesOption.textContent = '{{ trans('messages.no_cities_found', [], session('locale')) ?: 'No cities found' }}';
              wilayahSelect.appendChild(noCitiesOption);
            } else {
            const locale = '{{ session('locale', 'ar') }}';
            cities.forEach(city => {
              const option = document.createElement('option');
              option.value = city.id;
              option.dataset.charge = city.delivery_charges || 0;
              const cityName = locale === 'ar' ? (city.city_name_ar || city.city_name_en) : (city.city_name_en || city.city_name_ar);
              option.textContent = cityName + (city.delivery_charges ? ` - ${parseFloat(city.delivery_charges).toFixed(3)} ${translations.omr}` : '');
              wilayahSelect.appendChild(option);
            });
            }
            
            wilayahSelect.disabled = false;
            
            // Recalculate totals after cities are loaded (in case a city was previously selected)
            recalculateTotals();
          } catch (error) {
            console.error('Error loading cities:', error);
            wilayahSelect.innerHTML = '<option value="">{{ trans('messages.error_loading_cities', [], session('locale')) ?: 'Error loading cities' }}</option>';
            wilayahSelect.disabled = false;
          }
        }

        /* Partial payment remaining calculation */
        function initPartialPaymentInputs() {
          document.querySelectorAll('.partial-amount').forEach((el) => {
            el.removeEventListener('input', updatePartialRemaining);
            el.addEventListener('input', updatePartialRemaining);
          });
        }

        function updatePartialRemaining() {
          // Use payable amount (after discount) from payment modal
          const payableAmount = parseMoneyFromText($("paymentTotal")?.innerText || "0");
          let totalInputs = 0;
          document.querySelectorAll('.partial-amount').forEach((el) => {
            totalInputs += parseFloat(el.value || "0") || 0;
          });

          const remaining = payableAmount - totalInputs;
          if ($("partialRemaining")) {
            $("partialRemaining").innerText = `${Math.max(0, remaining).toFixed(2)} ${translations.omr}`;
          }
        }

        /* ===============================
           CUSTOMER AUTOCOMPLETE
        ================================ */
        function initCustomerAutocomplete() {
          const input = $("customerPhone");
          const box = $("customerSuggestions");
          const name = $("customerName");
          const selected = $("selectedCustomer");

          if (!input || !box) return;

          let searchTimeout;

          input.oninput = () => {
            box.innerHTML = "";

            const value = input.value.trim();
            if (!value) {
              box.classList.add("hidden");
              return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
              try {
                const res = await fetch(`{{ route('pos.customers.search') }}?search=${encodeURIComponent(value)}`);
                const data = await res.json();

                if (!Array.isArray(data) || data.length === 0) {
                  box.classList.add("hidden");
                  return;
                }

                data.forEach((c) => {
                  const div = document.createElement("div");
                  div.className = "p-3 hover:bg-gray-50 cursor-pointer text-sm";
                  const displayName = c.name || "{{ trans('messages.customer', [], session('locale')) }}";
                  div.innerText = `${displayName} – ${c.phone || ''}`;
                  div.onclick = () => {
                    if (name) name.value = c.name || '';
                    input.value = c.phone || '';

                    // fill area / wilayah if available
                    const areaSelect = document.getElementById('deliveryArea');
                    const wilSelect = document.getElementById('deliveryWilayah');
                    if (areaSelect && c.governorate) {
                      areaSelect.value = c.governorate;
                    }
                    if (wilSelect && c.area) {
                      wilSelect.value = c.area;
                    }

                    box.classList.add("hidden");

                    if (selected) {
                      selected.classList.remove("hidden");
                      selected.innerHTML = `
                <strong>{{ trans('messages.customer', [], session('locale')) }}:</strong> ${displayName}<br/>
                <span class="text-sm text-gray-500">${c.phone || ''}</span>
              `;
                    }
                  };
                  box.appendChild(div);
                });

                box.classList.remove("hidden");

                setTimeout(() => {
                  box.scrollIntoView({
                    behavior: "smooth",
                    block: "nearest"
                  });
                }, 50);
              } catch (error) {
                console.error('Customer search error:', error);
                box.classList.add("hidden");
              }
            }, 250); // debounce
          };
        }

        /* ===============================
           SUSPEND INVOICE (FLY + BADGE + LIST)
        ================================ */
        function updateSuspendedBadge() {
          const badge = $("suspendedBadge");
          const count = suspendedInvoices.length;

          if (!badge) return;

          if (count > 0) {
            badge.innerText = count;
            badge.classList.remove("hidden");
          } else {
            badge.classList.add("hidden");
          }
        }

        function suspendCurrentCart() {
          if (!cart.length) {
            alert(translations.cartEmpty);
            return;
          }

          // Fly animation target = notification button
          const target = $("notificationBtn");
          const targetRect = target.getBoundingClientRect();
          const cartRect = $("cartItems").getBoundingClientRect();

          cart.forEach((item, idx) => {
            const fly = document.createElement("div");
            fly.className = "fly-item";
            fly.style.backgroundImage = `url(${item.image})`;
            fly.style.left = cartRect.left + 40 + "px";
            fly.style.top = cartRect.top + 40 + idx * 18 + "px";
            document.body.appendChild(fly);

            requestAnimationFrame(() => {
              fly.style.transform = `
        translate(${targetRect.left - cartRect.left}px, ${targetRect.top - cartRect.top}px) scale(0.2)
      `;
              fly.style.opacity = "0";
            });

            setTimeout(() => fly.remove(), 900);
          });

          // Generate order number: YYYYMM-random number
          const now = new Date();
          const year = now.getFullYear();
          const month = String(now.getMonth() + 1).padStart(2, '0');
          const randomNum = Math.floor(Math.random() * 10000);
          const orderNumber = `${year}${month}-${randomNum}`;

          // Format date and time
          const day = String(now.getDate()).padStart(2, '0');
          const monthStr = String(now.getMonth() + 1).padStart(2, '0');
          const yearStr = now.getFullYear();
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          const seconds = String(now.getSeconds()).padStart(2, '0');
          const dateTime = `${day}/${monthStr}/${yearStr} ${hours}:${minutes}:${seconds}`;

          // Calculate total amount
          const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
          const discountAmount = getDiscountAmount(subtotal);
          const totalAmount = subtotal - discountAmount;

          // Save invoice snapshot
          suspendedInvoices.push({
            id: orderNumber,
            orderNumber: orderNumber,
            date: dateTime,
            items: JSON.parse(JSON.stringify(cart)),
            discount: JSON.parse(JSON.stringify(discount)),
            subtotal: subtotal,
            discountAmount: discountAmount,
            total: totalAmount,
            totalFormatted: formatMoney(totalAmount)
          });

          // Shake notification
          target.classList.add("shake");
          setTimeout(() => target.classList.remove("shake"), 600);

          updateSuspendedBadge();

          // Clear cart
          cart = [];
          renderCart();
          recalculateTotals();
        }

        function openSuspendedModal() {
          const modal = $("suspendedModal");
          const list = $("suspendedList");

          if (!modal || !list) return;

          list.innerHTML = "";

          if (!suspendedInvoices.length) {
            list.innerHTML = `
      <div class="p-6 text-center text-gray-500">
        ${translations.noSuspendedInvoices}
      </div>
    `;
          } else {
            suspendedInvoices.forEach((inv, i) => {
              const totalItems = inv.items.reduce((s, x) => s + x.qty, 0);
              const itemsList = inv.items.map(item =>
                `${item.name} (${item.size}${item.color ? ', ' + item.color : ''}) x${item.qty}`
              ).join(', ');

              list.innerHTML += `
        <div class="p-4 border rounded-xl bg-white hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-3">
            <div class="flex-1">
              <p class="font-bold text-lg text-gray-800 mb-1">${translations.orderNumber || 'Order'}: ${inv.orderNumber || inv.id}</p>
              <p class="text-xs text-gray-500 mb-2">
                <span class="material-symbols-outlined text-xs align-middle">schedule</span>
                ${inv.date}
              </p>
            </div>
            <div class="text-right ml-4">
              <p class="font-bold text-xl text-primary">${inv.totalFormatted || inv.total}</p>
            </div>
          </div>
          
          <div class="mb-3 pt-3 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-600 mb-2">${translations.items} (${totalItems}):</p>
            <div class="text-xs text-gray-600 space-y-1 max-h-20 overflow-y-auto">
              ${inv.items.map(item => `
                <div class="flex items-center gap-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                  <span>${item.name} - ${item.size}${item.color ? ' (' + item.color + ')' : ''} x${item.qty}</span>
                </div>
              `).join('')}
            </div>
          </div>
          
          <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <button onclick="restoreInvoice(${i})"
              class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-dark transition-colors">
              ${translations.restore}
            </button>
          </div>
        </div>
      `;
            });
          }

          modal.classList.remove("hidden");
          modal.classList.add("flex");
        }

        function closeSuspendedModal() {
          const modal = $("suspendedModal");
          modal?.classList.add("hidden");
          modal?.classList.remove("flex");
        }

        function restoreInvoice(index) {
          const inv = suspendedInvoices[index];
          if (!inv) return;

          cart = inv.items || [];
          discount = inv.discount || {
            type: "percent",
            value: 0
          };

          suspendedInvoices.splice(index, 1);

          // Reflect discount UI if box exists
          if ($("discountType")) $("discountType").value = discount.type;
          if ($("discountValue")) $("discountValue").value = discount.value;

          updateSuspendedBadge();
          renderCart();
          recalculateTotals();
          closeSuspendedModal();
        }

        /* ===============================
           BARCODE → OPEN PRODUCT MODAL (DEMO)
        ================================ */
        function initBarcode() {
          const input = $("barcodeInput");
          if (!input) return;

          // Search functionality - filter products as user types
          input.addEventListener("input", function() {
            const searchTerm = this.value.trim().toLowerCase();
            const products = document.querySelectorAll(".product-item");

            // Get active category filter
            const activeTab = document.querySelector(".category-tab.active");
            const activeFilter = activeTab ? activeTab.dataset.filter : "all";

            if (!searchTerm) {
              // Show all products based on category filter when search is empty
              products.forEach((p) => {
                const cat = p.dataset.category || "";
                if (activeFilter === "all" || cat === activeFilter) {
                  p.classList.remove("hidden");
                } else {
                  p.classList.add("hidden");
                }
              });
              return;
            }

            products.forEach((product) => {
              const barcode = (product.dataset.barcode || "").toLowerCase();
              const abayaCode = (product.dataset.abayaCode || "").toLowerCase();
              const designName = (product.dataset.designName || "").toLowerCase();
              const name = (product.dataset.name || "").toLowerCase();

              // Check if search term matches barcode, abaya code, or name
              const searchMatch =
                barcode.includes(searchTerm) ||
                abayaCode.includes(searchTerm) ||
                designName.includes(searchTerm) ||
                name.includes(searchTerm);

              // Check category filter
              const cat = product.dataset.category || "";
              const categoryMatch = (activeFilter === "all" || cat === activeFilter);

              // Show product only if both category and search match
              if (searchMatch && categoryMatch) {
                product.classList.remove("hidden");
              } else {
                product.classList.add("hidden");
              }
            });
          });

          // Enter key - open product modal if exact match found
          input.addEventListener("keydown", (e) => {
            if (e.key !== "Enter") return;

            const searchTerm = input.value.trim();
            if (!searchTerm) return;

            // Try to find exact match by barcode first
            let productItem = document.querySelector(`.product-item[data-barcode="${searchTerm}"]`);

            // If not found by barcode, try abaya code
            if (!productItem) {
              productItem = document.querySelector(`.product-item[data-abaya-code="${searchTerm}"]`);
            }

            // If still not found, try to find first visible product
            if (!productItem) {
              productItem = document.querySelector(".product-item:not(.hidden)");
            }

            if (!productItem) {
              alert(translations.productNotFound);
              input.value = "";
              return;
            }

            const product = {
              id: productItem.dataset.id,
              name: productItem.dataset.name,
              price: parseFloat(productItem.dataset.price),
              image: productItem.dataset.image
            };

            playBeep();
            openProductModal(product);
            input.value = "";

            // Reset search filter but respect category filter
            const activeTab = document.querySelector(".category-tab.active");
            const activeFilter = activeTab ? activeTab.dataset.filter : "all";

            document.querySelectorAll(".product-item").forEach((p) => {
              const cat = p.dataset.category || "";
              if (activeFilter === "all" || cat === activeFilter) {
                p.classList.remove("hidden");
              } else {
                p.classList.add("hidden");
              }
            });
          });
        }

        /* ===============================
           CATEGORY TABS FILTERING
        ================================ */
        function initCategoryTabs() {
          const tabs = document.querySelectorAll(".category-tab");
          const products = document.querySelectorAll(".product-item");

          if (!tabs.length) return;

          tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
              tabs.forEach((t) => t.classList.remove("active"));

              tab.classList.add("active");
              tab.focus();

              const filter = tab.dataset.filter;
              const searchInput = $("barcodeInput");
              const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : "";

              products.forEach((p) => {
                const cat = p.dataset.category || "";
                const barcode = (p.dataset.barcode || "").toLowerCase();
                const abayaCode = (p.dataset.abayaCode || "").toLowerCase();
                const designName = (p.dataset.designName || "").toLowerCase();
                const name = (p.dataset.name || "").toLowerCase();

                // Check category filter
                let categoryMatch = false;
                if (filter === "all") {
                  categoryMatch = true;
                } else {
                  categoryMatch = (cat === filter);
                }

                // Check search filter
                let searchMatch = true;
                if (searchTerm) {
                  searchMatch =
                    barcode.includes(searchTerm) ||
                    abayaCode.includes(searchTerm) ||
                    designName.includes(searchTerm) ||
                    name.includes(searchTerm);
                }

                // Show product only if both category and search match
                if (categoryMatch && searchMatch) {
                  p.classList.remove("hidden");
                } else {
                  p.classList.add("hidden");
                }
              });
            });
          });

          // Default active tab
          const defaultTab = document.querySelector('.category-tab[data-filter="all"]');
          if (defaultTab) defaultTab.classList.add("active");
        }

        /* ===============================
           BIND UI EVENTS
        ================================ */
        function bindProductsClick() {
          document.querySelectorAll(".product-item").forEach((item) => {
            item.addEventListener("click", () => {
              openProductModal({
                id: item.dataset.id,
                name: item.dataset.name,
                price: parseFloat(item.dataset.price),
                image: item.dataset.image
              });
            });
          });
        }

        function bindSizeColorButtons() {
          document.querySelectorAll(".size-btn").forEach((btn) => {
            btn.onclick = () => {
              document.querySelectorAll(".size-btn").forEach((b) => b.classList.remove("bg-primary", "text-white"));
              btn.classList.add("bg-primary", "text-white");
              selectedSize = btn.innerText.trim();
            };
          });

          document.querySelectorAll(".color-btn").forEach((btn) => {
            btn.onclick = () => {
              document.querySelectorAll(".color-btn").forEach((b) => b.classList.remove("ring-2", "ring-primary"));

              btn.classList.add("ring-2", "ring-primary");
              selectedColor = getComputedStyle(btn).backgroundColor;

              // 📱 Mobile UX: auto confirm if size is selected
              if (window.innerWidth < 768 && selectedSize) {
                confirmAddToCart();
              }
            };
          });
        }

        function bindSuspendButton() {
          const btn = $("suspendBtn");
          if (!btn) return;

          btn.addEventListener("click", suspendCurrentCart);
        }

        /* ===============================
           INIT
        ================================ */
        document.addEventListener("DOMContentLoaded", () => {
          // Ensure cart starts empty
          cart = [];

          // Load accounts for payment/partial
          fetchAccounts().then(() => {
            renderPaymentAccounts();
            renderPartialInputs();
          });

          bindProductsClick();
          bindSizeColorButtons();

          initDiscountHandlers();
          initBarcode();
          initCategoryTabs();
          bindSuspendButton();

          updateSuspendedBadge();
          renderCart();
          recalculateTotals();
        });

        function openCartMobile() {
          const modal = document.getElementById("cartMobile");
          if (!modal) return;

          modal.classList.remove("hidden");

          syncMobileCart();

          // scroll
          document.body.classList.add("modal-open");
        }

        function closeCartMobile() {
          const modal = document.getElementById("cartMobile");
          if (!modal) return;

          modal.classList.add("hidden");

          // 🔓 رجّع سكرول الصفحة
          document.body.classList.remove("modal-open");
        }

        /* ===============================
           EXPOSE FUNCTIONS FOR HTML onclick
        ================================ */
        window.changeQty = changeQty;
        window.closeModal = closeModal;
        window.confirmAddToCart = confirmAddToCart;

        window.openPaymentModal = openPaymentModal;
        window.closePaymentModal = closePaymentModal;

        window.toggleDiscount = toggleDiscount;
        window.clearCart = clearCart;

        window.openSuspendedModal = openSuspendedModal;
        window.closeSuspendedModal = closeSuspendedModal;
        window.restoreInvoice = restoreInvoice;

        /* ===============================
           ACCOUNTS & PAYMENT RENDERING
        ================================ */
        function fetchAccounts() {
          return fetch(`{{ url('accounts/all') }}`)
            .then((res) => res.json())
            .then((data) => {
              if (Array.isArray(data)) {
                accountsList = data;
              }
            })
            .catch((err) => {
              console.error('Error loading accounts:', err);
            });
        }

        function renderPaymentAccounts() {
          const container = document.getElementById('paymentAccounts');
          if (!container) return;

          container.innerHTML = '';

          const list = accountsList && accountsList.length ? accountsList : [];

          if (!list.length) {
            container.innerHTML = `<div class="col-span-4 text-center text-gray-400 text-xs py-3">{{ trans('messages.no_results', [], session('locale')) }}</div>`;
            return;
          }

          const iconForAccount = (acc) => {
            const name = (acc.account_name || '').toLowerCase();
            if (name.includes('cash')) return 'payments';
            if (name.includes('visa') || name.includes('card')) return 'credit_card';
            if (name.includes('bank') || name.includes('transfer')) return 'account_balance';
            return 'account_balance_wallet';
          };

          list.forEach((acc) => {
            const btn = document.createElement('button');
            btn.className = 'pay-btn';
            btn.dataset.method = acc.id;
            const icon = iconForAccount(acc);
            btn.innerHTML = `
      <span class="material-symbols-outlined">${icon}</span>
      ${acc.account_name || acc.account_no || ('#' + acc.id)}
    `;
            container.appendChild(btn);
          });

          // Add partial payment toggle
          const partialBtn = document.createElement('button');
          partialBtn.className = 'pay-btn';
          partialBtn.dataset.method = 'partial';
          partialBtn.innerHTML = `
    <span class="material-symbols-outlined">call_split</span>
    {{ trans('messages.partial_payment', [], session('locale')) }}
  `;
          container.appendChild(partialBtn);

          bindPaymentAccountButtons();
        }

        function bindPaymentAccountButtons() {
          const buttons = document.querySelectorAll("#paymentAccounts .pay-btn");
          const partialBox = $("partialPaymentBox");
          const singleBox = $("singlePaymentBox");
          const singleInput = $("singlePaymentAmount");

          buttons.forEach((btn) => {
            btn.onclick = function() {
              // Remove active from all buttons
              buttons.forEach((b) => b.classList.remove("active"));
              // Add active to clicked button
              btn.classList.add("active");

              // IMPORTANT: Update selectedPayMethod with the account ID from the button
              selectedPayMethod = btn.dataset.method;

              if (selectedPayMethod === 'partial') {
                partialBox?.classList.remove("hidden");
                singleBox?.classList.add("hidden");
              } else {
                partialBox?.classList.add("hidden");
                if (singleBox) {
                  singleBox.classList.remove("hidden");
                  const payable = parseMoneyFromText($("paymentTotal")?.innerText || "0");
                  if (singleInput) {
                    singleInput.value = payable.toFixed(3);
                  }
                }
              }
            };
          });

          // Set default selection only if no method is currently selected
          // This preserves user's selection if they clicked a button
          if (buttons.length && selectedPayMethod === null) {
            // Find first non-partial button for default
            const defaultBtn = Array.from(buttons).find(btn => btn.dataset.method !== 'partial') || buttons[0];
            if (defaultBtn) {
              defaultBtn.classList.add('active');
              selectedPayMethod = defaultBtn.dataset.method;

              // Trigger display for default selection
              if (selectedPayMethod === 'partial') {
                partialBox?.classList.remove("hidden");
                singleBox?.classList.add("hidden");
              } else {
                partialBox?.classList.add("hidden");
                singleBox?.classList.remove("hidden");
                const payable = parseMoneyFromText($("paymentTotal")?.innerText || "0");
                if (singleInput) {
                  singleInput.value = payable.toFixed(3);
                }
              }
            }
          }
        }

        function renderPartialInputs() {
          const container = document.getElementById('partialAccounts');
          if (!container) return;

          container.innerHTML = '';
          container.className = 'flex flex-wrap gap-2';

          const list = accountsList && accountsList.length ? accountsList : [];

          if (!list.length) {
            container.innerHTML = `<div class="text-gray-400 text-xs py-2">{{ trans('messages.no_results', [], session('locale')) }}</div>`;
            return;
          }

          list.forEach((acc) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 bg-gray-50 rounded-lg px-2 py-1';
            row.innerHTML = `
      <div class="text-xs font-semibold text-gray-700 truncate max-w-[140px]">${acc.account_name || acc.account_no || ('#' + acc.id)}</div>
      <input type="number"
             data-account-id="${acc.id}"
             class="w-32 h-9 text-xs rounded-lg border px-2 partial-amount"
             placeholder="{{ trans('messages.enter_amount', [], session('locale')) }}">
    `;
            container.appendChild(row);
          });

          initPartialPaymentInputs();
          updatePartialRemaining();
        }

        /* ===============================
           VALIDATE CUSTOMER BEFORE SUBMIT
        ================================ */
        document.addEventListener('DOMContentLoaded', () => {
          const confirmBtn = document.getElementById('confirmPaymentBtn');
          if (confirmBtn) {
            confirmBtn.addEventListener('click', (e) => {
              const phone = document.getElementById('customerPhone')?.value?.trim();
              const name = document.getElementById('customerName')?.value?.trim();

              if (!phone || !name) {
                e.preventDefault();
             if (typeof Swal !== 'undefined') {
  Swal.fire({
    icon: 'error',
    title: "{{ trans('messages.customer_data', [], session('locale')) }}",
    text: "{{ trans('messages.customer_name', [], session('locale')) }} / {{ trans('messages.phone_number', [], session('locale')) }}",
    confirmButtonColor: '#1F6F67'
  });
} else {
  alert("{{ trans('messages.customer_name', [], session('locale')) }} / {{ trans('messages.phone_number', [], session('locale')) }}");
}

return;
              }

              // If a submitPosOrder exists (custom flow), call it
              if (typeof submitPosOrder === 'function') {
                e.preventDefault();
                submitPosOrder();
              }
            });
          }
        });

        /* ===============================
           BUTTON STATE MANAGEMENT
        ================================ */
        function setConfirmButtonLoading(isLoading) {
          const confirmBtn = document.getElementById('confirmPaymentBtn');
          if (!confirmBtn) return;

          // Store original HTML if not already stored
          if (!confirmBtn.dataset.originalHtml) {
            confirmBtn.dataset.originalHtml = confirmBtn.innerHTML;
          }

          if (isLoading) {
            // Disable button and show loading state
            confirmBtn.disabled = true;
            confirmBtn.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
            confirmBtn.style.transition = 'all 0.3s ease';
            confirmBtn.innerHTML = `
              <span class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ trans('messages.processing', [], session('locale')) ?: 'Processing...' }}</span>
              </span>
            `;
          } else {
            // Re-enable button and restore original text
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
            confirmBtn.innerHTML = confirmBtn.dataset.originalHtml || "{{ trans('messages.confirm_payment', [], session('locale')) }}";
          }
        }
      </script>

      <script>
        // Additional POS submission helpers
        let selectedOrderType = 'direct';

        document.addEventListener('DOMContentLoaded', () => {
          const orderButtons = document.querySelectorAll('.order-type-btn');
          orderButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
              orderButtons.forEach((b) => b.classList.remove('active'));
              btn.classList.add('active');
              selectedOrderType = btn.dataset.type || 'direct';
            });
          });
        });

        function buildPaymentsPayload(payableAmount) {
          const payments = [];

          // Collect partial entries regardless of selection
          const partialEntries = [];
          document.querySelectorAll('.partial-amount').forEach((el) => {
            const val = parseFloat(el.value || '0');
            const accountId = el.dataset.accountId;
            if (val > 0 && accountId) {
              partialEntries.push({
                account_id: Number(accountId),
                amount: val,
                label: 'partial'
              });
            }
          });

          if (partialEntries.length > 0) {
            const sum = partialEntries.reduce((s, p) => s + p.amount, 0);
            if (sum - payableAmount > 0.0001) {
              Swal.fire({
                icon: 'error',
                title: "{{ trans('messages.payment_method', [], session('locale')) }}",
                text: "{{ trans('messages.amount_exceeds_remaining', [], session('locale')) ?: 'Amount exceeds payable' }}"
              });
              return [];
            }
            return partialEntries;
          }

          // Fallback to single payment
          if (selectedPayMethod && selectedPayMethod !== 'partial') {
            let amount = payableAmount;
            const single = document.getElementById('singlePaymentAmount');
            if (single && single.value) {
              amount = parseFloat(single.value) || payableAmount;
            }
            if (amount - payableAmount > 0.0001) {
              Swal.fire({
                icon: 'error',
                title: "{{ trans('messages.payment_method', [], session('locale')) }}",
                text: "{{ trans('messages.amount_exceeds_remaining', [], session('locale')) ?: 'Amount exceeds payable' }}"
              });
              return [];
            }
            
            // Convert to number and validate
            const accountId = Number(selectedPayMethod);
            if (isNaN(accountId) || accountId <= 0) {
              Swal.fire({
                icon: 'error',
                title: "{{ trans('messages.payment_method', [], session('locale')) }}",
                text: "{{ trans('messages.invalid_account', [], session('locale')) ?: 'Invalid account selected' }}"
              });
              return [];
            }
            payments.push({
              account_id: accountId,
              amount: amount,
              label: 'full'
            });
          }

          return payments;
        }

        function buildItemsPayload() {
          return cart.map((item) => {
            // Handle color_id - convert to number if valid, otherwise null
            let colorId = null;
            if (item.colorId !== null && item.colorId !== undefined && item.colorId !== '' && item.colorId !== 'null' && item.colorId !== 'undefined') {
              const parsed = parseInt(item.colorId, 10);
              if (!isNaN(parsed) && parsed > 0) {
                colorId = parsed;
              }
            }
            
            // Handle size_id - convert to number if valid, otherwise null
            let sizeId = null;
            if (item.sizeId !== null && item.sizeId !== undefined && item.sizeId !== '' && item.sizeId !== 'null' && item.sizeId !== 'undefined') {
              const parsed = parseInt(item.sizeId, 10);
              if (!isNaN(parsed) && parsed > 0) {
                sizeId = parsed;
              }
            }
            
            return {
              id: Number(item.id),
              barcode: item.barcode || '',
              abaya_code: item.abaya_code || '',
              qty: Number(item.qty),
              price: Number(item.price),
              color_id: colorId,
              size_id: sizeId,
              line_total: Number(item.price) * Number(item.qty)
            };
          });
        }

        async function submitPosOrder() {
          // Disable button immediately on click
          setConfirmButtonLoading(true);

          try {
            if (!cart.length) {
              Swal.fire({ icon: 'error', title: translations.cartEmpty || 'Cart empty' });
              setConfirmButtonLoading(false);
              return;
            }

            const subtotal = getCartSubtotal();
            const discountAmount = getDiscountAmount(subtotal);
            
            // Get delivery charge using the helper function (which checks if paid)
            const deliveryCharge = getDeliveryCharge();
            const wilayahSelect = document.getElementById('deliveryWilayah');
            const deliveryPaidCheckbox = document.getElementById('deliveryPaid');
            const orderType = selectedOrderType || 'direct';
            
            // Get raw delivery charge (before checking if paid) for saving to DB
            let rawDeliveryCharge = 0;
            let deliveryPaid = false;
            
            if (orderType === 'delivery' && wilayahSelect && wilayahSelect.value) {
              const selectedOption = wilayahSelect.options[wilayahSelect.selectedIndex];
              rawDeliveryCharge = parseFloat(selectedOption.dataset.charge || 0);
              deliveryPaid = deliveryPaidCheckbox ? deliveryPaidCheckbox.checked : false;
            }
            
            // Total includes delivery charge only if not paid (getDeliveryCharge already handles this)
            const total = subtotal - discountAmount + deliveryCharge;

            // Use the final payable amount from payment modal (which already includes delivery if not paid)
            const finalPayable = parseMoneyFromText($("paymentTotal")?.innerText || "0");
            const payments = buildPaymentsPayload(finalPayable);
            if (!payments.length) {
              Swal.fire({ icon: 'error', title: "{{ trans('messages.select', [], session('locale')) }}", text: "{{ trans('messages.payment_method', [], session('locale')) }}" });
              setConfirmButtonLoading(false);
              return;
            }

            const customerPayload = {
              name: document.getElementById('customerName')?.value || '',
              phone: document.getElementById('customerPhone')?.value || '',
              address: document.getElementById('deliveryAddress')?.value || '',
              area: document.getElementById('deliveryArea')?.value || '',
              wilayah: document.getElementById('deliveryWilayah')?.value || ''
            };

            const payload = {
              items: buildItemsPayload(),
              payments,
              totals: {
                subtotal,
                discount: discountAmount,
                total: finalPayable, // Use final payable which includes delivery if not paid
                delivery_charges: rawDeliveryCharge, // Raw delivery charge for DB
                delivery_paid: deliveryPaid
              },
              discount: {
                type: discount.type,
                value: discount.value
              },
              order_type: orderType,
              notes: document.getElementById('deliveryAddress')?.value || null,
              customer: customerPayload,
            };

            const res = await fetch(`{{ url('pos/orders') }}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
              },
              credentials: 'same-origin',
              body: JSON.stringify(payload)
            });

            if (!res.ok) {
              const text = await res.text();
              throw new Error(text || 'Request failed');
            }

            const data = await res.json();
            if (data.success) {
              // Show success message
              Swal.fire({ 
                icon: 'success', 
                title: "{{ trans('messages.confirm_payment', [], session('locale')) }}",
                showCancelButton: true,
                confirmButtonText: "{{ trans('messages.print', [], session('locale')) }}",
                cancelButtonText: "{{ trans('messages.close', [], session('locale')) }}"
              }).then((result) => {
                if (result.isConfirmed && data.order_id) {
                  // Open pos_bill in new window for printing
                  window.open(`{{ url('pos_bill') }}?order_id=${data.order_id}`, '_blank');
                }
              });
              
              cart = [];
              renderCart();
              recalculateTotals();
              closePaymentModal();
              
              // Re-enable button after successful completion
              setConfirmButtonLoading(false);
            } else {
              Swal.fire({ icon: 'error', title: data.message || 'Error saving order' });
              setConfirmButtonLoading(false);
            }
          } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Failed to save order' });
            setConfirmButtonLoading(false);
          }
        }

        window.submitPosOrder = submitPosOrder;
      </script>