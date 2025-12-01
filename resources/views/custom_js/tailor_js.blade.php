<script>
    $(document).ready(function() {

    function loadtailors(page = 1) {
    $.get("{{ url('tailors/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, tailor) {
            rows += `
            <tr class="hover:bg-pink-50/50 transition-tailors" data-id="${tailor.id}">
              <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${tailor.tailor_name}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${tailor.tailor_phone}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${tailor.tailor_address}</td>

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
                <a href="{{ url('tailors/list') }}?page=${i}">${i}</a>
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
        if (page) loadtailors(page);
    }
});


        // Initial load
        loadtailors();

        $('#search_tailor').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        // Add / Update tailor
        $('#tailor_form').submit(function(e) {
            e.preventDefault();
            let id = $('#tailor_id').val();
            let tailor_name = $('#tailor_name').val().trim();
            let tailor_phone = $('#tailor_phone').val().trim();

            // Simple validation
            if (!tailor_name) {
                show_notification('error', '<?= trans("messages.enter_tailor_name_ar", [], session("locale")) ?>');
                return;
            }
        
            if (!tailor_phone) {
                show_notification('error', '<?= trans("messages.enter_tailor_phone", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('tailors') }}/${id}` : "{{ url('tailors') }}";

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
                    $('#tailor_form')[0].reset();
                    $('#tailor_id').val('');
                    loadtailors();
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

        // Edit tailor
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');
            $.get("{{ url('tailors') }}/" + id, function(tailor) {
                $('#tailor_id').val(tailor.id);
                $('#tailor_name').val(tailor.tailor_name);
                $('#tailor_phone').val(tailor.tailor_phone);
                 $('#tailor_address').val(tailor.tailor_address);
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete tailor
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).closest('tr').data('id');

            Swal.fire({
                title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
                text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtontailor: '#3085d6',
                cancelButtontailor: '#d33',
                confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
                cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= url("tailors") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadtailors(); // reload table
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