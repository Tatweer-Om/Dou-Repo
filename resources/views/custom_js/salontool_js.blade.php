<script>
var currentPaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        tool: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentPaginationUrl = $(this).attr('href');
        fetchdata(currentPaginationUrl);
    });

    $('#tool_form').submit(function(e) {
        e.preventDefault();

        let id = $('#tool_id').val();
        let name = $('#tool_name').val().trim();

        if (!name) {
            show_notification('error', '<?= trans("messages.enter_tool_name", [], session("locale")) ?>');
            return;
        }

        let url = id ? `{{ url('salontools') }}/${id}` : "{{ url('salontools') }}";
        let payload = {
            name: $('#tool_name').val(),
            price: $('#tool_price').val(),
            notes: $('#tool_notes').val(),
            _token: '<?= csrf_token() ?>'
        };

        if (id) {
            payload._method = 'PUT';
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            success: function(res) {
                Alpine.store('modals').tool = false;

                let mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }

                $('#tool_form')[0].reset();
                $('#tool_id').val('');

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

    $('#close_tool_modal').click(function() {
        Alpine.store('modals').tool = false;

        let mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }

        $('#tool_form')[0].reset();
        $('#tool_id').val('');
    });

    $(document).on('click', '.edit-btn', function() {
        let id = $(this).closest('tr').data('id');

        if (!id) {
            show_notification('error', 'ID not found');
            return;
        }

        $.get("{{ url('salontools') }}/" + id, function(tool) {
            $('#tool_id').val(tool.id);
            $('#tool_name').val(tool.name || '');
            $('#tool_price').val(tool.price ?? '');
            $('#tool_notes').val(tool.notes || '');

            Alpine.store('modals').tool = true;

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
            show_notification('error', 'ID not found');
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
                    url: '<?= url("salontools") ?>/' + id,
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
