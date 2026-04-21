<script>
    // Global variables to store state
    let selectedTailorId = null;
    let currentPage = 1;

    // Load orders for selected tailor
    function loadOrders(tailorId, page = 1) {
        currentPage = page;
        selectedTailorId = tailorId; // Store globally
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var listNumber = $('#listNumberFilter').val();
        
        $.ajax({
            url: "{{ route('tailor_orders_list.data') }}",
            type: "GET",
            data: { 
                tailor_id: tailorId,
                start_date: startDate,
                end_date: endDate,
                list_number: listNumber,
                page: page
            },
            success: function(res) {
                if (res.success && res.orders && res.orders.length > 0) {
                    let rows = '';
                    res.orders.forEach(function(order) {
                        rows += `
                            <tr class="hover:bg-pink-50/50 transition-colors border-b">
                                <td class="px-4 py-4 font-semibold text-[var(--text-primary)] whitespace-nowrap">${order.special_order_no || '-'}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.list_number || '-'}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.sending_summary_number || '-'}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.tailor_name || '-'}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.quantity}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.abaya_code}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.design_name}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.abaya}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.bust}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.sleeves}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.customer_name}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.customer_phone}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap min-w-[200px]">${order.customer_address}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.customer_country}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.status || '-'}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.sent_at || '-'}</td>
                            </tr>
                        `;
                    });
                    $('#ordersTableBody').html(rows);

                    // Populate list number filter options (professional select)
                    if (Array.isArray(res.list_numbers)) {
                        const current = $('#listNumberFilter').val() || '';
                        let options = '<option value=\"\">{{ trans('messages.all', [], session('locale')) ?: 'All' }}</option>';
                        res.list_numbers.forEach(function(ln) {
                            const selected = (ln === current) ? 'selected' : '';
                            options += `<option value="${ln}" ${selected}>${ln}</option>`;
                        });
                        $('#listNumberFilter').html(options);
                    }
                    
                    // Render pagination
                    if (res.last_page && res.last_page > 1) {
                        renderPagination(res.current_page || 1, res.last_page);
                    } else {
                        $('#pagination').html('');
                    }
                } else {
                    $('#ordersTableBody').html(`
                        <tr>
                            <td colspan="15" class="px-4 py-8 text-center text-gray-500">
                                {{ trans('messages.no_orders_found', [], session('locale')) }}
                            </td>
                        </tr>
                    `);
                    $('#pagination').html('');
                }
            },
            error: function() {
                $('#ordersTableBody').html(`
                    <tr>
                        <td colspan="15" class="px-4 py-8 text-center text-red-500">
                            {{ trans('messages.error_loading_orders', [], session('locale')) }}
                        </td>
                    </tr>
                `);
                $('#pagination').html('');
            }
        });
    }

    // Render pagination
    function renderPagination(currentPage, lastPage) {
        if (!lastPage || lastPage <= 1) {
            $('#pagination').html('');
            return;
        }

        currentPage = parseInt(currentPage) || 1;
        lastPage = parseInt(lastPage) || 1;

        let pagination = '';
        
        // Previous button
        pagination += `
            <li class="flex">
                <a href="#" data-page="${currentPage - 1}"
                   class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                          ${currentPage == 1 
                            ? 'opacity-40 cursor-not-allowed bg-gray-200 pointer-events-none' 
                            : 'bg-white hover:bg-gray-100 cursor-pointer'}"
                   ${currentPage == 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>
                    &laquo;
                </a>
            </li>
        `;

        // Page numbers - show all pages if less than 10, otherwise show smart pagination
        if (lastPage <= 10) {
            // Show all pages if 10 or fewer
            for (let i = 1; i <= lastPage; i++) {
                pagination += `
                    <li>
                        <a href="#" data-page="${i}"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == i 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            ${i}
                        </a>
                    </li>
                `;
            }
        } else {
            // Smart pagination for many pages
            // Calculate range of pages to show around current page
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(lastPage, currentPage + 2);
            
            // Adjust range to always show some pages near boundaries
            if (startPage <= 3) {
                startPage = 1;
                endPage = Math.min(5, lastPage);
            } else if (endPage >= lastPage - 2) {
                endPage = lastPage;
                startPage = Math.max(1, lastPage - 4);
            }

            // Always show first page if not in range
            if (startPage > 1) {
                pagination += `
                    <li>
                        <a href="#" data-page="1"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == 1 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            1
                        </a>
                    </li>
                `;
                
                if (startPage > 2) {
                    pagination += `
                        <li class="px-4 py-1 mx-1 text-sm text-gray-500 pointer-events-none">
                            ...
                        </li>
                    `;
                }
            }

            // Show pages in range
            for (let i = startPage; i <= endPage; i++) {
                pagination += `
                    <li>
                        <a href="#" data-page="${i}"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == i 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            ${i}
                        </a>
                    </li>
                `;
            }

            // Show last page if not in range
            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    pagination += `
                        <li class="px-4 py-1 mx-1 text-sm text-gray-500 pointer-events-none">
                            ...
                        </li>
                    `;
                }
                
                pagination += `
                    <li>
                        <a href="#" data-page="${lastPage}"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == lastPage 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            ${lastPage}
                        </a>
                    </li>
                `;
            }
        }

        // Next button
        pagination += `
            <li class="flex">
                <a href="#" data-page="${currentPage + 1}"
                   class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                          ${currentPage == lastPage 
                            ? 'opacity-40 cursor-not-allowed bg-gray-200 pointer-events-none' 
                            : 'bg-white hover:bg-gray-100 cursor-pointer'}"
                   ${currentPage == lastPage ? 'tabindex="-1" aria-disabled="true"' : ''}>
                    &raquo;
                </a>
            </li>
        `;

        $('#pagination').html(pagination);
    }

    $(document).ready(function() {
        // Handle tailor selection
        $('#tailorSelect').on('change', function() {
            selectedTailorId = $(this).val();
            currentPage = 1;
            
            if (!selectedTailorId) {
                $('#ordersTableBody').html(`
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            {{ trans('messages.select_tailor_to_view_orders', [], session('locale')) }}
                        </td>
                    </tr>
                `);
                $('#exportPdfBtn, #exportExcelBtn').prop('disabled', true);
                $('#pagination').html('');
                return;
            }

            loadOrders(selectedTailorId, currentPage);
            $('#exportPdfBtn, #exportExcelBtn').prop('disabled', false);
        });

        // Handle date changes - reload orders if tailor is selected
                $('#startDate, #endDate').on('change', function() {
            if (selectedTailorId) {
                currentPage = 1;
                loadOrders(selectedTailorId, currentPage);
            }
        });

        // Handle list number filter change
        $('#listNumberFilter').on('change', function() {
            if (selectedTailorId) {
                currentPage = 1;
                loadOrders(selectedTailorId, currentPage);
            }
        });

        // Handle pagination click
        $(document).on('click', '#pagination a[data-page]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            let page = parseInt($(this).data('page'));
            let clickedElement = $(this);
            
            // Check if link is disabled
            if (clickedElement.hasClass('pointer-events-none') || clickedElement.hasClass('opacity-40') || clickedElement.hasClass('cursor-not-allowed')) {
                return false;
            }
            
            // Get selected tailor ID from the select element or use stored value
            let tailorId = selectedTailorId || $('#tailorSelect').val();
            
            if (page && page > 0 && tailorId) {
                loadOrders(tailorId, page);
            }
            
            return false;
        });

        // Export PDF
        $('#exportPdfBtn').on('click', function() {
            if (!selectedTailorId) return;
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var listNumber = $('#listNumberFilter').val();
            var params = 'tailor_id=' + selectedTailorId;
            if (startDate) params += '&start_date=' + startDate;
            if (endDate) params += '&end_date=' + endDate;
            if (listNumber) params += '&list_number=' + encodeURIComponent(listNumber);
            window.open("{{ route('tailor_orders_list.export_pdf') }}?" + params, '_blank');
        });

        // Export Excel
        $('#exportExcelBtn').on('click', function() {
            if (!selectedTailorId) return;
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var listNumber = $('#listNumberFilter').val();
            var params = 'tailor_id=' + selectedTailorId;
            if (startDate) params += '&start_date=' + startDate;
            if (endDate) params += '&end_date=' + endDate;
            if (listNumber) params += '&list_number=' + encodeURIComponent(listNumber);
            window.location.href = "{{ route('tailor_orders_list.export_excel') }}?" + params;
        });
    });
</script>
