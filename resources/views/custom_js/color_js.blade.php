<script>
    $(document).ready(function() {

    function loadcolors(page = 1) {
    $.get("{{ url('colors/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, color) {
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${color.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${color.color_name_ar}</td>
                <td class="px-4 sm:px-6 py-4">
                    <div class="w-8 h-8 rounded-lg shadow-inner" style="background:${color.color_code}"></div>
                </td>
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
                <a href="{{ url('colors/list') }}?page=${i}">${i}</a>
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
        if (page) loadcolors(page);
    }
});


        // Initial load
        loadcolors();

        $('#search_color').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        // Add / Update color
        $('#color_form').submit(function(e) {
            e.preventDefault();
            let id = $('#color_id').val();
            let color_name_ar = $('#color_name_ar').val().trim();
            let color_name_en = $('#color_name_en').val().trim();
            let color_code = $('#color_code').val().trim();

            // Simple validation
            if (!color_name_ar) {
                show_notification('error', '<?= trans("messages.enter_color_name_ar", [], session("locale")) ?>');
                return;
            }
            if (!color_name_en) {
                show_notification('error', '<?= trans("messages.enter_color_name_en", [], session("locale")) ?>');
                return;
            }
      
            if (!color_code) {
                show_notification('error', '<?= trans("messages.enter_color_code", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('colors') }}/${id}` : "{{ url('colors') }}";

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
                    $('#color_form')[0].reset();
                    $('#color_id').val('');
                    loadcolors();
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

        // Edit color
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');
            $.get("{{ url('colors') }}/" + id, function(color) {
                $('#color_id').val(color.id);
                $('#color_name_en').val(color.color_name_en);
                $('#color_name_ar').val(color.color_name_ar);
                $('#color_code').val(color.color_code_en);
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete color
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
                        url: '<?= url("colors") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadcolors(); // reload table
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