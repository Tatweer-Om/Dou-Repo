<script>
(function () {
    var chartJsonUrl = @json(route('saloon_dashboard.monthly_income_chart'));
    var i18n = {
        amount: @json(trans('messages.mir_legend_amount', [], session('locale'))),
        paid: @json(trans('messages.mir_legend_paid', [], session('locale'))),
        remaining: @json(trans('messages.mir_legend_remaining', [], session('locale'))),
        bookings: @json(trans('messages.mir_bookings_label', [], session('locale'))),
        omr: @json(trans('messages.saloon_booking_currency_omr', [], session('locale'))),
        fetchError: @json(trans('messages.fetch_error', [], session('locale'))),
        day: @json(trans('messages.mir_day_label', [], session('locale'))),
    };

    var initialChartData = @json($chartData);
    var dashMirChart = null;

    function formatMoney(n) {
        var x = parseFloat(n);
        return (Number.isFinite(x) ? x : 0).toFixed(3);
    }

    function setError(msg) {
        var el = document.getElementById('dash-mir-chart-error');
        if (!el) return;
        if (msg) {
            el.textContent = msg;
            el.classList.remove('hidden');
        } else {
            el.textContent = '';
            el.classList.add('hidden');
        }
    }

    function initChart(data) {
        var ctx = document.getElementById('dashMirChart');
        if (!ctx || typeof Chart === 'undefined') return;

        if (dashMirChart) {
            dashMirChart.destroy();
        }

        dashMirChart = new Chart(ctx, {
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
                    legend: { display: false },
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

    function buildQueryString() {
        var params = new URLSearchParams();
        var monthEl = document.getElementById('dash-mir-month');
        var teamEl = document.getElementById('dash-mir-team');
        var weekendEl = document.getElementById('dash-mir-weekend-only');
        if (monthEl && monthEl.value) params.set('mir_month', monthEl.value);
        if (teamEl && teamEl.value) params.set('mir_team', teamEl.value);
        if (weekendEl && weekendEl.checked) params.set('mir_weekend_only', '1');
        return params.toString();
    }

    function applyFilters() {
        setError('');
        var qs = buildQueryString();
        var url = chartJsonUrl + (qs ? '?' + qs : '');

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(function (r) {
                if (!r.ok) throw new Error('bad status');
                return r.json();
            })
            .then(function (res) {
                if (res.chartData) {
                    initialChartData = res.chartData;
                    initChart(res.chartData);
                }
                if (res.summaryTotals) {
                    var s = res.summaryTotals;
                    var b = document.getElementById('dash-mir-stat-bookings');
                    var a = document.getElementById('dash-mir-stat-amount');
                    var p = document.getElementById('dash-mir-stat-paid');
                    var rem = document.getElementById('dash-mir-stat-remaining');
                    var wk = document.getElementById('dash-mir-stat-weekend-income');
                    if (b) b.textContent = s.bookings;
                    if (a) a.textContent = formatMoney(s.amount);
                    if (p) p.textContent = formatMoney(s.paid);
                    if (rem) rem.textContent = formatMoney(s.remaining);
                    if (wk) wk.textContent = formatMoney(s.weekend_income || s.paid || 0);
                }
                if (typeof res.weekendOnly !== 'undefined') {
                    var card = document.getElementById('dash-mir-weekend-income-card');
                    if (card) {
                        if (res.weekendOnly) card.classList.remove('hidden');
                        else card.classList.add('hidden');
                    }
                }
                if (res.monthLabel) {
                    var lbl = document.getElementById('dash-mir-chart-month-label');
                    if (lbl) lbl.textContent = res.monthLabel;
                }
            })
            .catch(function () {
                setError(i18n.fetchError);
            });
    }

    var filterForm = document.getElementById('dash-mir-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilters();
        });
    }
})();
</script>
