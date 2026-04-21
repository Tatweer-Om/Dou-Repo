<script>
var currentPaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        saloonExpenseCategory: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentPaginationUrl = $(this).attr('href');
        fetchdata(currentPaginationUrl);
    });

    $('#saloon_expense_category_form').submit(function(e) {
        e.preventDefault();

        let id = $('#sec_category_id').val();
        let category_name = $('#sec_category_name').val().trim();

        if (!category_name) {
            show_notification('error', '<?= trans("messages.enter_category_name", [], session("locale")) ?>');
            return;
        }

        let base = "{{ url('saloon-expense-categories') }}";
        let url = id ? `${base}/${id}` : base;
        let payload = {
            category_name: category_name,
            notes: $('#sec_notes').val(),
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
                Alpine.store('modals').saloonExpenseCategory = false;

                let mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }

                $('#saloon_expense_category_form')[0].reset();
                $('#sec_category_id').val('');

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

    $('#close_saloon_expense_category_modal').click(function() {
        Alpine.store('modals').saloonExpenseCategory = false;

        let mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }

        $('#saloon_expense_category_form')[0].reset();
        $('#sec_category_id').val('');
    });

    $(document).on('click', '.edit-btn', function() {
        let id = $(this).closest('tr').data('id');

        if (!id) {
            show_notification('error', 'ID not found');
            return;
        }

        $.get("{{ url('saloon-expense-categories') }}/" + id, function(category) {
            $('#sec_category_id').val(category.id);
            $('#sec_category_name').val(category.category_name || '');
            $('#sec_notes').val(category.notes || '');

            Alpine.store('modals').saloonExpenseCategory = true;

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
                    url: '<?= url("saloon-expense-categories") ?>/' + id,
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
