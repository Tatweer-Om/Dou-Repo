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
      governorate: '', 
      city: '', 
      is_gift: 'no', 
      gift_message: '' 
    },
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
      
      this.loading = true;
      
      try {
        const formData = {
          customer: {
            name: this.customer.name,
            phone: this.customer.phone,
            source: this.customer.source,
            governorate: this.customer.governorate_name || '',
            area: this.customer.city, // backend currently stores area, map city into area
            city: this.customer.city,
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
          this.showModal = false;
          
          // Show success message
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.order_saved_successfully', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              // Reset form
              this.customer = { 
                source: '', 
                name: '', 
                phone: '', 
                governorate_name: '', 
              governorate_id: '',
              city: '', 
              city_id: '',
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
            // Reset form
            location.reload();
          }
        } else {
          throw new Error(data.message || 'Error saving order');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ الطلب: ' + error.message);
      } finally {
        this.loading = false;
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