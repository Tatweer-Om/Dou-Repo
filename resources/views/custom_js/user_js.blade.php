<script>
    $(document).ready(function() {

    function loadusers(page = 1) {
    $.get("{{ url('users/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, user) {
            rows += `
            <tr class="hover:bg-pink-50/50 transition-users" data-id="${user.id}">
              <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${user.user_name}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${user.user_phone}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${user.user_email}</td>
                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
    <button class="edit-btn icon-btn">
        <span class="material-symbols-outlined">{{ trans('messages.edit', [], session('locale')) }}</span>
    </button>
    <button class="delete-btn icon-btn hover:text-red-500">
        <span class="material-symbols-outlined">{{ trans('messages.delete', [], session('locale')) }}</span>
    </button>
    <button 
        title="{{ trans('messages.view_profile', [], session('locale')) }}" 
        class="p-2 rounded-lg text-white bg-[var(--primary-color)] hover:bg-[var(--primary-darker)] transition shadow-sm">
        <span class="material-symbols-outlined text-[22px]">person</span>
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
                <a href="{{ url('users/list') }}?page=${i}">${i}</a>
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
        if (page) loadusers(page);
    }
});


        // Initial load
        loadusers();

        $('#search_user').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        // Add / Update user
        $('#user_form').submit(function(e) {
            e.preventDefault();
            let id = $('#user_id').val();
            let user_name = $('#user_name').val().trim();
            let user_phone = $('#user_phone').val().trim();

            // Simple validation
            if (!user_name) {
                show_notification('error', '<?= trans("messages.enter_user_name_ar", [], session("locale")) ?>');
                return;
            }
        
            if (!user_phone) {
                show_notification('error', '<?= trans("messages.enter_user_phone", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('users') }}/${id}` : "{{ url('users') }}";

            // Serialize form data
            let data = $(this).serialize();
            if (id) data += '&_method=PUT'; // Important for Laravel to recognize PUT

            $.ajax({
                url: url,
                method: 'POST', // Always POST
                data: data,
                success: function(res) {
                    // Reset Alpine.js state using custom event
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    // Reset form
                    $('#user_form')[0].reset();
                    $('#user_id').val('');
                    loadusers();
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
        });

        // Edit user
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');
            $.get("{{ url('users') }}/" + id, function(user) {
                $('#user_id').val(user.id);
                $('#user_name').val(user.user_name);
                $('#user_phone').val(user.user_phone);
                 $('#user_email').val(user.user_email);
                $('#user_password').val(user.password);
                $('#notes').val(user.notes);

                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete user
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).closest('tr').data('id');

            Swal.fire({
                title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
                text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonuser: '#3085d6',
                cancelButtonuser: '#d33',
                confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
                cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= url("users") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadusers(); // reload table
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