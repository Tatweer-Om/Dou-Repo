<script>
var currentBookingsPaginationUrl = window.location.href;

$(document).ready(function () {
    const salonAccounts = @json($accounts ?? []);

    $('#bookings-search-form').on('submit', function (e) {
        e.preventDefault();
        const raw = $('#bookings-search-q').val() || '';
        const q = $.trim(raw);
        const base = '{{ route("view_bookings") }}';
        const url = q === '' ? base : base + '?q=' + encodeURIComponent(q);
        currentBookingsPaginationUrl = url;
        fetchBookingsData(url);
    });

    $(document).on('click', '.dress-page-link', function (e) {
        e.preventDefault();
        currentBookingsPaginationUrl = $(this).attr('href');
        fetchBookingsData(currentBookingsPaginationUrl);
    });

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMoney(n) {
        const x = parseFloat(n);
        return (Number.isFinite(x) ? x : 0).toFixed(3);
    }

    $(document).on('click', '.view-services-btn', function () {
        const id = $(this).closest('tr').data('id');
        if (!id) {
            return;
        }
        $.get('{{ url("saloon-bookings") }}/' + id, function (res) {
            const rows = (res.services || []).map(function (s) {
                return '<tr><td class="px-3 py-2 border-b">' + escapeHtml(s.name) + '</td><td class="px-3 py-2 border-b text-right">' + formatMoney(s.price) + '</td></tr>';
            }).join('');
            const table = '<table class="w-full text-sm text-left"><thead><tr class="bg-gray-100"><th class="px-3 py-2"><?= e(trans('messages.view_bookings_service_name', [], session('locale'))) ?></th><th class="px-3 py-2 text-right"><?= e(trans('messages.view_bookings_service_price', [], session('locale'))) ?></th></tr></thead><tbody>' + (rows || '<tr><td colspan="2" class="px-3 py-2"><?= e(trans('messages.view_bookings_empty', [], session('locale'))) ?></td></tr>') + '</tbody></table>';
            Swal.fire({
                title: '<?= e(trans('messages.view_bookings_services', [], session('locale'))) ?>',
                html: table,
                width: '520px',
                showCloseButton: true,
                confirmButtonText: '<?= e(trans('messages.close', [], session('locale'))) ?>'
            });
        }).fail(function () {
            show_notification('error', '<?= e(trans('messages.fetch_error', [], session('locale'))) ?>');
        });
    });

    $(document).on('click', '.view-payments-btn', function () {
        const id = $(this).closest('tr').data('id');
        if (!id) {
            return;
        }
        $.get('{{ url("saloon-bookings") }}/' + id, function (res) {
            const payments = (res.payments || []).slice().sort(function (a, b) {
                const ta = new Date(a.payment_at || 0).getTime();
                const tb = new Date(b.payment_at || 0).getTime();
                return ta - tb;
            });
            const totalAmount = parseFloat((res.booking && res.booking.total_services_amount) || 0) || 0;
            let runningPaid = 0;
            let rows = '';
            payments.forEach(function (p) {
                const acc = p.account ? (p.account.account_name || '') : '';
                const amount = parseFloat(p.amount || 0) || 0;
                runningPaid += amount;
                const remaining = Math.max(totalAmount - runningPaid, 0);
                rows += '<tr>'
                    + '<td class="px-3 py-2 border-b text-xs">' + escapeHtml(p.payment_at || '') + '</td>'
                    + '<td class="px-3 py-2 border-b">' + escapeHtml(p.payment_method) + '</td>'
                    + '<td class="px-3 py-2 border-b">' + escapeHtml(acc) + '</td>'
                    + '<td class="px-3 py-2 border-b text-right">' + formatMoney(amount) + '</td>'
                    + '<td class="px-3 py-2 border-b text-right">' + formatMoney(totalAmount) + '</td>'
                    + '<td class="px-3 py-2 border-b text-right">' + formatMoney(remaining) + '</td>'
                    + '<td class="px-3 py-2 border-b text-xs">' + escapeHtml(p.added_by || '') + '</td>'
                    + '</tr>';
            });
            if (!rows) {
                rows = '<tr><td colspan="7" class="px-3 py-2"><?= e(trans('messages.view_bookings_no_payments', [], session('locale'))) ?></td></tr>';
            }
            const table = '<table class="w-full text-sm text-left"><thead><tr class="bg-gray-100">'
                + '<th class="px-3 py-2"><?= e(trans('messages.view_bookings_payment_at', [], session('locale'))) ?></th>'
                + '<th class="px-3 py-2"><?= e(trans('messages.view_bookings_payment_method', [], session('locale'))) ?></th>'
                + '<th class="px-3 py-2"><?= e(trans('messages.view_bookings_payment_account', [], session('locale'))) ?></th>'
                + '<th class="px-3 py-2 text-right"><?= e(trans('messages.view_bookings_payment_amount', [], session('locale'))) ?></th>'
                + '<th class="px-3 py-2 text-right">Total</th>'
                + '<th class="px-3 py-2 text-right">Remaining</th>'
                + '<th class="px-3 py-2">Added By</th>'
                + '</tr></thead><tbody>' + rows + '</tbody></table>';
            Swal.fire({
                title: '<?= e(trans('messages.view_bookings_payments', [], session('locale'))) ?>',
                html: table,
                width: '640px',
                showCloseButton: true,
                confirmButtonText: '<?= e(trans('messages.close', [], session('locale'))) ?>'
            });
        }).fail(function () {
            show_notification('error', '<?= e(trans('messages.fetch_error', [], session('locale'))) ?>');
        });
    });

    function accountOptionsHtml() {
        let html = '<option value="">Select account</option>';
        (salonAccounts || []).forEach(function (a) {
            const accountId = a.id || '';
            const accountName = a.name || a.account_name || '';
            const accountNo = a.no || a.account_no || '';
            const label = accountName + (accountNo ? (' - ' + accountNo) : '');
            html += '<option value="' + escapeHtml(accountId) + '">' + escapeHtml(label) + '</option>';
        });
        return html;
    }

    $(document).on('click', '.receive-payment-btn', function () {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const remaining = parseFloat($(this).data('remaining') || 0) || 0;
        const total = parseFloat($(this).data('total') || 0) || 0;
        if (!id) {
            return;
        }
        if (remaining <= 0) {
            show_notification('error', 'No remaining amount.');
            return;
        }

        const popupHtml =
            '<div class="text-left space-y-3">'
            + '<div class="text-xs text-gray-600">Total: <b>' + formatMoney(total) + '</b> | Remaining: <b>' + formatMoney(remaining) + '</b></div>'
            + '<div><label class="block text-xs font-semibold mb-1">Account</label><select id="receivePaymentAccount" class="swal2-input" style="margin:0;height:42px;">' + accountOptionsHtml() + '</select></div>'
            + '<div><label class="block text-xs font-semibold mb-1">Amount</label><input id="receivePaymentAmount" type="number" step="0.001" min="0" class="swal2-input" style="margin:0;height:42px;" /></div>'
            + '<div><label class="block text-xs font-semibold mb-1">Notes (optional)</label><textarea id="receivePaymentNotes" class="swal2-textarea" style="margin:0;height:84px;"></textarea></div>'
            + '</div>';

        Swal.fire({
            title: 'Receive Payment',
            html: popupHtml,
            width: '560px',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Save Payment',
            cancelButtonText: '<?= e(trans('messages.cancel', [], session('locale'))) ?>',
            preConfirm: function () {
                const accountId = $('#receivePaymentAccount').val();
                const amount = parseFloat($('#receivePaymentAmount').val() || 0);
                if (!accountId) {
                    Swal.showValidationMessage('Please select account');
                    return false;
                }
                if (!Number.isFinite(amount) || amount <= 0) {
                    Swal.showValidationMessage('Amount must be greater than 0');
                    return false;
                }
                if (amount > remaining) {
                    Swal.showValidationMessage('Amount cannot be greater than remaining');
                    return false;
                }
                return {
                    account_id: parseInt(accountId, 10),
                    amount: parseFloat(amount.toFixed(3)),
                    notes: ($('#receivePaymentNotes').val() || '').trim() || null
                };
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) {
                return;
            }
            $.ajax({
                url: '{{ url("saloon-bookings") }}/' + id + '/payment',
                method: 'POST',
                data: {
                    _token: '<?= csrf_token() ?>',
                    account_id: result.value.account_id,
                    amount: result.value.amount,
                    notes: result.value.notes
                },
                success: function (data) {
                    show_notification('success', data.message || 'Payment saved successfully.');
                    fetchBookingsData(currentBookingsPaginationUrl);
                },
                error: function (xhr) {
                    let msg = '<?= e(trans('messages.generic_error', [], session('locale'))) ?>';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors && Object.keys(xhr.responseJSON.errors).length) {
                            msg = xhr.responseJSON.errors[Object.keys(xhr.responseJSON.errors)[0]][0];
                        } else if (xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                    }
                    show_notification('error', msg);
                }
            });
        });
    });

    $(document).on('click', '.approve-booking-btn', function () {
        const id = $(this).closest('tr').data('id');
        if (!id) {
            return;
        }
        Swal.fire({
            title: '<?= e(trans('messages.view_bookings_approve_confirm_title', [], session('locale'))) ?>',
            text: '<?= e(trans('messages.view_bookings_approve_confirm_text', [], session('locale'))) ?>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= e(trans('messages.view_bookings_approve', [], session('locale'))) ?>',
            cancelButtonText: '<?= e(trans('messages.cancel', [], session('locale'))) ?>'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: '{{ url("saloon-bookings") }}/' + id + '/approve',
                method: 'POST',
                data: { _token: '<?= csrf_token() ?>' },
                success: function (data) {
                    fetchBookingsData(currentBookingsPaginationUrl);
                    Swal.fire('<?= e(trans('messages.success', [], session('locale'))) ?>', data.message || '<?= e(trans('messages.view_bookings_approved_ok', [], session('locale'))) ?>', 'success');
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '<?= e(trans('messages.generic_error', [], session('locale'))) ?>';
                    Swal.fire('<?= e(trans('messages.error', [], session('locale'))) ?>', msg, 'error');
                }
            });
        });
    });

    $(document).on('click', '.delete-booking-btn', function () {
        const id = $(this).closest('tr').data('id');
        if (!id) {
            return;
        }
        Swal.fire({
            title: '<?= e(trans('messages.view_bookings_delete_confirm_title', [], session('locale'))) ?>',
            text: '<?= e(trans('messages.view_bookings_delete_confirm_text', [], session('locale'))) ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<?= e(trans('messages.yes_delete', [], session('locale'))) ?>',
            cancelButtonText: '<?= e(trans('messages.cancel', [], session('locale'))) ?>'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: '{{ url("saloon-bookings") }}/' + id,
                method: 'DELETE',
                data: { _token: '<?= csrf_token() ?>' },
                success: function (data) {
                    fetchBookingsData(currentBookingsPaginationUrl);
                    Swal.fire('<?= e(trans('messages.success', [], session('locale'))) ?>', data.message || '<?= e(trans('messages.view_bookings_deleted_ok', [], session('locale'))) ?>', 'success');
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '<?= e(trans('messages.generic_error', [], session('locale'))) ?>';
                    Swal.fire('<?= e(trans('messages.error', [], session('locale'))) ?>', msg, 'error');
                }
            });
        });
    });
});

function fetchBookingsData(url) {
    $.ajax({
        url: url,
        type: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataType: 'html',
        success: function (data) {
            var newTable = $(data).find('#data-table').html();
            var newPagination = $(data).find('#data-pagination').html();
            $('#data-table').html(newTable);
            $('#data-pagination').html(newPagination);
            window.history.pushState({}, '', url);
        },
        error: function () {
            show_notification('error', '<?= e(trans('messages.generic_error', [], session('locale'))) ?>');
        }
    });
}
</script>
