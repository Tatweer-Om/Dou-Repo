<script>
var currentSaloonExpensePaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        saloonExpense: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentSaloonExpensePaginationUrl = $(this).attr('href');
        fetchSaloonExpensePage(currentSaloonExpensePaginationUrl);
    });

    $('#se_expense_file').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    $('#se_expense_file_preview_img').attr('src', ev.target.result);
                    $('#se_expense_file_preview').removeClass('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                $('#se_expense_file_preview').addClass('hidden');
            }
        } else {
            $('#se_expense_file_preview').addClass('hidden');
        }
    });

    $('#saloon_expense_form').submit(function(e) {
        e.preventDefault();

        let id = $('#se_expense_id').val();
        let expense_name = $('#se_expense_name').val().trim();
        let amount = $('#se_amount').val();
        let expense_date = $('#se_expense_date').val();

        if (!expense_name) {
            show_notification('error', '<?= trans("messages.enter_expense_name", [], session("locale")) ?>');
            return;
        }
        if (!amount || parseFloat(amount) <= 0) {
            show_notification('error', '<?= trans("messages.enter_valid_amount", [], session("locale")) ?>');
            return;
        }
        if (!expense_date) {
            show_notification('error', '<?= trans("messages.enter_expense_date", [], session("locale")) ?>');
            return;
        }
        if (!$('#se_account_id').val()) {
            show_notification('error', '<?= trans("messages.select_account", [], session("locale")) ?>');
            return;
        }

        let base = "{{ url('saloon-expenses') }}";
        let url = id ? `${base}/${id}` : base;
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
                Alpine.store('modals').saloonExpense = false;

                let mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }

                $('#saloon_expense_form')[0].reset();
                $('#se_expense_id').val('');
                $('#se_expense_date').val('<?= date('Y-m-d') ?>');
                $('#se_expense_file_preview').addClass('hidden');

                fetchSaloonExpensePage(currentSaloonExpensePaginationUrl);

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

    $('#close_saloon_expense_modal').click(function() {
        Alpine.store('modals').saloonExpense = false;

        let mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }

        $('#saloon_expense_form')[0].reset();
        $('#se_expense_id').val('');
        $('#se_expense_date').val('<?= date('Y-m-d') ?>');
        $('#se_expense_file_preview').addClass('hidden');
    });

    $(document).on('click', '.edit-btn', function() {
        let rowId = $(this).closest('tr').data('id');

        if (!rowId) {
            show_notification('error', 'ID not found');
            return;
        }

        $.get("{{ url('saloon-expenses') }}/" + rowId, function(expense) {
            $('#se_expense_id').val(expense.id);
            $('#se_expense_name').val(expense.expense_name || '');
            $('#se_category_id').val(expense.salon_expense_category_id || '');
            $('#se_amount').val(expense.amount);
            let expenseDate = expense.expense_date;
            if (expenseDate) {
                let d = new Date(expenseDate);
                if (!isNaN(d.getTime())) {
                    let y = d.getFullYear();
                    let m = String(d.getMonth() + 1).padStart(2, '0');
                    let day = String(d.getDate()).padStart(2, '0');
                    expenseDate = y + '-' + m + '-' + day;
                } else if (typeof expenseDate === 'string' && expenseDate.includes(' ')) {
                    expenseDate = expenseDate.split(' ')[0];
                }
            }
            $('#se_expense_date').val(expenseDate || '');
            $('#se_account_id').val(expense.payment_method || '');
            $('#se_reciept_no').val(expense.reciept_no || '');
            $('#se_notes').val(expense.notes || '');

            if (expense.expense_image) {
                $('#se_expense_file_preview_img').attr('src', '{{ url("uploads/salon_expense_files") }}/' + expense.expense_image);
                $('#se_expense_file_preview').removeClass('hidden');
            } else {
                $('#se_expense_file_preview').addClass('hidden');
            }

            Alpine.store('modals').saloonExpense = true;

            let mainEl = document.querySelector('main[x-data]');
            if (mainEl && mainEl.__x) {
                mainEl.__x.$data.edit = true;
            }
        }).fail(function() {
            show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
        });
    });

    $(document).on('click', '.delete-btn', function() {
        let rowId = $(this).closest('tr').data('id');

        if (!rowId) {
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
                    url: '<?= url("saloon-expenses") ?>/' + rowId,
                    method: 'DELETE',
                    data: {
                        _token: '<?= csrf_token() ?>'
                    },
                    success: function() {
                        fetchSaloonExpensePage(currentSaloonExpensePaginationUrl);
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

    $(document).on('click', '.view-receipt-btn', function() {
        let fileName = $(this).data('file');
        let fileUrl = '<?= url("uploads/salon_expense_files") ?>/' + fileName;
        let fileExtension = fileName.split('.').pop().toLowerCase();
        let isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExtension);
        let isPdf = fileExtension === 'pdf';

        if (isImage) {
            Swal.fire({
                title: 'Receipt',
                html: '<img src="' + fileUrl + '" style="max-width: 100%; max-height: 70vh; border-radius: 8px;" alt="Receipt">',
                showCloseButton: true,
                showConfirmButton: true,
                confirmButtonText: 'Download',
                confirmButtonColor: '#3085d6',
                showCancelButton: true,
                cancelButtonText: 'Close',
                width: 'auto',
                padding: '20px'
            }).then((result) => {
                if (result.isConfirmed) {
                    let link = document.createElement('a');
                    link.href = fileUrl;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
        } else if (isPdf) {
            Swal.fire({
                title: 'Receipt PDF',
                text: 'Choose an action',
                icon: 'info',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'View',
                denyButtonText: 'Download',
                cancelButtonText: 'Close',
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(fileUrl, '_blank');
                } else if (result.isDenied) {
                    let link = document.createElement('a');
                    link.href = fileUrl;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
        } else {
            let link = document.createElement('a');
            link.href = fileUrl;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
});

function fetchSaloonExpensePage(url) {
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'html',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
