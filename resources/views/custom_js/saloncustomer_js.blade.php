<script>
var currentPaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        customer: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentPaginationUrl = $(this).attr('href');
        fetchdata(currentPaginationUrl);
    });

    

    $('#customer_form').submit(function(e) {
        e.preventDefault();

        let id = $('#customer_id').val();
        let name = $('#name').val().trim();
        let phone = $('#phone').val();

        if (!name) {
            show_notification('error', '<?= trans("messages.enter_customer_name", [], session("locale")) ?>');
            return;
        }

        if (!phone) {
            show_notification('error', '<?= trans("messages.enter_customer_phone", [], session("locale")) ?>');
            return;
        }

        let url = id ? `{{ url('saloncustomers') }}/${id}` : "{{ url('saloncustomers') }}";
        let formData = new FormData(this);

        if (id) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                Alpine.store('modals').customer = false;

                let mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }

                $('#customer_form')[0].reset();
                $('#customer_id').val('');
                $('#customer_file_preview').addClass('hidden');

                fetchdata(currentPaginationUrl);

                show_notification(
                    'success',
                    id
                        ? '<?= trans("messages.updated_success", [], session("locale")) ?>'
                        : '<?= trans("messages.added_success", [], session("locale")) ?>'
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

    $('#close_modal').click(function() {
        Alpine.store('modals').customer = false;

        let mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }

        $('#customer_form')[0].reset();
        $('#customer_id').val('');
        $('#customer_file_preview').addClass('hidden');
    });

    $(document).on('click', '.edit-btn', function() {
        let id = $(this).closest('tr').data('id');

        if (!id) {
            show_notification('error', 'customer ID not found');
            return;
        }

        $.get("{{ url('saloncustomers') }}/" + id, function(customer) {
            $('#customer_id').val(customer.id);
            $('#name').val(customer.name || '');
            $('#phone').val(customer.phone || ''); 
            $('#notes').val(customer.notes || '');

            

            Alpine.store('modals').customer = true;

            let mainEl = document.querySelector('main[x-data]');
            if (mainEl && mainEl.__x) {
                mainEl.__x.$data.edit = true;
            }
        }).fail(function() {
            show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
        });
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).closest('tr').data('id');

        if (!id) {
            show_notification('error', 'customer ID not found');
            return;
        }

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
                    url: '<?= url("saloncustomers") ?>/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '<?= csrf_token() ?>'
                    },
                    success: function(data) {
                        fetchdata(currentPaginationUrl);

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

function fetchdata(url) {
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'html',
        success: function (data) {
            var newTable = $(data).find('#data-table').html();
            var newPagination = $(data).find('#data-pagination').html();

            $('#data-table').html(newTable);
            $('#data-pagination').html(newPagination);

            window.history.pushState({}, '', url);
        },
        error: function (xhr, status, error) {
            console.log(error);
            alert('Something went wrong while fetching data.');
        }
    });
}
</script>