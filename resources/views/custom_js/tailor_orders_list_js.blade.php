<script>
    $(document).ready(function() {
        let selectedTailorId = null;
        let currentPage = 1;

        // Handle tailor selection
        $('#tailorSelect').on('change', function() {
            selectedTailorId = $(this).val();
            currentPage = 1;
            
            if (!selectedTailorId) {
                $('#ordersTableBody').html(`
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-500">
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

        // Load orders for selected tailor
        function loadOrders(tailorId, page = 1) {
            currentPage = page;
            $.ajax({
                url: "{{ route('tailor_orders_list.data') }}",
                type: "GET",
                data: { 
                    tailor_id: tailorId,
                    page: page
                },
                success: function(res) {
                    if (res.success && res.orders && res.orders.length > 0) {
                        let rows = '';
                        res.orders.forEach(function(order) {
                            rows += `
                                <tr class="hover:bg-pink-50/50 transition-colors border-b">
                                    <td class="px-4 py-4 font-semibold text-[var(--text-primary)] whitespace-nowrap">${order.order_no}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.dress_name}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.dress_code}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.size}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${order.quantity}</td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        ${order.buttons ? '<span class="material-symbols-outlined text-green-600">check_circle</span>' : '-'}
                                    </td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.gift}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap min-w-[200px]" title="${order.notes || ''}">${order.notes || '-'}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.customer_name}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.customer_phone}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap min-w-[200px]">${order.customer_address}</td>
                                    <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${order.customer_country}</td>
                                </tr>
                            `;
                        });
                        $('#ordersTableBody').html(rows);
                        
                        // Render pagination
                        if (res.last_page && res.last_page > 1) {
                            renderPagination(res.current_page || 1, res.last_page);
                        } else {
                            $('#pagination').html('');
                        }
                    } else {
                        $('#ordersTableBody').html(`
                            <tr>
                                <td colspan="12" class="px-4 py-8 text-center text-gray-500">
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
                            <td colspan="12" class="px-4 py-8 text-center text-red-500">
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

            let pagination = '';
            
            // Previous button
            pagination += `
                <li class="px-3 py-1 rounded-full ${currentPage == 1 ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300 cursor-pointer'}">
                    <a href="#" data-page="${currentPage - 1}" class="block">&laquo;</a>
                </li>
            `;

            // Page numbers
            for (let i = 1; i <= lastPage; i++) {
                if (i == 1 || i == lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    pagination += `
                        <li class="px-3 py-1 rounded-full ${currentPage == i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300 cursor-pointer'}">
                            <a href="#" data-page="${i}" class="block">${i}</a>
                        </li>
                    `;
                } else if (i == currentPage - 3 || i == currentPage + 3) {
                    pagination += `
                        <li class="px-3 py-1 rounded-full bg-gray-200 opacity-50 pointer-events-none">
                            <span class="block">...</span>
                        </li>
                    `;
                }
            }

            // Next button
            pagination += `
                <li class="px-3 py-1 rounded-full ${currentPage == lastPage ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300 cursor-pointer'}">
                    <a href="#" data-page="${currentPage + 1}" class="block">&raquo;</a>
                </li>
            `;

            $('#pagination').html(pagination);
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && selectedTailorId) {
                loadOrders(selectedTailorId, page);
            }
        });

        // Export PDF
        $('#exportPdfBtn').on('click', function() {
            if (!selectedTailorId) return;
            window.open("{{ route('tailor_orders_list.export_pdf') }}?tailor_id=" + selectedTailorId, '_blank');
        });

        // Export Excel
        $('#exportExcelBtn').on('click', function() {
            if (!selectedTailorId) return;
            window.location.href = "{{ route('tailor_orders_list.export_excel') }}?tailor_id=" + selectedTailorId;
        });
    });
</script>

