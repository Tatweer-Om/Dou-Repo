<script>
    $(document).ready(function() {
        let currentPage = 1;

        function loadAuditList(page = 1) {
            currentPage = page;
            $.get("{{ url('stock/audit/list') }}?page=" + page, function(res) {
                if (!res.success) {
                    $('#auditTableBody').html('<tr><td colspan="8" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading audit data</td></tr>');
                    return;
                }

                // ---- Table Rows ----
                let rows = '';
                if (res.data && res.data.length > 0) {
                    $.each(res.data, function(i, item) {
                        rows += `
                        <tr class="hover:bg-pink-50/50 transition-colors">
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold">${item.barcode || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold">${item.abaya_code || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${item.design_name || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold text-green-600">${item.quantity_added || 0}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-red-600">${item.quantity_sold_pos || 0}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-orange-600">${item.quantity_transferred_out || 0}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-blue-600">${item.quantity_received || 0}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-bold text-primary">${item.remaining_quantity || 0}</td>
                        </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="8" class="px-4 sm:px-6 py-8 text-center text-gray-500">{{ trans('messages.no_data', [], session('locale')) ?: 'No data found' }}</td></tr>';
                }
                $('#auditTableBody').html(rows);

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
                $('#auditTableBody').html('<tr><td colspan="8" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading audit data</td></tr>');
            });
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page) {
                loadAuditList(page);
            }
        });

        // Initial load
        loadAuditList();

        // Search functionality
        $('#search_audit').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
    });
</script>
