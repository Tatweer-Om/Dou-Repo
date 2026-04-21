<script>
var currentPaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        service: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentPaginationUrl = $(this).attr('href');
        fetchdata(currentPaginationUrl);
    });

    

    $('#service_form').submit(function(e) {
        e.preventDefault();

        let id = $('#service_id').val();
        let name = $('#name').val().trim();
        let price = $('#price').val();

        if (!name) {
            show_notification('error', '<?= trans("messages.enter_service_name", [], session("locale")) ?>');
            return;
        }

        if (!price) {
            show_notification('error', '<?= trans("messages.enter_service_price", [], session("locale")) ?>');
            return;
        }

        let url = id ? `{{ url('salonservices') }}/${id}` : "{{ url('salonservices') }}";
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
                Alpine.store('modals').service = false;

                let mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }

                $('#service_form')[0].reset();
                $('#service_id').val('');
                $('#service_file_preview').addClass('hidden');

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
        Alpine.store('modals').service = false;

        let mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }

        $('#service_form')[0].reset();
        $('#service_id').val('');
        $('#service_file_preview').addClass('hidden');
    });

    $(document).on('click', '.edit-btn', function() {
        let id = $(this).closest('tr').data('id');

        if (!id) {
            show_notification('error', 'service ID not found');
            return;
        }

        $.get("{{ url('salonservices') }}/" + id, function(service) {
            $('#service_id').val(service.id);
            $('#name').val(service.name || '');
            $('#price').val(service.price || ''); 
            $('#notes').val(service.notes || '');

            

            Alpine.store('modals').service = true;

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
            show_notification('error', 'service ID not found');
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
                    url: '<?= url("salonservices") ?>/' + id,
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