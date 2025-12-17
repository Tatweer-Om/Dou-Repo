<script>
    $(document).ready(function() {

       function loadCities(page = 1) {
    $.get("{{ url('cities/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, city) {
            const notesPreview = city.notes ? 
                (city.notes.length > 50 ? city.notes.substring(0, 50) + '...' : city.notes) : 
                '-';
            const locale = '{{ session('locale', 'en') }}';
            const areaDisplay = city.area ? 
                (locale === 'ar' ? 
                    (city.area.area_name_ar || city.area.area_name_en) : 
                    (city.area.area_name_en || city.area.area_name_ar)) : 
                '-';
            
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${city.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${areaDisplay}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${city.city_name_en || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${city.city_name_ar || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${city.delivery_charges ?? '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${notesPreview}</td>
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
                <a href="{{ url('cities/list') }}?page=${i}">${i}</a>
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
        if (page) loadCities(page);
    }
});

        // Initial load
        loadCities();

        $('#search_city').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        
        // Add / Update city
        $('#city_form').submit(function(e) {
            e.preventDefault();
            let id = $('#city_id').val();
            let area_id = $('#area_id').val();
            let city_name_en = $('#city_name_en').val().trim();
            let city_name_ar = $('#city_name_ar').val().trim();
            let notes = $('#notes').val().trim();
            let delivery_charges = $('#delivery_charges').val().trim();

            // Simple validation
            if (!area_id) {
                show_notification('error', '<?= trans("messages.please_select_area", [], session("locale")) ?>');
                return;
            }
            if (!city_name_en) {
                show_notification('error', '<?= trans("messages.enter_city_name_en", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('cities') }}/${id}` : "{{ url('cities') }}";

            // Serialize form data
            let data = {
                area_id: area_id,
                city_name_en: city_name_en,
                city_name_ar: city_name_ar,
                delivery_charges: delivery_charges,
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
                    $('#city_form')[0].reset();
                    $('#city_id').val('');
                    loadCities();
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
            $('#city_form')[0].reset();
            $('#city_id').val('');
        });

        // Edit city
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('cities') }}/" + id, function(city) {
                $('#city_id').val(city.id);
                $('#area_id').val(city.area_id || '');
                $('#city_name_en').val(city.city_name_en || '');
                $('#city_name_ar').val(city.city_name_ar || '');
                $('#delivery_charges').val(city.delivery_charges || '');
                $('#notes').val(city.notes || '');
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete city
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
                        url: '<?= url("cities") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadCities(); // reload table
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

