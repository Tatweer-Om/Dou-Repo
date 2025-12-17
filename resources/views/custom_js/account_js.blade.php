<script>
    $(document).ready(function() {

       function loadAccounts(page = 1) {
    $.get("{{ url('accounts/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, account) {
            const accountTypeText = account.account_type == 1 ? 
                '{{ trans('messages.normal_account', [], session('locale')) }}' : 
                (account.account_type == 2 ? '{{ trans('messages.saving_account', [], session('locale')) }}' : '-');
            const statusText = account.account_status == 1 ? 
                '{{ trans('messages.active', [], session('locale')) }}' : 
                '{{ trans('messages.inactive', [], session('locale')) }}';
            const statusClass = account.account_status == 1 ? 'text-green-600' : 'text-red-600';
            
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${account.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${account.account_name || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${account.account_branch || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${account.account_no || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${account.opening_balance ? parseFloat(account.opening_balance).toFixed(3) : '0.000'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${account.commission ? parseFloat(account.commission).toFixed(2) : '0.00'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${accountTypeText}</td>
                <td class="px-4 sm:px-6 py-5 ${statusClass} font-semibold">${statusText}</td>
                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
                        <button class="edit-btn icon-btn">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="delete-btn icon-btn hover:text-red-500">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });
        $('tbody').html(rows);

        // ---- Pagination ----
        let pagination = '';

        // Previous
        pagination += `
        <li class="px-3 py-1 rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
            <a href="${res.prev_page_url ? res.prev_page_url : '#'}">&laquo;</a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= res.last_page; i++) {
            pagination += `
            <li class="px-3 py-1 rounded-full ${res.current_page == i ? ' text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                <a href="{{ url('accounts/list') }}?page=${i}">${i}</a>
            </li>
            `;
        }

        // Next
        pagination += `
        <li class="px-3 py-1 rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
            <a href="${res.next_page_url ? res.next_page_url : '#'}">&raquo;</a>
        </li>`;

        $('#pagination').html(pagination);
    });
}

// Handle pagination click
$(document).on('click', '#pagination a', function(e) {
    e.preventDefault();
    let href = $(this).attr('href');
    if (href && href !== '#') {
        let page = new URL(href).searchParams.get('page');
        if (page) loadAccounts(page);
    }
});

        // Initial load
        loadAccounts();

        $('#search_account').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        
        // Add / Update account
        $('#account_form').submit(function(e) {
            e.preventDefault();
            let id = $('#account_edit_id').val();
            let account_name = $('#account_name').val().trim();
            let account_branch = $('#account_branch').val().trim();
            let account_no = $('#account_no').val().trim();
            let opening_balance = $('#opening_balance').val() || 0;
            let commission = $('#commission').val() || 0;
            let account_type = $('#account_type').val();
            let account_status = $('#account_status').val() || 1;
            let notes = $('#notes').val().trim();

            // Simple validation
            if (!account_name) {
                show_notification('error', '<?= trans("messages.enter_account_name", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('accounts') }}/${id}` : "{{ url('accounts') }}";

            // Serialize form data
            let data = {
                account_name: account_name,
                account_branch: account_branch,
                account_no: account_no,
                opening_balance: opening_balance,
                commission: commission,
                account_type: account_type,
                account_status: account_status,
                notes: notes,
                _token: '{{ csrf_token() }}'
            };
            
            if (id) {
                data._method = 'PUT';
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(res) {
                    // Reset Alpine.js state using custom event
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    // Reset form
                    $('#account_form')[0].reset();
                    $('#account_edit_id').val('');
                    loadAccounts();
                    show_notification(
                        'success',
                        id ?
                        '<?= trans("messages.updated_success", [], session("locale")) ?>' :
                        '<?= trans("messages.added_success", [], session("locale")) ?>'
                    );
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            show_notification('error', value[0]);
                        });
                    } else {
                        show_notification('error', '<?= trans("messages.generic_error", [], session("locale")) ?>');
                    }
                }
            });
        });

        // Close modal button
        $('#close_modal').click(function() {
            // Reset Alpine.js state using custom event
            window.dispatchEvent(new CustomEvent('close-modal'));
            $('#account_form')[0].reset();
            $('#account_edit_id').val('');
        });

        // Edit account
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('accounts') }}/" + id, function(account) {
                $('#account_edit_id').val(account.id);
                $('#account_name').val(account.account_name || '');
                $('#account_branch').val(account.account_branch || '');
                $('#account_no').val(account.account_no || '');
                $('#opening_balance').val(account.opening_balance || '');
                $('#commission').val(account.commission || '');
                $('#account_type').val(account.account_type || '');
                $('#account_status').val(account.account_status || 1);
                $('#notes').val(account.notes || '');
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete account
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).closest('tr').data('id');

            Swal.fire({
                title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
                text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
                cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= url("accounts") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadAccounts(); // reload table
                            Swal.fire(
                                '<?= trans("messages.deleted_success", [], session("locale")) ?>',
                                '<?= trans("messages.deleted_success_text", [], session("locale")) ?>',
                                'success'
                            );
                        },
                        error: function() {
                            Swal.fire(
                                '<?= trans("messages.delete_error", [], session("locale")) ?>',
                                '<?= trans("messages.delete_error_text", [], session("locale")) ?>',
                                'error'
                            );
                        }
                    });
                }
            });
        });

    });
</script>

