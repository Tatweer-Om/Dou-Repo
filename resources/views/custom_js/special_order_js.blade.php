<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('tailorApp', () => ({
    showModal: false,
    showPaymentModal: false,
    shipping_fee: 0,
    governorates: [],
    availableCities: [],
    areasMap: [], // [{id,name}]
    areaCityMap: {
      'مسقط': ['السيب', 'بوشر', 'مطرح'],
      'الداخلية': ['نزوى', 'بهلاء', 'الحمراء'],
      'الشرقية': ['إبراء', 'صور', 'بدية'],
    },
    loading: false,
    savedOrderId: null,
    paymentAmount: '',
    selectedAccountId: '',
    accounts: [],
    paymentProcessing: false,
    paymentError: '',
    orderSubmitted: false,
    customer: { 
      source: '', 
      name: '', 
      phone: '', 
      governorate_id: '', 
      city_id: '', 
      address: '',
      is_gift: 'no', 
      gift_message: '' 
    },
    customerSuggestions: [],
    orders: [{ 
      id: 1, 
      stock_id: null,
      abaya_code: '',
      design_name: '',
      quantity: 1, 
      price: 0, 
      length: '', 
      bust: '', 
      sleeves: '', 
      buttons: 'yes', 
      notes: '' 
    }],

    async init() {
      await this.fetchAreas();
      // Fallback to static map keys if API returns empty
      if (this.governorates.length === 0) {
        this.governorates = Object.keys(this.areaCityMap).map(name => ({id: name, name}));
      }
      await this.loadAccounts();
    },

    async loadAccounts() {
      try {
        const response = await fetch('{{ url('accounts/all') }}');
        const data = await response.json();
        if (Array.isArray(data)) {
          this.accounts = data;
        }
      } catch (error) {
        console.error('Error loading accounts:', error);
      }
    },

    async fetchAreas() {
      try {
        const response = await fetch('{{ url('areas/all') }}');
        const data = await response.json();
        if (Array.isArray(data)) {
          this.areasMap = data.map(a => ({
            id: a.id,
            name: a.area_name_ar || a.area_name_en
          })).filter(a => !!a.name);
          this.governorates = this.areasMap;
        }
      } catch (error) {
        console.error('Error loading areas list:', error);
      }
    },

    getGovernorateName(id) {
      if (!id) return '';
      const area = this.areasMap.find(a => a.id == id);
      return area ? area.name : '';
    },

    getCityName(id) {
      if (!id) return '';
      const city = this.availableCities.find(c => c.id == id);
      return city ? city.name : '';
    },

    calculateTotal() {
      let subtotal = 0;
      this.orders.forEach(order => {
        subtotal += (parseFloat(order.price) || 0) * (parseInt(order.quantity) || 1);
      });
      return subtotal + (parseFloat(this.shipping_fee) || 0);
    },

    updateCities(areaId) {
      // Reset city selection first
      this.customer.city = '';
      this.customer.city_id = '';
      this.availableCities = [];
      
      this.customer.governorate_name = this.areasMap.find(a => a.id == areaId || String(a.id) === String(areaId))?.name || '';
      this.customer.governorate_id = String(areaId || '');

      if (areaId) {
        this.fetchCities(areaId);
      } else {
        // fallback to static map if no area id match
        this.availableCities = (this.areaCityMap[this.customer.governorate_name] || []).map(n => ({id: n, name: n, charge: 0}));
        this.updateShipping();
      }
    },

    async fetchCities(areaId) {
      try {
        const response = await fetch(`{{ url('pos/cities') }}?area_id=${areaId}`);
        const data = await response.json();
        if (Array.isArray(data)) {
          this.availableCities = data.map(c => ({
            id: c.id,
            name: c.city_name_ar || c.city_name_en,
            charge: Number(c.delivery_charges || 0)
          })).filter(c => !!c.name);
        }
      } catch (error) {
        console.error('Error loading cities:', error);
        this.availableCities = [];
      } finally {
        this.updateShipping();
      }
    },
    
    selectCity(cityId) {
      if (!cityId) {
        this.customer.city_id = '';
        this.customer.city = '';
        this.shipping_fee = 0;
        return;
      }
      
      // Compare as strings to handle type mismatches
      const city = this.availableCities.find(c => String(c.id) === String(cityId));
      if (city) {
        this.customer.city_id = String(cityId);
        this.customer.city = city.name;
        this.shipping_fee = parseFloat(city.charge) || 0;
      } else {
        // If city not found, still set the ID (might be loading)
        this.customer.city_id = String(cityId);
        this.shipping_fee = 0;
      }
    },
    
    updateShipping() {
      // Update shipping fee based on selected city
      if (this.customer.city_id) {
        const city = this.availableCities.find(c => String(c.id) === String(this.customer.city_id));
        this.shipping_fee = city ? (parseFloat(city.charge) || 0) : 0;
      } else {
        this.shipping_fee = 0;
      }
    },
    
    async searchCustomers() {
      const phone = this.customer.phone?.trim() || '';
      
      if (phone.length < 2) {
        this.customerSuggestions = [];
        return;
      }
      
      try {
        const response = await fetch(`{{ route('pos.customers.search') }}?search=${encodeURIComponent(phone)}`);
        const data = await response.json();
        this.customerSuggestions = Array.isArray(data) ? data : [];
      } catch (error) {
        console.error('Error searching customers:', error);
        this.customerSuggestions = [];
      }
    },
    
    async selectCustomer(customerItem) {
      // Fill customer data from selected customer
      this.customer.phone = customerItem.phone || '';
      this.customer.name = customerItem.name || '';
      this.customer.address = customerItem.address || '';
      
      // Clear suggestions first
      this.customerSuggestions = [];
      
      // Fill area/governorate if available (area_id in customer = governorate_id in form)
      if (customerItem.area_id) {
        // Try to find matching area in governorates list (compare as strings to handle type mismatches)
        const matchingArea = this.areasMap.find(a => String(a.id) === String(customerItem.area_id));
        if (matchingArea) {
          this.customer.governorate_id = String(customerItem.area_id);
          this.customer.governorate_name = matchingArea.name;
          
          // Update cities for this area (this will fetch cities asynchronously)
          await this.updateCitiesAsync(customerItem.area_id);
          
          // After cities are loaded, select the city if available
          if (customerItem.city_id) {
            // Wait for Alpine to update the DOM with new cities
            await this.$nextTick();
            
            // Try multiple times to find the city (with retries)
            let attempts = 0;
            const maxAttempts = 5;
            const findAndSelectCity = () => {
              attempts++;
              // Try to find the city (compare as strings and also try as numbers)
              let matchingCity = this.availableCities.find(c => String(c.id) === String(customerItem.city_id));
              if (!matchingCity) {
                // Try comparing as numbers
                matchingCity = this.availableCities.find(c => Number(c.id) === Number(customerItem.city_id));
              }
              
              if (matchingCity) {
                // Set city_id directly (ensure it's the same type as the option value)
                this.customer.city_id = String(matchingCity.id);
                // Call selectCity to update shipping fee
                this.selectCity(String(matchingCity.id));
                return true;
              } else if (attempts < maxAttempts && this.availableCities.length > 0) {
                // Retry after a short delay if cities are loaded but city not found yet
                setTimeout(() => {
                  this.$nextTick().then(() => findAndSelectCity());
                }, 100);
                return false;
              } else {
                console.warn('City not found after', attempts, 'attempts:', customerItem.city_id, 'Available cities:', this.availableCities.map(c => ({id: c.id, name: c.name})));
                return false;
              }
            };
            
            findAndSelectCity();
          }
        } else {
          // If area not found, still try to set governorate_id
          this.customer.governorate_id = String(customerItem.area_id);
        }
      }
    },

    async updateCitiesAsync(areaId) {
      // Reset city selection first
      this.customer.city = '';
      this.customer.city_id = '';
      this.availableCities = [];
      
      // Wait for DOM update
      await this.$nextTick();
      
      this.customer.governorate_name = this.areasMap.find(a => a.id == areaId || String(a.id) === String(areaId))?.name || '';
      this.customer.governorate_id = String(areaId || '');

      if (areaId) {
        await this.fetchCities(areaId);
      } else {
        // fallback to static map if no area id match
        this.availableCities = (this.areaCityMap[this.customer.governorate_name] || []).map(n => ({id: n, name: n, charge: 0}));
        this.updateShipping();
      }
    },
    
    addOrder() {
      const newId = this.orders.length + 1;
      this.orders.push({ 
        id: newId, 
        stock_id: null,
        abaya_code: '',
        design_name: '',
        quantity: 1, 
        price: 0, 
        length: '', 
        bust: '', 
        sleeves: '', 
        buttons: 'yes', 
        notes: '' 
      });
      this.$nextTick(() => {
        const element = document.getElementById('order-' + newId);
        if (element) {
          element.scrollIntoView({ behavior: 'smooth' });
        }
      });
    },
    
    removeOrder(index) {
      if (confirm('{{ trans('messages.confirm_delete_order', [], session('locale')) }}')) {
        this.orders.splice(index, 1);
      }
    },
    
    openPaymentModal() {
      // Validate all required fields before opening modal
      
      // Validate customer name
      if (!this.customer.name || this.customer.name.trim() === '') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.customer_name', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}'
          });
        } else {
          alert('{{ trans('messages.customer_name', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}');
        }
        return;
      }
      
      // Validate order source
      if (!this.customer.source || this.customer.source.trim() === '') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.order_source', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}'
          });
        } else {
          alert('{{ trans('messages.order_source', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}');
        }
        return;
      }
      
      // Validate phone number
      if (!this.customer.phone || this.customer.phone.trim() === '') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.phone_number', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}'
          });
        } else {
          alert('{{ trans('messages.phone_number', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}');
        }
        return;
      }
      
      // Validate governorate
      if (!this.customer.governorate_id || this.customer.governorate_id === '') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.governorate', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}'
          });
        } else {
          alert('{{ trans('messages.governorate', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}');
        }
        return;
      }
      
      // Validate city/state area
      if (!this.customer.city_id || this.customer.city_id === '') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.state_area', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}'
          });
        } else {
          alert('{{ trans('messages.state_area', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}');
        }
        return;
      }
      
      // Validate address
      if (!this.customer.address || this.customer.address.trim() === '') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.address', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}'
          });
        } else {
          alert('{{ trans('messages.address', [], session('locale')) }} {{ trans('messages.is_required', [], session('locale')) ?: 'is required' }}');
        }
        return;
      }
      
      // Validate orders
      if (this.orders.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.add_new_abaya', [], session('locale')) }}'
          });
        } else {
        alert('{{ trans('messages.add_new_abaya', [], session('locale')) }}');
        }
        return;
      }
      
      // Validate each order has price > 0
      const invalidOrders = this.orders.filter(o => !o.price || parseFloat(o.price) <= 0);
      if (invalidOrders.length > 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.price_is_required_for_all_items', [], session('locale')) ?: 'Price is required for all items' }}'
          });
        } else {
          alert('{{ trans('messages.price_is_required_for_all_items', [], session('locale')) ?: 'Price is required for all items' }}');
        }
        return;
      }
      
      this.showModal = true;
    },
    
    async submitOrders() {
      if (this.loading || this.orderSubmitted) return;
      
      // Disable button immediately to prevent multiple clicks
      this.loading = true;
      this.orderSubmitted = true;
      
      try {
        const formData = {
          customer: {
            name: this.customer.name,
            phone: this.customer.phone,
            source: this.customer.source,
            area_id: this.customer.governorate_id, // Governorate ID
            city_id: this.customer.city_id, // State/Area ID
            address: this.customer.address,
            is_gift: this.customer.is_gift,
            gift_message: this.customer.gift_message
          },
          orders: this.orders.map(order => ({
            stock_id: order.stock_id,
            abaya_code: order.abaya_code,
            design_name: order.design_name,
            quantity: parseInt(order.quantity) || 1,
            price: parseFloat(order.price) || 0,
            length: order.length || null,
            bust: order.bust || null,
            sleeves: order.sleeves || null,
            buttons: order.buttons || 'yes',
            notes: order.notes || null
          })),
          shipping_fee: this.shipping_fee,
          notes: ''
        };

        console.log('Submitting order:', formData);

        const response = await fetch('{{ url('add_spcialorder') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify(formData)
        });

        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
          // Keep loading true until form is reset (prevents multiple submissions)
          this.showModal = false;
          this.loading = false;
          
          // Store order ID for payment
          if (data.special_order_id) {
            this.savedOrderId = data.special_order_id;
            // Set payment amount to total (remaining amount for new order)
            this.paymentAmount = this.calculateTotal().toFixed(3);
            this.selectedAccountId = '';
            this.paymentError = '';
            // Show payment modal
            this.showPaymentModal = true;
          }
        } else {
          // Re-enable button on error
          this.loading = false;
          this.orderSubmitted = false;
          throw new Error(data.message || 'Error saving order');
        }
      } catch (error) {
        console.error('Error:', error);
        // Re-enable button on error so user can try again
        this.loading = false;
        this.orderSubmitted = false;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message || '{{ trans('messages.error_saving_order', [], session('locale')) ?: 'Error saving order' }}'
          });
        } else {
          alert('حدث خطأ أثناء حفظ الطلب: ' + error.message);
        }
      }
    },

    async confirmPayment() {
      if (!this.savedOrderId) return;

      // Clear previous error
      this.paymentError = '';

      const amount = parseFloat(this.paymentAmount);
      if (isNaN(amount) || amount <= 0) {
        this.paymentError = '{{ trans('messages.please_enter_valid_amount', [], session('locale')) }}';
        return;
      }

      // Validate account selection
      if (!this.selectedAccountId) {
        this.paymentError = '{{ trans('messages.please_select_account', [], session('locale')) ?: 'Please select an account' }}';
        return;
      }

      // Validate amount doesn't exceed total
      const total = this.calculateTotal();
      if (amount > total + 0.001) {
        this.paymentError = '{{ trans('messages.amount_exceeds_remaining', [], session('locale')) ?: 'Payment amount exceeds remaining amount' }}';
        return;
      }

      this.paymentProcessing = true;

      try {
        const response = await fetch('{{ url('record_payment') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_id: this.savedOrderId,
            amount: amount,
            account_id: this.selectedAccountId
          })
        });

        const data = await response.json();

        if (data.success) {
          this.showPaymentModal = false;
          this.paymentError = '';
          
          // Open bill in new window
          const billUrl = '{{ url("special-order-bill") }}/' + this.savedOrderId;
          window.open(billUrl, '_blank');
          
          // Show success message
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.order_saved_successfully', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.confirm_payment', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              this.resetForm();
            });
          } else {
            alert('{{ trans('messages.order_saved_successfully', [], session('locale')) }}');
            this.resetForm();
          }
        } else {
          // Show error in modal, don't close it
          this.paymentError = data.message || '{{ trans('messages.error_recording_payment', [], session('locale')) ?: 'Error recording payment' }}';
        }
      } catch (error) {
        console.error('Error:', error);
        // Show error in modal, don't close it
        this.paymentError = error.message || '{{ trans('messages.error_recording_payment', [], session('locale')) ?: 'Error recording payment' }}';
      } finally {
        this.paymentProcessing = false;
      }
    },

    skipPayment() {
      this.showPaymentModal = false;
      this.paymentError = '';
      
      // Open bill in new window
      if (this.savedOrderId) {
        const billUrl = '{{ url("special-order-bill") }}/' + this.savedOrderId;
        window.open(billUrl, '_blank');
      }
      
      // Reset form and redirect to view_special_order
      this.resetForm();
      window.location.href = '{{ route('view_special_order') }}';
    },

    resetForm() {
      this.loading = false;
      this.orderSubmitted = false;
      this.savedOrderId = null;
      this.paymentAmount = '';
      this.selectedAccountId = '';
      this.paymentError = '';
              this.customer = { 
                source: '', 
                name: '', 
                phone: '', 
              governorate_id: '',
              city_id: '',
        address: '',
                is_gift: 'no', 
                gift_message: '' 
              };
              this.orders = [{ 
                id: 1, 
                stock_id: null,
                abaya_code: '',
                design_name: '',
                quantity: 1, 
                price: 0, 
                length: '', 
                bust: '', 
                sleeves: '', 
                buttons: 'yes', 
                notes: '' 
              }];
              this.shipping_fee = 0;
            this.availableCities = [];
    }
  }));

  Alpine.data('abayaSelector', (order) => ({
    search: '', 
    selectedAbaya: null,
    abayas: [],
    loading: false,
    
    async searchAbayas() {
      if (this.search.length < 2) {
        this.abayas = [];
        return;
      }
      
      this.loading = true;
      
      try {
        const response = await fetch(`{{ url('search_abayas') }}?search=${encodeURIComponent(this.search)}`);
        const data = await response.json();
        this.abayas = data || [];
      } catch (error) {
        console.error('Error searching abayas:', error);
        this.abayas = [];
      } finally {
        this.loading = false;
      }
    },
    
    selectAbaya(item) {
      this.selectedAbaya = item;
      this.search = item.name || item.code;
      order.stock_id = item.id;
      order.abaya_code = item.code;
      order.design_name = item.name;
      order.price = parseFloat(item.price) || 0;
      this.abayas = []; // Clear results after selection
    },
  }));
});
</script>