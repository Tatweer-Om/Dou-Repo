<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('assignTailorPage', () => ({

    /* Modes */
    mode: 'view',

    /* MODALS */
    showConfirmModal: false,
    showDetailsModal: false,
    showPrintModal: false,

    /* FILTERS */
    search: '',
    searchView: '',
    tailorViewFilter: '',

    filter: {
      from: '',
      to: ''
    },

    statusFilter: '',

    /* Lists */
    selectedItems: [],
    receivedList: [],
    loading: false,

    selectedItem: {},

    /* Tailors list */
    tailors: [],

    /* Data from backend */
    newItems: [],
    processingItems: [],

    /* ======================================================================= */
    /* INITIALIZE - LOAD DATA FROM BACKEND */
    /* ======================================================================= */
    async init() {
      await this.loadData();
    },

    async loadData() {
      this.loading = true;
      try {
        const response = await fetch('{{ route('send_request.data') }}');
        const data = await response.json();
        
        if (data.success) {
          this.tailors = data.tailors || [];
          this.newItems = data.new || [];
          this.processingItems = data.processing || [];
          this.selectedItems = [];
          this.receivedList = [];
        } else {
          throw new Error(data.message || 'Failed to load data');
        }
      } catch (error) {
        console.error('Error loading data:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        } else {
          alert('Error: ' + error.message);
        }
      } finally {
        this.loading = false;
      }
    },

    tailorNameById(id) {
      if (!id) return '';
      const tailor = this.tailors.find(t => String(t.id) === String(id));
      return tailor ? tailor.name : '';
    },

    updateTailorSelection(item) {
      item.tailor_name = this.tailorNameById(item.tailor_id);
    },


    /* ======================================================================= */
    /* OPEN DETAILS */
    /* ======================================================================= */
    openDetails(item) {
      this.selectedItem = item;
      this.showDetailsModal = true;
    },


    /* ======================================================================= */
    /* PRINT SINGLE ITEM */
    /* ======================================================================= */
    printSingle(item) {
      let w = window.open('', '_blank');
      w.document.write(`
        <html>
        <head>
          <title>ورقة الخياط</title>
          <style>
            body { font-family: sans-serif; direction: rtl; padding: 20px; }
            h1 { margin-bottom: 15px; }
            .box { border: 1px solid #ccc; padding: 15px; border-radius: 12px; margin-bottom:20px; }
            img { width: 180px; border-radius: 10px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ccc; padding: 8px; font-size: 14px; }
          </style>
        </head>
        <body>

          <h1>{{ trans('messages.abaya_details', [], session('locale')) }}</h1>

          <img src="${item.image}">

          <div class="box">
            <p><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> ${item.orderId}</p>
            <p><strong>{{ trans('messages.customer', [], session('locale')) }}:</strong> ${item.customer}</p>
            <p><strong>{{ trans('messages.tailor', [], session('locale')) }}:</strong> ${item.tailor_name || item.tailor || ''}</p>
            <p><strong>{{ trans('messages.order_source', [], session('locale')) }}:</strong> ${item.source}</p>
          </div>

          <table>
            <tr><th>{{ trans('messages.abaya_length', [], session('locale')) }}</th><td>${item.length}</td></tr>
            <tr><th>{{ trans('messages.bust_one_side', [], session('locale')) }}</th><td>${item.bust}</td></tr>
            <tr><th>{{ trans('messages.sleeves_length', [], session('locale')) }}</th><td>${item.sleeves}</td></tr>
            <tr><th>{{ trans('messages.buttons', [], session('locale')) }}</th><td>${item.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'}</td></tr>
          </table>

          <h3>{{ trans('messages.notes', [], session('locale')) }}</h3>
          <p>${item.notes}</p>

        </body>
        </html>
      `);

      w.document.close();
      w.print();
    },


    /* ======================================================================= */
    /* PRINT MULTIPLE ITEMS */
    /* ======================================================================= */
    doPrintList() {
      if (this.selectedItems.length === 0) {
        alert('{{ trans('messages.no_items_selected', [], session('locale')) }}');
        return;
      }

      let w = window.open('', '_blank');

      // Create a single table with all items, including tailor name in each row
      let rows = this.selectedItems.map((i, idx) => {
        const tailorName = i.tailor_name || this.tailorNameById(i.tailor_id) || '{{ trans('messages.not_assigned', [], session('locale')) }}';
        return `
          <tr>
            <td class="text-center">${idx + 1}</td>
            <td>${i.order_no || ('#' + i.orderId)}</td>
            <td>${i.customer || '—'}</td>
            <td><strong>${tailorName}</strong></td>
            <td><strong>${i.abayaName || i.code || '—'}</strong><br><small style="color: #666;">{{ trans('messages.code', [], session('locale')) }}: ${i.code || '—'}</small></td>
            <td class="text-center">${i.quantity || 1}</td>
            <td>
              <strong>{{ trans('messages.abaya_length', [], session('locale')) }}:</strong> ${i.length || '—'}<br>
              <strong>{{ trans('messages.bust_one_side', [], session('locale')) }}:</strong> ${i.bust || '—'}<br>
              <strong>{{ trans('messages.sleeves_length', [], session('locale')) }}:</strong> ${i.sleeves || '—'}<br>
              <strong>{{ trans('messages.buttons', [], session('locale')) }}:</strong> ${i.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'}
            </td>
            <td>${i.notes || '—'}</td>
          </tr>
        `;
      }).join('');

      let content = `
        <table>
          <thead>
            <tr>
              <th style="width: 40px;">#</th>
              <th>{{ trans('messages.order_number', [], session('locale')) }}</th>
              <th>{{ trans('messages.customer', [], session('locale')) }}</th>
              <th>{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th>{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th style="width: 60px;">{{ trans('messages.quantity', [], session('locale')) }}</th>
              <th>{{ trans('messages.sizes', [], session('locale')) }}</th>
              <th>{{ trans('messages.notes', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      `;

      w.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>{{ trans('messages.tailor_sheet_orders', [], session('locale')) }}</title>
          <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
              padding: 20px; 
              direction: rtl; 
              background: #fff;
              color: #333;
            }
            .header {
              text-align: center;
              margin-bottom: 30px;
              padding-bottom: 20px;
              border-bottom: 3px solid #4f46e5;
            }
            .header h1 {
              color: #4f46e5;
              font-size: 24px;
              margin-bottom: 10px;
            }
            .header .info {
              color: #666;
              font-size: 14px;
            }
            table { 
              width: 100%; 
              border-collapse: collapse; 
              font-size: 13px;
              margin-bottom: 20px;
              box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            th { 
              background: linear-gradient(to bottom, #f3f4f6, #e5e7eb);
              color: #374151;
              font-weight: 600;
              padding: 12px 8px;
              border: 1px solid #d1d5db;
              text-align: right;
            }
            td { 
              border: 1px solid #e5e7eb;
              padding: 10px 8px;
              text-align: right;
            }
            tr:nth-child(even) {
              background-color: #f9fafb;
            }
            tr:hover {
              background-color: #f3f4f6;
            }
            .text-center {
              text-align: center;
            }
            @media print {
              body { padding: 10px; }
              table { page-break-inside: auto; }
              tr { page-break-inside: avoid; page-break-after: auto; }
              thead { display: table-header-group; }
              tfoot { display: table-footer-group; }
              @page { margin: 1cm; }
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>{{ trans('messages.tailor_sheet', [], session('locale')) }}</h1>
            <div class="info">
              {{ trans('messages.total_items', [], session('locale')) }}: ${this.selectedItems.length} | 
              {{ trans('messages.printed_on', [], session('locale')) }}: ${new Date().toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
            </div>
          </div>
          ${content}
        </body>
        </html>
      `);

      w.document.close();
      setTimeout(() => {
        w.print();
      }, 250);
      this.showPrintModal = false;
    },


    /* ======================================================================= */
    /* SELECT ITEMS */
    /* ======================================================================= */
    toggleSelection(item) {
      let exists = this.selectedItems.find(i => i.rowId === item.rowId);
      if (exists) {
        this.selectedItems = this.selectedItems.filter(i => i.rowId !== item.rowId);
      } else {
        this.selectedItems.push(item);
      }
    },

    toggleReceive(item) {
      if (this.receivedList.includes(item.rowId)) {
        this.receivedList = this.receivedList.filter(id => id !== item.rowId);
      } else {
        this.receivedList.push(item.rowId);
      }
    },


    /* ======================================================================= */
    /* CONFIRM RECEIVE */
    /* ======================================================================= */
    async confirmReceive() {
      if (this.receivedList.length === 0) return;

      try {
        const response = await fetch('{{ route('send_request.receive') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ item_ids: this.receivedList })
        });

        const data = await response.json();
        
        if (data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: data.message || '{{ trans('messages.abayas_received', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert("✔ " + (data.message || '{{ trans('messages.abayas_received', [], session('locale')) }}'));
          }
          
          await this.loadData();
          this.showConfirmModal = false;
        } else {
          throw new Error(data.message || 'Failed to mark items as received');
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        } else {
          alert('Error: ' + error.message);
        }
      }
    },


    /* ======================================================================= */
    /* FILTERING NEW ABAYAS (ASSIGN MODE) */
    /* ======================================================================= */
    filteredAbayas() {
      return this.newItems
        .filter(i => {
          const term = this.search.trim().toLowerCase();
          if (!term) return true;
          return (
            (i.customer || '').toLowerCase().includes(term) ||
            String(i.orderId || '').includes(term) ||
            (i.code || '').toLowerCase().includes(term)
          );
        })
        .filter(i => {
          if (!this.filter.from && !this.filter.to) return true;
          if (!i.date) return true;

          let d = new Date(i.date);
          let from = this.filter.from ? new Date(this.filter.from) : null;
          let to = this.filter.to ? new Date(this.filter.to) : null;

          if (from && d < from) return false;
          if (to && d > to) return false;

          return true;
        });
    },


    /* ======================================================================= */
    /* SORT PROCESSING (VIEW MODE) */
    /* ======================================================================= */
    sortedProcessing() {
      return this.processingItems
        .filter(i => {
          const term = this.searchView.trim().toLowerCase();
          if (!term) return true;
          return (
            (i.customer || '').toLowerCase().includes(term) ||
            String(i.orderId || '').includes(term) ||
            (i.tailor_name || i.tailor || '').toLowerCase().includes(term) ||
            (i.code || '').toLowerCase().includes(term)
          );
        })
        .filter(i => {
          if (!this.tailorViewFilter) return true;
          return String(i.tailor_id) === String(this.tailorViewFilter);
        })
        .sort((a, b) => {
          const lateA = this.isLate(a.date) ? 1 : 0;
          const lateB = this.isLate(b.date) ? 1 : 0;
          return lateB - lateA;
        });
    },


    /* ======================================================================= */
    /* DATE HELPERS */
    /* ======================================================================= */
    isLate(date) {
      return ((new Date() - new Date(date)) / 86400000) >= 12;
    },

    daysAgo(date) {
      return "{{ trans('messages.ago', [], session('locale')) }} " + Math.floor((new Date() - new Date(date)) / 86400000) + " {{ trans('messages.days', [], session('locale')) }}";
    },


    /* ======================================================================= */
    /* GROUP BY TAILOR */
    /* ======================================================================= */
    groupByTailor() {
      let result = {};
      this.selectedItems.forEach(i => {
        const tailorName = this.tailorNameById(i.tailor_id) || '{{ trans('messages.not_assigned', [], session('locale')) }}';
        const quantity = i.quantity || 1;
        result[tailorName] = (result[tailorName] || 0) + quantity;
      });
      return result;
    },


    /* ======================================================================= */
    /* SUBMIT SELECTED ITEMS TO TAILOR */
    /* ======================================================================= */
    async submitToTailor() {
      if (this.selectedItems.length === 0) return;

      const assignments = this.selectedItems.map(item => ({
        item_id: item.rowId,
        tailor_id: item.tailor_id
      }));

      // Validate all items have tailor selected
      if (assignments.some(a => !a.tailor_id)) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.select_tailor_first', [], session('locale')) }}',
            text: '{{ trans('messages.please_select_tailor', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.select_tailor_first', [], session('locale')) }}');
        }
        return;
      }

      try {
        const response = await fetch('{{ route('send_request.assign') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ assignments })
        });

        const data = await response.json();
        
        if (data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: data.message || '{{ trans('messages.abayas_sent_to_tailor', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert("✔ " + (data.message || '{{ trans('messages.abayas_sent_to_tailor', [], session('locale')) }}'));
          }
          
          await this.loadData();
          this.mode = 'view';
          this.selectedItems = [];
        } else {
          throw new Error(data.message || 'Failed to assign items to tailor');
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        } else {
          alert('Error: ' + error.message);
        }
      }
    }

  }));
});
</script>