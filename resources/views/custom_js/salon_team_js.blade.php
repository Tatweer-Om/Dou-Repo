<script>
var currentTeamPaginationUrl = window.location.href;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        salonTeam: false
    });
});

$(document).ready(function () {

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentTeamPaginationUrl = $(this).attr('href');
        fetchTeamTable(currentTeamPaginationUrl);
    });

    $('#salon_team_form').submit(function (e) {
        e.preventDefault();

        var id = $('#st_team_id').val();
        var code = $('#st_code').val().trim();
        var name = $('#st_name').val().trim();

        if (!code || !name) {
            show_notification('error', '<?= e(trans('messages.salon_team_validation_required', [], session('locale'))) ?>');
            return;
        }

        var base = "<?= url('salon-teams') ?>";
        var url = id ? base + '/' + id : base;
        var payload = {
            code: code,
            name: name,
            name_ar: $('#st_name_ar').val(),
            sort_order: $('#st_sort_order').val() || 0,
            is_active: $('#st_is_active').is(':checked') ? 1 : 0,
            _token: '<?= csrf_token() ?>'
        };

        if (id) {
            payload._method = 'PUT';
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            success: function () {
                Alpine.store('modals').salonTeam = false;
                var mainEl = document.querySelector('main[x-data]');
                if (mainEl && mainEl.__x) {
                    mainEl.__x.$data.edit = false;
                }
                $('#salon_team_form')[0].reset();
                $('#st_team_id').val('');
                $('#st_is_active').prop('checked', true);
                fetchTeamTable(currentTeamPaginationUrl);
                show_notification('success', id
                    ? '<?= e(trans('messages.updated_success', [], session('locale'))) ?>'
                    : '<?= e(trans('messages.added_success', [], session('locale'))) ?>');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON && xhr.responseJSON.errors;
                    if (errors) {
                        $.each(errors, function (key, value) {
                            show_notification('error', value[0]);
                        });
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        show_notification('error', xhr.responseJSON.message);
                    }
                } else {
                    show_notification('error', '<?= e(trans('messages.generic_error', [], session('locale'))) ?>');
                }
            }
        });
    });

    $('#close_salon_team_modal').click(function () {
        Alpine.store('modals').salonTeam = false;
        var mainEl = document.querySelector('main[x-data]');
        if (mainEl && mainEl.__x) {
            mainEl.__x.$data.edit = false;
        }
        $('#salon_team_form')[0].reset();
        $('#st_team_id').val('');
        $('#st_is_active').prop('checked', true);
    });

    $(document).on('click', '.edit-team-btn', function () {
        var id = $(this).closest('tr').data('id');
        if (!id) {
            return;
        }
        $.get("<?= url('salon-teams') ?>/" + id, function (team) {
            $('#st_team_id').val(team.id);
            $('#st_code').val(team.code || '');
            $('#st_name').val(team.name || '');
            $('#st_name_ar').val(team.name_ar || '');
            $('#st_sort_order').val(team.sort_order != null ? team.sort_order : 0);
            $('#st_is_active').prop('checked', !!team.is_active);
            Alpine.store('modals').salonTeam = true;
            var mainEl = document.querySelector('main[x-data]');
            if (mainEl && mainEl.__x) {
                mainEl.__x.$data.edit = true;
            }
        }).fail(function () {
            show_notification('error', '<?= e(trans('messages.fetch_error', [], session('locale'))) ?>');
        });
    });

    $(document).on('click', '.delete-team-btn', function () {
        var id = $(this).closest('tr').data('id');
        if (!id) {
            return;
        }
        Swal.fire({
            title: '<?= e(trans('messages.confirm_delete_title', [], session('locale'))) ?>',
            text: '<?= e(trans('messages.confirm_delete_text', [], session('locale'))) ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= e(trans('messages.yes_delete', [], session('locale'))) ?>',
            cancelButtonText: '<?= e(trans('messages.cancel', [], session('locale'))) ?>'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: '<?= url('salon-teams') ?>/' + id,
                method: 'DELETE',
                data: { _token: '<?= csrf_token() ?>' },
                success: function () {
                    fetchTeamTable(currentTeamPaginationUrl);
                    Swal.fire('<?= e(trans('messages.deleted_success', [], session('locale'))) ?>', '', 'success');
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : '<?= e(trans('messages.delete_error', [], session('locale'))) ?>';
                    Swal.fire('<?= e(trans('messages.error', [], session('locale'))) ?>', msg, 'error');
                }
            });
        });
    });
});

function fetchTeamTable(url) {
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
        error: function () {
            alert('Something went wrong while fetching data.');
        }
    });
}
</script>
