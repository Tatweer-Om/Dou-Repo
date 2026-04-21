<script>
    $(document).ready(function() {

    var MSG_USER_TYPE_ADMIN = @json(trans('messages.user_type_admin', [], session('locale')));
    var MSG_USER_TYPE_STAFF = @json(trans('messages.user_type_staff_user', [], session('locale')));
    var MSG_STAFF_REQUIRED = @json(trans('messages.salon_staff_required_for_user_type', [], session('locale')));

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function syncUserTypeStaffUi() {
        var isStaffUser = $('input[name="user_type"][value="user"]').is(':checked');
        var $sel = $('#salon_staff_id');
        if (isStaffUser) {
            $sel.prop('disabled', false).removeAttr('disabled');
        } else {
            $sel.prop('disabled', true);
            $sel.val('');
        }
        $('#salon_staff_required_mark').toggleClass('hidden', !isStaffUser);
    }

    $(document).on('change', 'input[name="user_type"]', function() {
        var prevStaff = $('#salon_staff_id').val();
        syncUserTypeStaffUi();
        var editPk = $('#user_id').val();
        if ($('input[name="user_type"][value="user"]').is(':checked')) {
            refreshStaffDropdown(editPk || null, prevStaff || null);
        }
    });

    var STAFF_PLACEHOLDER = @json(trans('messages.salon_staff_select_placeholder', [], session('locale')));

    function refreshStaffDropdown(editingUserPk, selectedStaffId) {
        $.get("{{ url('users/staff-options') }}", function(list) {
            var $sel = $('#salon_staff_id');
            $sel.empty();
            $sel.append($('<option></option>').attr('value', '').text(STAFF_PLACEHOLDER));
            $.each(list, function(_, st) {
                if (st.id == null) {
                    return;
                }
                var label = (st.name || '—') + (st.team_label ? ' — ' + st.team_label : '');
                var $o = $('<option></option>')
                    .attr('value', String(st.id))
                    .text(label);
                $sel.append($o);
            });
            if (selectedStaffId) {
                $sel.val(String(selectedStaffId));
            }
            syncUserTypeStaffUi();
        }).fail(function() {
            syncUserTypeStaffUi();
        });
    }

    function loadusers(page = 1) {
    $.get("{{ url('users/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, user) {
            var ut = (user.user_type === 'user') ? MSG_USER_TYPE_STAFF : MSG_USER_TYPE_ADMIN;
            var st = (user.salon_staff && user.salon_staff.name) ? escapeHtml(user.salon_staff.name) : '—';
            rows += `
            <tr class="hover:bg-pink-50/50 transition-users" data-id="${user.id}">
              <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${escapeHtml(user.user_name)}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${escapeHtml(String(user.user_phone))}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${escapeHtml(user.user_email || '')}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-sm whitespace-nowrap">${ut}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-sm">${st}</td>
                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
    <button class="edit-btn icon-btn">
        <span class="material-symbols-outlined">{{ trans('messages.edit', [], session('locale')) }}</span>
    </button>
    <button class="delete-btn icon-btn hover:text-red-500">
        <span class="material-symbols-outlined">{{ trans('messages.delete', [], session('locale')) }}</span>
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
                <a href="{{ url('users/list') }}?page=${i}">${i}</a>
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
        if (page) loadusers(page);
    }
});


        window.resetUserForm = function() {
            var f = document.getElementById('user_form');
            if (f) f.reset();
            $('#user_id').val('');
            $('input[name="permissions[]"]').prop('checked', false);
            $('input[name="user_scope"][value="boutique"]').prop('checked', true);
            $('input[name="user_type"][value="admin"]').prop('checked', true);
            refreshStaffDropdown(null, null);
            syncToggleAllLabel();
        };

        // Initial load — staff list from API (assignable only); disable staff select while type is Admin
        loadusers();
        syncUserTypeStaffUi();
        refreshStaffDropdown(null, null);
        syncToggleAllLabel();

        var PERM_BOUTIQUE_IDS = [1,2,3,4,5,6,7,8,9,10,11,12,13];
        var PERM_SALON_IDS = [14,15,16,17,18,19,20,21,22,23,24,25,26,27,28];
        var PERM_ALL_IDS = PERM_BOUTIQUE_IDS.concat(PERM_SALON_IDS);

        function setPermissionsByIds(ids, checked) {
            ids.forEach(function(id) {
                $('#permission_' + id).prop('checked', checked);
            });
        }

        function syncToggleAllLabel() {
            var total = $('input[name="permissions[]"]').length;
            var n = $('input[name="permissions[]"]:checked').length;
            var allOn = total > 0 && n === total;
            $('#toggleAllPermissions').text(allOn ?
                '<?= trans("messages.deselect_all", [], session("locale")) ?: "Deselect All" ?>' :
                '<?= trans("messages.select_all", [], session("locale")) ?: "Select All" ?>'
            );
        }

        $(document).on('change', 'input[name="permissions[]"]', syncToggleAllLabel);

        $('#toggleAllPermissions').click(function() {
            var total = $('input[name="permissions[]"]').length;
            var n = $('input[name="permissions[]"]:checked').length;
            var turnOn = !(total > 0 && n === total);
            $('input[name="permissions[]"]').prop('checked', turnOn);
            syncToggleAllLabel();
        });

        $('#btnPermAll').click(function() {
            setPermissionsByIds(PERM_ALL_IDS, true);
            syncToggleAllLabel();
        });
        $('#btnPermSalonOnly').click(function() {
            $('input[name="permissions[]"]').prop('checked', false);
            setPermissionsByIds(PERM_SALON_IDS, true);
            syncToggleAllLabel();
        });
        $('#btnPermBoutiqueOnly').click(function() {
            $('input[name="permissions[]"]').prop('checked', false);
            setPermissionsByIds(PERM_BOUTIQUE_IDS, true);
            syncToggleAllLabel();
        });

        $('#search_user').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        // Add / Update user
        $('#user_form').submit(function(e) {
            e.preventDefault();
            let id = $('#user_id').val();
            let user_name = $('#user_name').val().trim();
            let user_phone = $('#user_phone').val().trim();

            // Simple validation
            if (!user_name) {
                show_notification('error', '<?= trans("messages.enter_user_name_ar", [], session("locale")) ?>');
                return;
            }
        
            if (!user_phone) {
                show_notification('error', '<?= trans("messages.enter_user_phone", [], session("locale")) ?>');
                return;
            }

            if ($('input[name="user_type"][value="user"]').is(':checked')) {
                var sid = $('#salon_staff_id').val();
                if (!sid) {
                    show_notification('error', MSG_STAFF_REQUIRED);
                    return;
                }
            }

            let url = id ? `{{ url('users') }}/${id}` : "{{ url('users') }}";

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
                    $('#user_form')[0].reset();
                    $('#user_id').val('');
                    $('input[name="permissions[]"]').prop('checked', false);
                    $('input[name="user_scope"][value="boutique"]').prop('checked', true);
                    $('input[name="user_type"][value="admin"]').prop('checked', true);
                    $('#salon_staff_id').val('');
                    refreshStaffDropdown(null, null);
                    syncToggleAllLabel();
                    loadusers();
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
        $('#close_modal, #close_modal_btn').click(function() {
            // Reset Alpine.js state using custom event
            window.dispatchEvent(new CustomEvent('close-modal'));
        });

        // Edit user
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');
            $.get("{{ url('users') }}/" + id, function(user) {
                $('#user_id').val(user.id);
                $('#user_name').val(user.user_name);
                $('#user_phone').val(user.user_phone);
                 $('#user_email').val(user.user_email);
                $('#user_password').val('');
                $('#notes').val(user.notes);

                $('input[name="permissions[]"]').prop('checked', false);

                if (user.permissions && Array.isArray(user.permissions)) {
                    user.permissions.forEach(function(permission) {
                        var permissionId = typeof permission === 'number' ? permission : permission;
                        $('#permission_' + permissionId).prop('checked', true);
                    });
                }

                var scope = (user.user_scope === 'saloon' || user.user_scope === 'boutique') ? user.user_scope : 'boutique';
                $('input[name="user_scope"][value="' + scope + '"]').prop('checked', true);

                var ut = (user.user_type === 'user') ? 'user' : 'admin';
                $('input[name="user_type"][value="' + ut + '"]').prop('checked', true);
                refreshStaffDropdown(String(user.id), user.salon_staff_id || null);

                syncToggleAllLabel();

                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete user
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).closest('tr').data('id');

            Swal.fire({
                title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
                text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonuser: '#3085d6',
                cancelButtonuser: '#d33',
                confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
                cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= url("users") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadusers(); // reload table
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