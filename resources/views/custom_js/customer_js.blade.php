<script>
    $(document).ready(function() {

    // Load cities when area changes
    $('#customer_area_id').on('change', function() {
        let areaId = $(this).val();
        let citySelect = $('#customer_city_id');
        
        // Clear existing options except first one
        citySelect.find('option:not(:first)').remove();
        
        if (areaId) {
            // Filter cities by area_id from the preloaded options
            $('#customer_city_id option[data-area-id]').each(function() {
                if ($(this).data('area-id') == areaId) {
                    $(this).clone().appendTo(citySelect);
                }
            });
        } else {
            // If no area selected, show all cities
            @foreach($cities as $city)
                @if($city->area_id)
                citySelect.append('<option value="{{ $city->id }}" data-area-id="{{ $city->area_id }}">{{ session('locale') == 'ar' ? ($city->city_name_ar ?: $city->city_name_en) : ($city->city_name_en ?: $city->city_name_ar) }}</option>');
                @endif
            @endforeach
        }
    });

    function loadCustomers(page = 1) {
    $.get("{{ url('customers/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, customer) {
            const locale = '{{ session('locale', 'en') }}';
            const cityDisplay = customer.city ? 
                (locale === 'ar' ? 
                    (customer.city.city_name_ar || customer.city.city_name_en) : 
                    (customer.city.city_name_en || customer.city.city_name_ar)) : 
                '-';
            const areaDisplay = customer.area ? 
                (locale === 'ar' ? 
                    (customer.area.area_name_ar || customer.area.area_name_en) : 
                    (customer.area.area_name_en || customer.area.area_name_ar)) : 
                '-';
            
            const profileUrl = "{{ url('customer_profile') }}/" + customer.id;
            const customerName = customer.name || '-';
            const customerPhone = customer.phone || '-';
            
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${customer.id}">
              <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">
                <a href="${profileUrl}" class="text-[var(--primary-color)] hover:text-[var(--primary-darker)] hover:underline font-semibold transition">
                  ${customerName}
                </a>
              </td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">
                  <a href="${profileUrl}" class="text-[var(--primary-color)] hover:text-[var(--primary-darker)] hover:underline transition">
                    ${customerPhone}
                  </a>
                </td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${cityDisplay}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${areaDisplay}</td>

                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
    <a href="{{ url('customer_profile') }}/${customer.id}" class="icon-btn text-[var(--primary-color)] hover:text-[var(--primary-darker)]" title="{{ trans('messages.view_profile', [], session('locale')) ?: 'View Profile' }}">
        <span class="material-symbols-outlined">person</span>
    </a>
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
                <a href="{{ url('customers/list') }}?page=${i}">${i}</a>
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
        if (page) loadCustomers(page);
    }
});


        // Initial load
        loadCustomers();

        $('#search_customer').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        
        // Add / Update customer
        $('#customer_form').submit(function(e) {
            e.preventDefault();
            let id = $('#customer_id').val();
            let name = $('#customer_name').val().trim();

            // Simple validation
            if (!name) {
                show_notification('error', '{{ trans('messages.enter_customer_name', [], session('locale')) ?: 'Please enter customer name' }}');
                return;
            }

            let url = id ? `{{ url('customers') }}/${id}` : "{{ url('customers') }}";

            // Serialize form data
            let data = $(this).serialize();
            if (id) data += '&_method=PUT';

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(res) {
                    // Reset Alpine.js state using custom event
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    // Reset form
                    $('#customer_form')[0].reset();
                    $('#customer_id').val('');
                    $('#customer_area_id').trigger('change'); // Reset city dropdown
                    loadCustomers();
                    show_notification(
                        'success',
                        id ?
                        '{{ trans('messages.updated_success', [], session('locale')) }}' :
                        '{{ trans('messages.added_success', [], session('locale')) }}'
                    );
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            show_notification('error', value[0]);
                        });
                    } else {
                        show_notification('error', '{{ trans('messages.generic_error', [], session('locale')) }}');
                    }
                }
            });
        });


        // Close modal button
        $('#close_modal').click(function() {
            // Reset Alpine.js state using custom event
            window.dispatchEvent(new CustomEvent('close-modal'));
        });

        // Edit customer
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');
            $.get("{{ url('customers') }}/" + id, function(customer) {
                $('#customer_id').val(customer.id);
                $('#customer_name').val(customer.name);
                $('#customer_phone').val(customer.phone);
                $('#customer_area_id').val(customer.area_id || '');
                $('#customer_notes').val(customer.notes || '');
                
                // Trigger area change to load cities, then set city
                $('#customer_area_id').trigger('change');
                
                // Set city after a small delay to ensure cities are loaded
                setTimeout(function() {
                    $('#customer_city_id').val(customer.city_id || '');
                }, 100);
                
                // Update modal title
                $('#modal_title').text('{{ trans('messages.edit_customer', [], session('locale')) ?: 'Edit Customer' }}');
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '{{ trans('messages.fetch_error', [], session('locale')) }}');
            });
        });

        // Delete customer
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).closest('tr').data('id');

            Swal.fire({
                title: '{{ trans('messages.confirm_delete_title', [], session('locale')) }}',
                text: '{{ trans('messages.confirm_delete_text', [], session('locale')) }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ trans('messages.yes_delete', [], session('locale')) }}',
                cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url('customers') }}/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            loadCustomers(); // reload table
                            Swal.fire(
                                '{{ trans('messages.deleted_success', [], session('locale')) }}',
                                '{{ trans('messages.deleted_success_text', [], session('locale')) }}',
                                'success'
                            );
                        },
                        error: function() {
                            Swal.fire(
                                '{{ trans('messages.delete_error', [], session('locale')) }}',
                                '{{ trans('messages.delete_error_text', [], session('locale')) }}',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        // Reset modal title when opening add modal
        $('button[onclick*="open = true"], button[@click="open = true"]').on('click', function() {
            $('#modal_title').text('{{ trans('messages.add_customer', [], session('locale')) ?: 'Add Customer' }}');
            $('#customer_form')[0].reset();
            $('#customer_id').val('');
            $('#customer_area_id').trigger('change');
        });

    });
</script>
