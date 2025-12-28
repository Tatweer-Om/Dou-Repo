<script>
    $(document).ready(function() {
        let currentPage = 1;

        // Function to get delivery status badge HTML
        function getDeliveryStatusBadge(status, orderTypeRaw) {
            // Only show status for delivery orders
            if (orderTypeRaw !== 'delivery') {
                return '<span class="text-gray-400">-</span>';
            }

            if (!status || status === 'not_delivered') {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 border border-red-300">
                    <span class="material-symbols-outlined text-sm align-middle">cancel</span>
                    {{ trans('messages.not_delivered', [], session('locale')) }}
                </span>`;
            } else if (status === 'delivered') {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 border border-green-300">
                    <span class="material-symbols-outlined text-sm align-middle">check_circle</span>
                    {{ trans('messages.delivered', [], session('locale')) }}
                </span>`;
            } else if (status === 'pending') {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 border border-yellow-300">
                    <span class="material-symbols-outlined text-sm align-middle">schedule</span>
                    {{ trans('messages.pending', [], session('locale')) }}
                </span>`;
            } else if (status === 'shipped') {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-300">
                    <span class="material-symbols-outlined text-sm align-middle">local_shipping</span>
                    {{ trans('messages.shipped', [], session('locale')) }}
                </span>`;
            } else if (status === 'under_preparation') {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-300">
                    <span class="material-symbols-outlined text-sm align-middle">restaurant</span>
                    {{ trans('messages.under_preparation', [], session('locale')) }}
                </span>`;
            } else if (status === 'under_repairing') {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700 border border-orange-300">
                    <span class="material-symbols-outlined text-sm align-middle">build</span>
                    {{ trans('messages.under_repairing', [], session('locale')) }}
                </span>`;
            } else {
                return `<span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-300">
                    ${status}
                </span>`;
            }
        }

        function loadOrders(page = 1) {
            currentPage = page;
            $.get("{{ url('pos/orders/list/data') }}?page=" + page, function(res) {
                if (!res.success) {
                    $('#ordersTableBody').html('<tr><td colspan="12" class="px-3 sm:px-4 md:px-6 py-8 text-center text-red-500">Error loading orders</td></tr>');
                    return;
                }

                // ---- Table Rows ----
                let rows = '';
                if (res.orders && res.orders.length > 0) {
                    $.each(res.orders, function(i, order) {
                        rows += `
                        <tr class="hover:bg-pink-50/50 transition-colors" data-id="${order.id}">
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] font-semibold whitespace-nowrap">#${order.order_no}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap">${order.customer_name || '-'}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold ${order.order_type === 'Delivery' || order.order_type === 'توصيل' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}">
                                    ${order.order_type || '-'}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap">${order.date || '-'}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap">${order.time || '-'}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] text-center whitespace-nowrap">${order.items_count || 0}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap">${parseFloat(order.subtotal || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap">${parseFloat(order.discount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] font-bold whitespace-nowrap">${parseFloat(order.paid_amount || 0).toFixed(3)} {{ trans('messages.omr', [], session('locale')) }}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap min-w-[150px]">${order.payment_methods || '-'}</td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-[var(--text-primary)] whitespace-nowrap min-w-[140px]">
                                ${getDeliveryStatusBadge(order.delivery_status || 'not_delivered', order.order_type_raw)}
                            </td>
                            <td class="px-3 sm:px-4 md:px-6 py-5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <button class="view-details-btn p-2 rounded-lg text-white bg-[var(--primary-color)] hover:bg-[var(--primary-darker)] transition shadow-sm" 
                                            data-order-data='${JSON.stringify(order)}'
                                            title="{{ trans('messages.view_details', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[22px]">visibility</span>
                                    </button>
                                    ${(order.order_type_raw === 'delivery' || order.order_type === '{{ trans('messages.delivery', [], session('locale')) }}') ? `
                                    <button class="update-status-btn p-2 rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition shadow-sm" 
                                            data-order-id="${order.id}"
                                            data-order-status="${order.delivery_status || 'not_delivered'}"
                                            title="{{ trans('messages.update_delivery_status', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[22px]">local_shipping</span>
                                    </button>
                                    ` : ''}
                                    <button onclick="window.open('{{ url('pos_bill') }}?order_id=${order.id}', '_blank')" 
                                            class="p-2 rounded-lg text-white bg-green-600 hover:bg-green-700 transition shadow-sm"
                                            title="{{ trans('messages.print', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[22px]">print</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="12" class="px-3 sm:px-4 md:px-6 py-8 text-center text-gray-500">{{ trans('messages.no_orders', [], session('locale')) ?: 'No orders found' }}</td></tr>';
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
                $('#ordersTableBody').html('<tr><td colspan="12" class="px-3 sm:px-4 md:px-6 py-8 text-center text-red-500">Error loading orders</td></tr>');
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

        // Handle status update button click
        $(document).on('click', '.update-status-btn', function() {
            let orderId = $(this).data('order-id');
            let currentStatus = $(this).data('order-status') || 'not_delivered';
            
            // Determine initial status based on current status
            let initialStatus = 'not_delivered';
            if (['pending', 'shipped', 'under_preparation', 'under_repairing'].includes(currentStatus)) {
                initialStatus = 'not_delivered';
            } else if (currentStatus) {
                initialStatus = currentStatus;
            }
            
            $('#deliveryStatusOrderId').val(orderId);
            $('#deliveryStatusSelect').val(initialStatus);
            $('#deliveryStatusModal').removeClass('hidden').addClass('flex');
        });

        // Handle status update form submission
        $('#updateDeliveryStatusForm').on('submit', function(e) {
            e.preventDefault();
            
            let orderId = $('#deliveryStatusOrderId').val();
            let status = $('#deliveryStatusSelect').val();
            
            $.ajax({
                url: "{{ route('pos.orders.update_delivery_status') }}",
                type: "POST",
                data: {
                    order_id: orderId,
                    delivery_status: status,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ trans('messages.success', [], session('locale')) }}',
                            text: res.message || '{{ trans('messages.delivery_status_updated', [], session('locale')) }}',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#deliveryStatusModal').removeClass('flex').addClass('hidden');
                        // Reload orders
                        loadOrders(currentPage);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ trans('messages.error', [], session('locale')) }}',
                            text: res.message || '{{ trans('messages.error_updating_status', [], session('locale')) }}'
                        });
                    }
                },
                error: function(xhr) {
                    let message = '{{ trans('messages.error_updating_status', [], session('locale')) }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: '{{ trans('messages.error', [], session('locale')) }}',
                        text: message
                    });
                }
            });
        });

        // Close status modal
        window.closeDeliveryStatusModal = function() {
            $('#deliveryStatusModal').removeClass('flex').addClass('hidden');
        };

        // Close status modal on backdrop click
        $('#deliveryStatusModal').on('click', function(e) {
            if ($(e.target).is('#deliveryStatusModal')) {
                closeDeliveryStatusModal();
            }
        });
    });
</script>
