<script>
(function () {
    var reportBaseUrl = @json(route('saloon_monthly_income_report'));
    var exportBaseUrl = @json(route('saloon_monthly_income_report.export_excel'));
    var bookingShowUrl = @json(url('saloon-bookings'));
    var i18n = {
        amount: @json(trans('messages.mir_legend_amount', [], session('locale'))),
        paid: @json(trans('messages.mir_legend_paid', [], session('locale'))),
        remaining: @json(trans('messages.mir_legend_remaining', [], session('locale'))),
        bookings: @json(trans('messages.mir_bookings_label', [], session('locale'))),
        omr: @json(trans('messages.saloon_booking_currency_omr', [], session('locale'))),
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
        day: @json(trans('messages.mir_day_label', [], session('locale'))),
    };

    var initialChartData = @json($chartData);
    var mirChart = null;

    function escHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function formatMoney(n) {
        var x = parseFloat(n);
        return (Number.isFinite(x) ? x : 0).toFixed(3);
    }

    function initChart(data) {
        var ctx = document.getElementById('mirChart');
        if (!ctx) return;

        if (mirChart) {
            mirChart.destroy();
        }

        mirChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: i18n.amount,
                        data: data.amount,
                        backgroundColor: 'rgba(138, 72, 83, 0.85)',
                        borderColor: '#8a4853',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 3,
                    },
                    {
                        label: i18n.paid,
                        data: data.paid,
                        backgroundColor: 'rgba(5, 150, 105, 0.80)',
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        label: i18n.remaining,
                        data: data.remaining,
                        backgroundColor: 'rgba(220, 38, 38, 0.75)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: 'rgba(26,28,28,0.95)',
                        titleFont: { family: 'Manrope', weight: '800', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            title: function (items) {
                                if (!items.length) return '';
                                return i18n.day + ' ' + items[0].label;
                            },
                            label: function (context) {
                                var val = context.parsed.y;
                                return ' ' + context.dataset.label + ': ' + formatMoney(val) + ' ' + i18n.omr;
                            },
                            afterBody: function (items) {
                                if (!items.length) return '';
                                var idx = items[0].dataIndex;
                                var bkCount = data.bookings[idx] || 0;
                                return '\n' + i18n.bookings + ': ' + bkCount;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 10, weight: '600' },
                            color: '#524345',
                        },
                        title: {
                            display: true,
                            text: i18n.day,
                            font: { family: 'Manrope', size: 11, weight: '800' },
                            color: '#857374',
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(215,193,195,0.25)' },
                        ticks: {
                            font: { family: 'Inter', size: 10, weight: '600' },
                            color: '#524345',
                            callback: function (value) {
                                return formatMoney(value);
                            }
                        },
                        title: {
                            display: true,
                            text: i18n.amount + ' (' + i18n.omr + ')',
                            font: { family: 'Manrope', size: 11, weight: '800' },
                            color: '#857374',
                        }
                    }
                }
            }
        });
    }

    initChart(initialChartData);

    // Filter form handling
    var filterForm = document.getElementById('mir-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilters();
        });
    }

    var monthInput = document.getElementById('mir-month');
    var teamSelect = document.getElementById('mir-team');
    var weekendOnlyInput = document.getElementById('mir-weekend-only');

    function buildFilterUrl(extraPage) {
        var params = new URLSearchParams();
        if (monthInput && monthInput.value) params.set('month', monthInput.value);
        if (teamSelect && teamSelect.value) params.set('team', teamSelect.value);
        if (weekendOnlyInput && weekendOnlyInput.checked) params.set('weekend_only', '1');
        if (extraPage) params.set('page', extraPage);
        return reportBaseUrl + '?' + params.toString();
    }

    function refreshExportUrl() {
        var params = new URLSearchParams();
        if (monthInput && monthInput.value) params.set('month', monthInput.value);
        if (teamSelect && teamSelect.value) params.set('team', teamSelect.value);
        if (weekendOnlyInput && weekendOnlyInput.checked) params.set('weekend_only', '1');
        var exportBtn = document.getElementById('mir-export-excel-btn');
        if (exportBtn) {
            exportBtn.setAttribute('href', exportBaseUrl + '?' + params.toString());
        }
    }

    function applyFilters(pageNum) {
        var url = buildFilterUrl(pageNum || null);

        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function (res) {
                if (res.chartData) {
                    initialChartData = res.chartData;
                    initChart(res.chartData);
                }
                if (res.summaryTotals) {
                    var s = res.summaryTotals;
                    $('#mir-stat-bookings').text(s.bookings);
                    $('#mir-stat-amount').text(formatMoney(s.amount));
                    $('#mir-stat-paid').text(formatMoney(s.paid));
                    $('#mir-stat-remaining').text(formatMoney(s.remaining));
                    $('#mir-stat-weekend-income').text(formatMoney(s.weekend_income || s.paid || 0));
                }
                if (typeof res.weekendOnly !== 'undefined') {
                    $('#mir-weekend-income-card').toggleClass('hidden', !res.weekendOnly);
                }
                if (res.monthLabel) {
                    $('#mir-chart-month-label').text(res.monthLabel);
                }
                if (res.tableHtml) {
                    $('#mir-table-container').html(res.tableHtml);
                }
                refreshExportUrl();
                window.history.pushState({}, '', url);
            },
            error: function () {
                if (typeof toastr !== 'undefined') toastr.error(i18n.fetchError);
            }
        });
    }

    // Pagination
    $(document).on('click', '.mir-page-link', function (e) {
        e.preventDefault();
        var href = $(this).attr('href');
        if (!href) return;
        var urlObj = new URL(href, window.location.origin);
        var pageNum = urlObj.searchParams.get('page');
        applyFilters(pageNum);
    });

    if (weekendOnlyInput) {
        weekendOnlyInput.addEventListener('change', function () {
            refreshExportUrl();
        });
    }
    refreshExportUrl();

    // View services popup
    $(document).on('click', '.mir-view-services-btn', function () {
        var id = $(this).closest('tr').data('id');
        if (!id) return;
        $.get(bookingShowUrl + '/' + id, function (res) {
            var rows = (res.services || []).map(function (s) {
                return '<tr><td class="px-3 py-2 border-b">' + escHtml(s.name) + '</td><td class="px-3 py-2 border-b text-right">' + formatMoney(s.price) + '</td></tr>';
            }).join('');
            var table = '<table class="w-full text-sm text-left"><thead><tr class="bg-gray-100"><th class="px-3 py-2">' + escHtml(i18n.serviceName) + '</th><th class="px-3 py-2 text-right">' + escHtml(i18n.servicePrice) + '</th></tr></thead><tbody>' + (rows || '<tr><td colspan="2" class="px-3 py-2">' + escHtml(i18n.emptyLabel) + '</td></tr>') + '</tbody></table>';
            Swal.fire({ title: i18n.servicesTitle, html: table, width: '520px', showCloseButton: true, confirmButtonText: i18n.closeLabel });
        }).fail(function () {
            if (typeof show_notification === 'function') show_notification('error', i18n.fetchError);
        });
    });

    // View payments popup
    $(document).on('click', '.mir-view-payments-btn', function () {
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
            Swal.fire({ title: i18n.paymentsTitle, html: table, width: '640px', showCloseButton: true, confirmButtonText: i18n.closeLabel });
        }).fail(function () {
            if (typeof show_notification === 'function') show_notification('error', i18n.fetchError);
        });
    });
})();
</script>
