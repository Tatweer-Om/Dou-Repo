<script>
function boutiqueProfile() {
  return {
    tab: 'overview',
    boutiqueId: {{ $boutique->id }},
    
    // Delete boutique function
    deleteBoutique() {
      Swal.fire({
        title: '{{ trans("messages.confirm_delete_title", [], session("locale")) }}',
        text: '{{ trans("messages.confirm_delete_text", [], session("locale")) }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '{{ trans("messages.yes_delete", [], session("locale")) }}',
        cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: '/boutique/' + this.boutiqueId,
            method: 'DELETE',
            data: {
              _token: '{{ csrf_token() }}'
            },
            success: (data) => {
              Swal.fire(
                '{{ trans("messages.deleted_success", [], session("locale")) }}',
                '{{ trans("messages.deleted_success_text", [], session("locale")) }}',
                'success'
              ).then(() => {
                window.location.href = '/boutique_list';
              });
            },
            error: () => {
              Swal.fire(
                '{{ trans("messages.delete_error", [], session("locale")) }}',
                '{{ trans("messages.delete_error_text", [], session("locale")) }}',
                'error'
              );
            }
          });
        }
      });
    },

    // SALES
    salesSearch: '',
    dateFromSales: '',
    dateToSales: '',
    sales: @json($salesByTransfer ?? []),
    filteredSales: [],
    currentSale: {transfer_code:'', date:'', items:[]},
    showSaleModal: false,
    detailSearch: '',
    detailDateFrom: '',
    detailDateTo: '',
    get filteredDetails() {
      if (!this.currentSale.items) return [];
      const q = this.detailSearch.toLowerCase();
      return this.currentSale.items.filter(item => {
        const matchQ = !q || 
          (item.code && item.code.toLowerCase().includes(q)) ||
          (item.color && item.color.toLowerCase().includes(q)) ||
          (item.size && item.size.toLowerCase().includes(q));
        return matchQ;
      });
    },
    formatCurrency(n) {
      const v = Number(n || 0);
      return v.toLocaleString('ar-EG', {minimumFractionDigits: 3, maximumFractionDigits: 3}) + ' ر.ع';
    },
    getStatusText(status) {
      if (status === 'fully_paid') return '{{ trans('messages.fully_paid', [], session('locale')) }}';
      if (status === 'partially_paid') return '{{ trans('messages.partially_paid', [], session('locale')) }}';
      return '{{ trans('messages.not_paid', [], session('locale')) }}';
    },
    filterSales() {
      const q = this.salesSearch.toLowerCase();
      const from = this.dateFromSales ? new Date(this.dateFromSales) : null;
      const to   = this.dateToSales ? new Date(this.dateToSales) : null;
      this.filteredSales = this.sales.filter(r => {
        const d = new Date(r.date);
        const matchQ = !q || (r.transfer_code && r.transfer_code.toLowerCase().includes(q));
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },
    openSaleDetails(row) {
      this.currentSale = {
        transfer_code: row.transfer_code,
        date: row.date,
        items: row.items || []
      };
      this.detailSearch = '';
      this.detailDateFrom = '';
      this.detailDateTo = '';
      this.showSaleModal = true;
    },

    // SHIPMENTS
    shipmentSearch: '',
    dateFromSh: '',
    dateToSh: '',
    shipments: @json($shipmentsData ?? []),
    filteredShipments: [],
    currentShipment: {transfer_code:'', date:'', items:[]},
    showShipModal: false,
    filterShipments() {
      const q = this.shipmentSearch.toLowerCase();
      const from = this.dateFromSh ? new Date(this.dateFromSh) : null;
      const to   = this.dateToSh ? new Date(this.dateToSh) : null;
      this.filteredShipments = this.shipments.filter(r => {
        const d = new Date(r.date);
        const matchQ = !q || (r.transfer_code && r.transfer_code.toLowerCase().includes(q));
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },
    openShipmentDetails(row) {
      this.currentShipment = {
        transfer_code: row.transfer_code,
        date: row.date,
        items: row.items || []
      };
      this.showShipModal = true;
    },

    // INVOICES
    showPaymentModal: false,
    currentPaymentInvoice: {id: null, month: '', amount: 0},
    paymentAmount: '',
    paymentDate: '',
    openPaymentModal(invoiceId, month, amount) {
      this.currentPaymentInvoice = {
        id: invoiceId,
        month: month,
        amount: amount
      };
      this.paymentAmount = amount;
      this.paymentDate = new Date().toISOString().split('T')[0]; // Today's date
      this.showPaymentModal = true;
    },
    async savePayment() {
      if (!this.paymentAmount || !this.paymentDate) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.warning', [], session('locale')) }}',
            text: '{{ trans('messages.please_enter_amount_and_date', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.please_enter_amount_and_date', [], session('locale')) }}');
        }
        return;
      }
      
      try {
        const response = await fetch('{{ route('update_invoice_payment') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            invoices: [{
              id: this.currentPaymentInvoice.id,
              total_amount: this.paymentAmount,
              payment_date: this.paymentDate
            }]
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          // Close modal first
          this.showPaymentModal = false;
          
          // Show success message and reload after it closes
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.success', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.payment_updated_successfully', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false,
              allowOutsideClick: false,
              allowEscapeKey: false
            }).then(() => {
              location.reload(); // Reload to show updated invoice status
            });
          } else {
            alert(data.message || '{{ trans('messages.payment_updated_successfully', [], session('locale')) }}');
            setTimeout(() => {
              location.reload();
            }, 100);
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.error_updating_payments', [], session('locale')) }}'
            });
          } else {
            alert(data.message || '{{ trans('messages.error_updating_payments', [], session('locale')) }}');
          }
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.error_updating_payments', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.error_updating_payments', [], session('locale')) }}');
        }
      }
    },

    // CHART
    initCharts() {
      // default filtered
      this.filterSales();
      this.filterShipments();

      const ctx = document.getElementById('salesChart');
      if (!ctx) return;
      
      // Get sales data from PHP
      const salesData = @json($salesData);
      const monthTranslationKeys = @json($monthNames);
      
      // Translate month names
      const monthLabels = monthTranslationKeys.map(key => {
        const translations = {
          'january': '{{ trans("messages.january", [], session("locale")) }}',
          'february': '{{ trans("messages.february", [], session("locale")) }}',
          'march': '{{ trans("messages.march", [], session("locale")) }}',
          'april': '{{ trans("messages.april", [], session("locale")) }}',
          'may': '{{ trans("messages.may", [], session("locale")) }}',
          'june': '{{ trans("messages.june", [], session("locale")) }}',
          'july': '{{ trans("messages.july", [], session("locale")) }}',
          'august': '{{ trans("messages.august", [], session("locale")) }}',
          'september': '{{ trans("messages.september", [], session("locale")) }}',
          'october': '{{ trans("messages.october", [], session("locale")) }}',
          'november': '{{ trans("messages.november", [], session("locale")) }}',
          'december': '{{ trans("messages.december", [], session("locale")) }}'
        };
        return translations[key] || key;
      });
      
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: monthLabels,
          datasets: [{
            label: '{{ trans('messages.total_sales_currency', [], session('locale')) }}',
            data: salesData,
            borderColor: '#d63384',
            backgroundColor: 'rgba(214, 51, 132, 0.15)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          plugins:{legend:{display:false}},
          scales:{y:{beginAtZero:true,ticks:{color:'#666'}},x:{ticks:{color:'#666'}}}
        }
      });
    }
  }
}
</script>