<script>
    $(document).ready(function() {
        let currentPage = 1;

        function loadOrders(page = 1) {
            currentPage = page;
            $.get("{{ url('pos/orders/list/data') }}?page=" + page, function(res) {
                if (!res.success) {
                    $('#ordersTableBody').html('<tr><td colspan="11" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading orders</td></tr>');
                    return;
                }

                // ---- Table Rows ----
                let rows = '';
                if (res.orders && res.orders.length > 0) {
                    $.each(res.orders, function(i, order) {
                        rows += `
                        <tr class="hover:bg-pink-50/50 transition-colors" data-id="${order.id}">
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold">#${order.order_no}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${order.customer_name || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold ${order.order_type === 'Delivery' || order.order_type === 'توصيل' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}">
                                    ${order.order_type || '-'}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${order.date || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${order.time || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${order.items_count || 0}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${parseFloat(order.subtotal || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${parseFloat(order.discount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-bold">${parseFloat(order.paid_amount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${order.payment_methods || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-center">
                                <button class="view-details-btn p-2 rounded-lg text-white bg-[var(--primary-color)] hover:bg-[var(--primary-darker)] transition shadow-sm" 
                                        data-order-data='${JSON.stringify(order)}'>
                                    <span class="material-symbols-outlined text-[22px]">visibility</span>
                                </button>
                            </td>
                        </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="11" class="px-4 sm:px-6 py-8 text-center text-gray-500">{{ trans('messages.no_orders', [], session('locale')) ?: 'No orders found' }}</td></tr>';
                }
                $('#ordersTableBody').html(rows);

                // ---- Pagination ----
                let pagination = '';
                if (res.last_page > 1) {
                    // Previous
                    pagination += `
                    <li class="px-3 py-1 rounded-full ${res.current_page == 1 ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
                        <a href="#" data-page="${res.current_page - 1}">&laquo;</a>
                    </li>`;

                    // Page numbers
                    for (let i = 1; i <= res.last_page; i++) {
                        pagination += `
                        <li class="px-3 py-1 rounded-full ${res.current_page == i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                            <a href="#" data-page="${i}">${i}</a>
                        </li>
                        `;
                    }

                    // Next
                    pagination += `
                    <li class="px-3 py-1 rounded-full ${res.current_page == res.last_page ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
                        <a href="#" data-page="${res.current_page + 1}">&raquo;</a>
                    </li>`;
                }
                $('#pagination').html(pagination);
            }).fail(function() {
                $('#ordersTableBody').html('<tr><td colspan="10" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading orders</td></tr>');
            });
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page) {
                loadOrders(page);
            }
        });

        // Initial load
        loadOrders();

        // Search functionality
        $('#search_order').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });

        // View details button click
        $(document).on('click', '.view-details-btn', function() {
            let orderData = $(this).data('order-data');
            showProductDetails(orderData);
        });

        function showProductDetails(order) {
            let html = `
                <div class="space-y-6">
                    <!-- Order Information -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.order_number', [], session('locale')) }}</p>
                                <p class="font-bold text-lg">#${order.order_no}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.customer_name', [], session('locale')) }}</p>
                                <p class="font-semibold">${order.customer_name || '-'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.date', [], session('locale')) }}</p>
                                <p class="font-semibold">${order.date || '-'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.time', [], session('locale')) }}</p>
                                <p class="font-semibold">${order.time || '-'}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.subtotal', [], session('locale')) }}</p>
                                <p class="font-bold">${parseFloat(order.subtotal || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.discount', [], session('locale')) }}</p>
                                <p class="font-bold text-red-600">-${parseFloat(order.discount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.total_amount', [], session('locale')) }}</p>
                                <p class="font-bold text-primary">${parseFloat(order.total_amount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.paid_amount', [], session('locale')) }}</p>
                                <p class="font-bold text-green-600">${parseFloat(order.paid_amount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-gray-500 text-xs mb-1">{{ trans('messages.payment_method', [], session('locale')) }}</p>
                            <p class="font-semibold">${order.payment_methods || '-'}</p>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 border-b-2 border-indigo-200 pb-2 mb-4">
                            {{ trans('messages.order_items', [], session('locale')) ?: 'Order Items' }} 
                            <span class="text-indigo-600">(${order.items ? order.items.length : 0})</span>
                        </h3>
                        <div class="space-y-4">
            `;

            if (order.items && order.items.length > 0) {
                order.items.forEach(function(item) {
                    html += `
                        <div class="border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-shadow bg-white">
                            <div class="flex flex-col md:flex-row gap-4">
                                <!-- Product Image -->
                                <div class="w-full md:w-48 flex-shrink-0">
                                    <img src="${item.image || '/images/placeholder.png'}" 
                                         alt="${item.design_name || '-'}"
                                         class="w-full h-48 md:h-full object-cover rounded-xl shadow-md"
                                         onerror="this.src='/images/placeholder.png'">
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1 space-y-3">
                                    <div>
                                        <h4 class="font-bold text-lg text-gray-800">${item.design_name || '-'}</h4>
                                        <p class="text-gray-500 text-xs mt-1">
                                            {{ trans('messages.code', [], session('locale')) }}: 
                                            <span class="font-semibold">${item.abaya_code || '-'}</span>
                                        </p>
                                        ${item.barcode ? `<p class="text-gray-500 text-xs">
                                            {{ trans('messages.barcode', [], session('locale')) }}: 
                                            <span class="font-semibold">${item.barcode}</span>
                                        </p>` : ''}
                                    </div>

                                    <!-- Color and Size -->
                                    <div class="flex flex-wrap gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">{{ trans('messages.color', [], session('locale')) ?: 'Color' }}: </span>
                                            <span class="font-semibold ${item.color_name && item.color_name !== '-' ? 'text-blue-600' : 'text-gray-400'}">${item.color_name || '-'}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">{{ trans('messages.size', [], session('locale')) ?: 'Size' }}: </span>
                                            <span class="font-semibold ${item.size_name && item.size_name !== '-' ? 'text-green-600' : 'text-gray-400'}">${item.size_name || '-'}</span>
                                        </div>
                                    </div>

                                    <!-- Quantity and Price -->
                                    <div class="flex flex-wrap gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">{{ trans('messages.quantity', [], session('locale')) }}: </span>
                                            <span class="font-semibold text-indigo-600">${item.quantity || 0}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">{{ trans('messages.price', [], session('locale')) }}: </span>
                                            <span class="font-semibold">${parseFloat(item.price || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">{{ trans('messages.total', [], session('locale')) }}: </span>
                                            <span class="font-bold text-primary">${parseFloat(item.total || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<p class="text-gray-500 text-center py-8">{{ trans('messages.no_items', [], session('locale')) ?: 'No items found' }}</p>';
            }

            html += `
                        </div>
                    </div>
                </div>
            `;

            $('#productDetailsContent').html(html);
            $('#productDetailsModal').removeClass('hidden').addClass('flex');
        }

        window.closeProductModal = function() {
            $('#productDetailsModal').removeClass('flex').addClass('hidden');
        };

        // Close modal on backdrop click
        $('#productDetailsModal').on('click', function(e) {
            if ($(e.target).is('#productDetailsModal')) {
                closeProductModal();
            }
        });
    });
</script>
