<script>
(function () {
    var spCurrentPageUrl = window.location.href;
    var staffId = {{ $staff->id }};
    var availRangeUrl = @json(route('salonstaff.profile.availability.range', $staff->id));
    var availDayUrl = @json(route('salonstaff.profile.availability.day', $staff->id));
    var bookingShowUrl = @json(url('saloon-bookings'));
    var appLocale = @json(session('locale', 'en'));
    var RANGE_DAYS = 31;
    var rangeAnchor = new Date();
    rangeAnchor.setHours(0, 0, 0, 0);

    var i18n = {
        omr: @json(trans('messages.saloon_booking_currency_omr', [], session('locale'))),
        slotFreeLabel: @json(trans('messages.booking_management_slot_free', [], session('locale'))),
        availLoadError: @json(trans('messages.saloon_booking_availability_load_error', [], session('locale'))),
        dayTitle: @json(trans('messages.saloon_booking_availability_day_title', [], session('locale'))),
        servicesTitle: @json(trans('messages.view_bookings_services', [], session('locale'))),
        paymentsTitle: @json(trans('messages.view_bookings_payments', [], session('locale'))),
        serviceName: @json(trans('messages.view_bookings_service_name', [], session('locale'))),
        servicePrice: @json(trans('messages.view_bookings_service_price', [], session('locale'))),
        paymentMethod: @json(trans('messages.view_bookings_payment_method', [], session('locale'))),
        paymentAccount: @json(trans('messages.view_bookings_payment_account', [], session('locale'))),
        paymentAmount: @json(trans('messages.view_bookings_payment_amount', [], session('locale'))),
        paymentAt: @json(trans('messages.view_bookings_payment_at', [], session('locale'))),
        emptyLabel: @json(trans('messages.view_bookings_empty', [], session('locale'))),
        noPayments: @json(trans('messages.view_bookings_no_payments', [], session('locale'))),
        closeLabel: @json(trans('messages.close', [], session('locale'))),
        fetchError: @json(trans('messages.fetch_error', [], session('locale'))),
        bookingNo: @json(trans('messages.view_bookings_booking_no', [], session('locale'))),
        customer: @json(trans('messages.view_bookings_customer', [], session('locale'))),
        time: @json(trans('messages.view_bookings_time', [], session('locale'))),
        status: @json(trans('messages.view_bookings_status', [], session('locale'))),
        totalLabel: @json(trans('messages.view_bookings_total', [], session('locale'))),
        paidLabel: @json(trans('messages.view_bookings_paid', [], session('locale'))),
        remainingLabel: @json(trans('messages.view_bookings_remaining', [], session('locale'))),
    };

    function escHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escAttr(s) {
        return escHtml(s);
    }

    function formatMoney(n) {
        var x = parseFloat(n);
        return (Number.isFinite(x) ? x : 0).toFixed(3);
    }

    function localYmd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function shiftRange(deltaDays) {
        rangeAnchor.setDate(rangeAnchor.getDate() + deltaDays);
    }

    function cellClass(level) {
        if (level === 'full') return 'bg-red-600 text-white border-2 border-red-900 shadow-md hover:bg-red-700';
        if (level === 'partial') return 'bg-amber-500 text-gray-900 border-2 border-amber-900 shadow-md hover:bg-amber-600';
        return 'bg-slate-300 text-slate-900 border-2 border-slate-600 shadow-sm hover:bg-slate-400';
    }

    function buildCalendar(data) {
        var dates = data.dates || [];
        var staff = data.staff || [];
        var loc = appLocale === 'ar' ? 'ar' : 'en-US';
        var todayStr = localYmd(new Date());
        var cols = dates.length;
        if (cols === 0) return '<p class="text-xs text-on-surface-variant py-4 text-center">No data</p>';

        var gridTemplate = '5.75rem repeat(' + cols + ', minmax(2rem, 1fr))';
        var html = '<div class="overflow-x-auto rounded-xl border-2 border-slate-400 bg-white p-2 sm:p-3 shadow-md">';
        html += '<div class="min-w-[720px]" style="display:grid;grid-template-columns:' + gridTemplate + ';gap:5px;align-items:stretch">';

        html += '<div class="text-[9px] font-extrabold text-slate-700 uppercase tracking-tight self-end pb-1 pr-1"></div>';
        dates.forEach(function (ds) {
            var parts = ds.split('-');
            var dt = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            var wd = dt.toLocaleDateString(loc, { weekday: 'short' });
            var dn = parseInt(parts[2], 10);
            var mon = dt.toLocaleDateString(loc, { month: 'short' });
            html += '<div class="text-center text-[8px] font-extrabold text-slate-800 leading-tight py-1 px-0.5 bg-slate-200 rounded-md border-2 border-slate-400">' + escAttr(wd) + '<br><span class="text-[11px] tabular-nums">' + dn + '</span><br><span class="text-[7px] opacity-90 uppercase">' + escAttr(mon) + '</span></div>';
        });

        staff.forEach(function (s) {
            var daysMap = s.days || {};
            html += '<div class="text-xs font-extrabold text-slate-900 truncate py-2 pr-1 border-r-2 border-slate-300 self-center" title="' + escAttr(s.name) + '">' + escAttr(s.name) + '</div>';
            dates.forEach(function (ds) {
                var info = daysMap[ds] || { level: 'free', booking_count: 0, booked_slot_count: 0, total_slots: 15 };
                var level = info.level || 'free';
                var cnt = info.booking_count || 0;
                var cls = cellClass(level);
                var todayRing = ds === todayStr ? ' ring-2 ring-violet-600 ring-offset-2' : '';
                var titleAttr = ds + ' · ' + cnt + ' bookings · ' + (info.booked_slot_count || 0) + '/' + (info.total_slots || 15) + ' h';
                html += '<button type="button" class="sp-avail-day-cell min-h-[2.85rem] rounded-md flex flex-col items-center justify-center p-0.5 leading-tight transition-transform active:scale-95 ' + cls + todayRing + '" data-date="' + ds + '" title="' + escAttr(titleAttr) + '">';
                html += '<span class="text-[11px] font-extrabold tabular-nums">' + parseInt(ds.split('-')[2], 10) + '</span>';
                if (cnt > 0) {
                    html += '<span class="text-[8px] font-extrabold leading-none mt-0.5">' + cnt + '</span>';
                }
                html += '</button>';
            });
        });

        html += '</div></div>';
        return html;
    }

    function fetchAvailRange() {
        var calEl = document.getElementById('spAvailCalendar');
        var labelEl = document.getElementById('spAvailLabel');
        if (!calEl) return;

        var params = new URLSearchParams();
        params.set('start', localYmd(rangeAnchor));
        params.set('days', String(RANGE_DAYS));

        fetch(availRangeUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (res) { if (!res.ok) throw new Error('bad'); return res.json(); })
        .then(function (data) {
            if (labelEl) labelEl.textContent = data.range_label || '';
            calEl.innerHTML = buildCalendar(data);
        })
        .catch(function () {
            if (typeof toastr !== 'undefined') toastr.error(i18n.availLoadError);
        });
    }

    var prevBtn = document.getElementById('spAvailPrev');
    var nextBtn = document.getElementById('spAvailNext');
    if (prevBtn) prevBtn.addEventListener('click', function () { shiftRange(-RANGE_DAYS); fetchAvailRange(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { shiftRange(RANGE_DAYS); fetchAvailRange(); });

    fetchAvailRange();

    // Day modal
    var modal = document.getElementById('spDayModal');
    var modalClose = document.getElementById('spDayModalClose');
    var modalTitle = document.getElementById('spDayModalTitle');
    var modalSub = document.getElementById('spDayModalSub');
    var timelineLabels = document.getElementById('spDayTimelineLabels');
    var timelineBar = document.getElementById('spDayTimelineBar');
    var slotsGrid = document.getElementById('spDaySlotsGrid');
    var bookingsTableEl = document.getElementById('spDayBookingsTable');

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
    }

    function openDayModal(dateStr) {
        if (!modal) return;
        var url = availDayUrl + '?date=' + encodeURIComponent(dateStr);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (res) { if (!res.ok) throw new Error('bad'); return res.json(); })
        .then(function (data) {
            modalTitle.textContent = data.staff.name;
            modalSub.textContent = data.date + ' · ' + i18n.dayTitle;

            // Timeline labels
            var labelsHtml = '<div class="relative h-full w-full" style="min-width:100%">';
            (data.hour_markers || []).forEach(function (h) {
                var st = h.anchor_end ? 'right:0;transform:translateX(-50%)' : 'left:' + h.left_pct + '%;transform:translateX(-50%)';
                labelsHtml += '<span class="absolute top-0 whitespace-nowrap text-[8px]" style="' + st + '">' + escAttr(h.label) + '</span>';
            });
            labelsHtml += '</div>';
            timelineLabels.innerHTML = labelsHtml;

            // Timeline bar
            var barInner = '';
            (data.segments || []).forEach(function (seg) {
                var bg = seg.status === 'draft' ? '#c2410c' : '#5b21b6';
                var tip = escAttr(seg.booking_no + ' · ' + seg.time_label + ' · ' + seg.customer + ' | ' + seg.total_amount + ' OMR (Paid: ' + seg.paid + ', Rem: ' + seg.remaining + ')');
                var wPct = Math.max(parseFloat(seg.width_pct) || 0, 0.85);
                barInner += '<div class="absolute top-0.5 bottom-0.5 rounded shadow-md border-2 border-white text-[7px] text-white font-extrabold flex items-center justify-center px-0.5 overflow-hidden leading-none" style="left:' + seg.left_pct + '%;width:' + wPct + '%;min-width:6px;background:' + bg + '" title="' + tip + '">' + escAttr(seg.booking_no) + '</div>';
            });
            timelineBar.innerHTML = '<div class="relative w-full h-full min-w-[520px] bg-slate-200 rounded-lg border-2 border-slate-400">' + barInner + '</div>';

            // Day bookings mini-table
            var dayBookings = data.day_bookings || [];
            if (dayBookings.length > 0) {
                var tbl = '<table class="w-full text-sm text-left"><thead><tr class="bg-surface-container-low">';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary">' + escHtml(i18n.bookingNo) + '</th>';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary">' + escHtml(i18n.customer) + '</th>';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary">' + escHtml(i18n.time) + '</th>';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary text-right">' + escHtml(i18n.totalLabel) + '</th>';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary text-right">' + escHtml(i18n.paidLabel) + '</th>';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary text-right">' + escHtml(i18n.remainingLabel) + '</th>';
                tbl += '<th class="px-3 py-2.5 text-[10px] font-bold uppercase tracking-wider text-primary text-center">' + escHtml(i18n.status) + '</th>';
                tbl += '</tr></thead><tbody class="divide-y divide-surface">';
                dayBookings.forEach(function (bk) {
                    var statusBadge = bk.status === 'draft'
                        ? '<span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-amber-100 text-amber-900">Draft</span>'
                        : '<span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-emerald-100 text-emerald-900">Confirmed</span>';
                    tbl += '<tr class="hover:bg-surface-container-low">';
                    tbl += '<td class="px-3 py-2.5 text-xs font-semibold">' + escHtml(bk.booking_no) + '</td>';
                    tbl += '<td class="px-3 py-2.5 text-xs">' + escHtml(bk.customer) + '</td>';
                    tbl += '<td class="px-3 py-2.5 text-xs whitespace-nowrap">' + escHtml(bk.time_label) + '</td>';
                    tbl += '<td class="px-3 py-2.5 text-xs text-right font-medium">' + escHtml(bk.total_amount) + '</td>';
                    tbl += '<td class="px-3 py-2.5 text-xs text-right text-emerald-700">' + escHtml(bk.paid) + '</td>';
                    tbl += '<td class="px-3 py-2.5 text-xs text-right text-red-600">' + escHtml(bk.remaining) + '</td>';
                    tbl += '<td class="px-3 py-2.5 text-center">' + statusBadge + '</td>';
                    tbl += '</tr>';
                });
                tbl += '</tbody></table>';
                bookingsTableEl.innerHTML = tbl;
            } else {
                bookingsTableEl.innerHTML = '<p class="text-xs text-on-surface-variant py-4 text-center">' + escHtml(i18n.emptyLabel) + '</p>';
            }

            // Slots grid
            var slotsHtml = '';
            (data.slots || []).forEach(function (slot) {
                var booked = slot.booked;
                var border = booked ? 'border-2 border-violet-800' : 'border-2 border-emerald-700';
                var bg = booked ? 'bg-violet-200' : 'bg-emerald-100';
                var block = '<div class="font-extrabold text-[9px] text-slate-900 truncate">' + escAttr(slot.label) + '</div>';
                if (booked && slot.bookings && slot.bookings.length) {
                    slot.bookings.forEach(function (b) {
                        block += '<div class="mt-0.5 text-[8px] font-semibold text-violet-950 truncate">' + escAttr(b.booking_no + ' · ' + b.time_label + ' · ' + b.customer) + '</div>';
                        block += '<div class="text-[7px] text-slate-600 truncate">' + escAttr(b.total_amount + ' OMR | Paid: ' + b.paid + ' | Rem: ' + b.remaining) + '</div>';
                    });
                } else {
                    block += '<div class="text-[8px] text-emerald-800 font-extrabold mt-0.5">' + escAttr(i18n.slotFreeLabel) + '</div>';
                }
                slotsHtml += '<div class="rounded-md p-1.5 ' + border + ' ' + bg + '">' + block + '</div>';
            });
            slotsGrid.innerHTML = slotsHtml;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
        })
        .catch(function () {
            if (typeof toastr !== 'undefined') toastr.error(i18n.availLoadError);
        });
    }

    document.addEventListener('click', function (e) {
        var cell = e.target.closest('.sp-avail-day-cell');
        if (!cell) return;
        var ds = cell.getAttribute('data-date');
        if (ds) openDayModal(ds);
    });

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    // Pagination for bookings table
    $(document).on('click', '.sp-page-link', function (e) {
        e.preventDefault();
        spCurrentPageUrl = $(this).attr('href');
        fetchProfileData(spCurrentPageUrl);
    });

    function fetchProfileData(url) {
        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'html',
            success: function (data) {
                var newTable = $(data).find('#sp-data-table').html();
                var newPagination = $(data).find('#sp-data-pagination').html();
                $('#sp-data-table').html(newTable);
                $('#sp-data-pagination').html(newPagination);
                window.history.pushState({}, '', url);
            },
            error: function () {
                if (typeof show_notification === 'function') show_notification('error', i18n.fetchError);
            }
        });
    }

    // View services popup
    $(document).on('click', '.sp-view-services-btn', function () {
        var id = $(this).closest('tr').data('id');
        if (!id) return;
        $.get(bookingShowUrl + '/' + id, function (res) {
            var rows = (res.services || []).map(function (s) {
                return '<tr><td class="px-3 py-2 border-b">' + escHtml(s.name) + '</td><td class="px-3 py-2 border-b text-right">' + formatMoney(s.price) + '</td></tr>';
            }).join('');
            var table = '<table class="w-full text-sm text-left"><thead><tr class="bg-gray-100"><th class="px-3 py-2">' + escHtml(i18n.serviceName) + '</th><th class="px-3 py-2 text-right">' + escHtml(i18n.servicePrice) + '</th></tr></thead><tbody>' + (rows || '<tr><td colspan="2" class="px-3 py-2">' + escHtml(i18n.emptyLabel) + '</td></tr>') + '</tbody></table>';
            Swal.fire({
                title: i18n.servicesTitle,
                html: table,
                width: '520px',
                showCloseButton: true,
                confirmButtonText: i18n.closeLabel
            });
        }).fail(function () {
            if (typeof show_notification === 'function') show_notification('error', i18n.fetchError);
        });
    });

    // View payments popup
    $(document).on('click', '.sp-view-payments-btn', function () {
        var id = $(this).closest('tr').data('id');
        if (!id) return;
        $.get(bookingShowUrl + '/' + id, function (res) {
            var payments = res.payments || [];
            var rows = '';
            payments.forEach(function (p) {
                var acc = p.account ? (p.account.account_name || '') : '';
                rows += '<tr><td class="px-3 py-2 border-b">' + escHtml(p.payment_method) + '</td><td class="px-3 py-2 border-b">' + escHtml(acc) + '</td><td class="px-3 py-2 border-b text-right">' + formatMoney(p.amount) + '</td><td class="px-3 py-2 border-b text-xs">' + escHtml(p.payment_at || '') + '</td></tr>';
            });
            if (!rows) {
                rows = '<tr><td colspan="4" class="px-3 py-2">' + escHtml(i18n.noPayments) + '</td></tr>';
            }
            var table = '<table class="w-full text-sm text-left"><thead><tr class="bg-gray-100"><th class="px-3 py-2">' + escHtml(i18n.paymentMethod) + '</th><th class="px-3 py-2">' + escHtml(i18n.paymentAccount) + '</th><th class="px-3 py-2 text-right">' + escHtml(i18n.paymentAmount) + '</th><th class="px-3 py-2">' + escHtml(i18n.paymentAt) + '</th></tr></thead><tbody>' + rows + '</tbody></table>';
            Swal.fire({
                title: i18n.paymentsTitle,
                html: table,
                width: '640px',
                showCloseButton: true,
                confirmButtonText: i18n.closeLabel
            });
        }).fail(function () {
            if (typeof show_notification === 'function') show_notification('error', i18n.fetchError);
        });
    });
})();
</script>
