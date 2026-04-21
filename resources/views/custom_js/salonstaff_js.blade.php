<script>
var currentPaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        staff: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentPaginationUrl = $(this).attr('href');
        fetchdata(currentPaginationUrl);
    });

    $('#staff_file').on('change', function(e) {
        const file = e.target.files[0];

        if (file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#staff_file_preview_img').attr('src', e.target.result);
                    $('#staff_file_preview').removeClass('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                $('#staff_file_preview').addClass('hidden');
            }
        } else {
            $('#staff_file_preview').addClass('hidden');
        }
    });

    $('#staff_form').submit(function(e) {
        e.preventDefault();

        let id = $('#staff_id').val();
        let name = $('#name').val().trim();
        let phone = $('#phone').val();

        if (!name) {
            show_notification('error', '<?= trans("messages.enter_staff_name", [], session("locale")) ?>');
            return;
        }

        if (!phone) {
            show_notification('error', '<?= trans("messages.enter_staff_phone", [], session("locale")) ?>');
            return;
        }

        let url = id ? `{{ url('salonstaffs') }}/${id}` : "{{ url('salonstaffs') }}";
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
                Alpine.store('modals').staff = false;

                let mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }

                $('#staff_form')[0].reset();
                $('#staff_id').val('');
                $('#staff_file_preview').addClass('hidden');

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
        Alpine.store('modals').staff = false;

        let mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }

        $('#staff_form')[0].reset();
        $('#staff_id').val('');
        $('#staff_file_preview').addClass('hidden');
    });

    $(document).on('click', '.edit-btn', function() {
        let id = $(this).closest('tr').data('id');

        if (!id) {
            show_notification('error', 'Staff ID not found');
            return;
        }

        $.get("{{ url('salonstaffs') }}/" + id, function(staff) {
            $('#staff_id').val(staff.id);
            $('#name').val(staff.name || '');
            $('#phone').val(staff.phone || '');
            $('#email').val(staff.email || '');
            $('#team_id').val(staff.team_id || '');
            $('#address').val(staff.address || '');

            if (staff.staff_image) {
                $('#staff_file_preview_img').attr('src', '{{ url("uploads/staff_files") }}/' + staff.staff_image);
                $('#staff_file_preview').removeClass('hidden');
            } else {
                $('#staff_file_preview').addClass('hidden');
            }

            Alpine.store('modals').staff = true;

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
            show_notification('error', 'Staff ID not found');
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
                    url: '<?= url("salonstaffs") ?>/' + id,
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

    $(document).on('click', '.view-receipt-btn', function() {
        let fileName = $(this).data('file');
        let fileUrl = '<?= url("uploads/staff_files") ?>/' + fileName;

        let fileExtension = fileName.split('.').pop().toLowerCase();
        let isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExtension);
        let isPdf = fileExtension === 'pdf';

        if (isImage) {
            Swal.fire({
                title: 'Receipt',
                html: `<img src="${fileUrl}" style="max-width: 100%; max-height: 70vh; border-radius: 8px;" alt="Receipt">`,
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