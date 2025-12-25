<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('tailorApp', () => ({
    showModal: false,
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
      this.availableCities = [];
      this.customer.city = '';
      this.customer.city_id = '';
      this.customer.governorate_name = this.areasMap.find(a => a.id == areaId)?.name || '';
      this.customer.governorate_id = areaId || '';

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
      const city = this.availableCities.find(c => c.id == cityId);
      this.customer.city_id = cityId || '';
      this.customer.city = city ? city.name : '';
      this.shipping_fee = city ? city.charge : 0;
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
    
    selectCustomer(customerItem) {
      // Fill customer data from selected customer
      this.customer.name = customerItem.name || '';
      this.customer.phone = customerItem.phone || '';
      this.customer.address = customerItem.address || '';
      
      // Fill area/governorate if available (area_id in customer = governorate_id in form)
      if (customerItem.area_id) {
        // Try to find matching area in governorates list
        const matchingArea = this.areasMap.find(a => a.id == customerItem.area_id);
        if (matchingArea) {
          this.customer.governorate_id = customerItem.area_id;
          this.customer.governorate_name = matchingArea.name;
          // Update cities for this area
          this.updateCities(customerItem.area_id);
          
          // After cities are loaded, select the city if available
          if (customerItem.city_id) {
            // Use a small delay to ensure cities are loaded
            setTimeout(() => {
              const matchingCity = this.availableCities.find(c => c.id == customerItem.city_id);
              if (matchingCity) {
                this.selectCity(customerItem.city_id);
              }
            }, 300);
          }
        }
      } else if (customerItem.city_id) {
        // If no area_id but city_id exists, try to find city and set its area
        // This is a fallback - ideally area_id should be set
        setTimeout(() => {
          const matchingCity = this.availableCities.find(c => c.id == customerItem.city_id);
          if (matchingCity) {
            this.selectCity(customerItem.city_id);
          }
        }, 300);
      }
      
      // Clear suggestions
      this.customerSuggestions = [];
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
      // Validate before opening modal
      if (!this.customer.name || !this.customer.source) {
        alert('{{ trans('messages.customer_name', [], session('locale')) }} و {{ trans('messages.order_source', [], session('locale')) }} مطلوبان');
        return;
      }
      
      if (this.orders.length === 0 || !this.orders.some(o => o.price > 0)) {
        alert('{{ trans('messages.add_new_abaya', [], session('locale')) }}');
        return;
      }
      
      this.showModal = true;
    },
    
    async submitOrders() {
      if (this.loading) return;
      
      // Disable button immediately to prevent multiple clicks
      this.loading = true;
      
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
          
          // Open bill in new window
          if (data.special_order_id) {
            const billUrl = '{{ url("special-order-bill") }}/' + data.special_order_id;
            window.open(billUrl, '_blank');
          }
          
          // Show success message
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.order_saved_successfully', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              // Reset form and re-enable button
              this.loading = false;
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
            });
          } else {
            alert('{{ trans('messages.order_saved_successfully', [], session('locale')) }}');
            // Reset form and re-enable button
            this.loading = false;
            location.reload();
          }
        } else {
          // Re-enable button on error
          this.loading = false;
          throw new Error(data.message || 'Error saving order');
        }
      } catch (error) {
        console.error('Error:', error);
        // Re-enable button on error so user can try again
        this.loading = false;
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