<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SalonExpense;
use App\Models\SalonService;
use App\Models\SalonStaff;
use App\Models\SalonTeam;
use App\Models\SaloonBookingDetail;
use App\Models\SaloonBooking;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaloonController extends Controller
{
    /**
     * Chart filters use mir_* keys so they never clash with schedule ?date= & ?team= on the dashboard URL.
     */
    private function requestForDashboardMonthlyChart(Request $request): Request
    {
        return Request::create('/', 'GET', [
            'month' => $request->get('mir_month', now()->format('Y-m')),
            'team' => (string) $request->get('mir_team', ''),
            'weekend_only' => $request->boolean('mir_weekend_only') ? '1' : '',
        ]);
    }

    /**
     * Most-requested salon service: counts each line item in booking details (draft + confirmed).
     *
     * @return array{name: string, times: int, service_id: int|null}|null
     */
    private function computeTopSalonServiceFromBookings(): ?array
    {
        $rows = SaloonBookingDetail::query()
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['draft', 'confirmed']);
            })
            ->get(['services_json']);

        $tally = [];
        foreach ($rows as $row) {
            $services = $row->services_json;
            if (!is_array($services)) {
                continue;
            }
            foreach ($services as $item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $serviceId = !empty($item['service_id']) ? (int) $item['service_id'] : null;
                $key = $serviceId ? 'id:'.$serviceId : 'n:'.mb_strtolower($name);
                if (!isset($tally[$key])) {
                    $tally[$key] = ['name' => $name, 'times' => 0, 'service_id' => $serviceId];
                }
                $tally[$key]['times']++;
            }
        }

        if ($tally === []) {
            return null;
        }

        uasort($tally, fn ($a, $b) => $b['times'] <=> $a['times']);
        $top = reset($tally);

        return [
            'name' => (string) $top['name'],
            'times' => (int) $top['times'],
            'service_id' => $top['service_id'],
        ];
    }

    public function saloon_dashboard(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::DASHBOARD])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $bookingScope = fn () => SaloonBooking::query()
            ->whereIn('status', ['draft', 'confirmed']);

        $received = round((float) $bookingScope()->sum('total_paid'), 3);
        $pending = round((float) $bookingScope()->sum('total_remaining'), 3);
        $totalBookingsAmount = round((float) $bookingScope()->sum('total_services_amount'), 3);
        $expense = round((float) SalonExpense::query()->sum('amount'), 3);

        $summary = [
            'received_amount' => $received,
            'pending_amount' => $pending,
            'total_amount' => $totalBookingsAmount,
            'expense_amount' => $expense,
            'income_amount' => round($received - $expense, 3),
        ];

        $schedule = app(SaloonBookingController::class)->buildDaySchedulePayload($request);
        $monthlyChart = app(SaloonBookingController::class)
            ->buildMonthlyIncomeChartPayload($this->requestForDashboardMonthlyChart($request));

        $teamBookingStats = SalonTeam::query()
            ->active()
            ->ordered()
            ->withCount([
                'bookings as bookings_count' => function ($q) {
                    $q->whereIn('status', ['draft', 'confirmed']);
                },
            ])
            ->get();

        $topSalonService = $this->computeTopSalonServiceFromBookings();

        return view('saloon.dashboard', array_merge(
            compact('summary', 'monthlyChart', 'teamBookingStats', 'topSalonService'),
            $schedule
        ));
    }

    /**
     * JSON for dashboard monthly income chart (same data as saloon-monthly-income-report chart).
     */
    public function monthlyIncomeChartJson(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::DASHBOARD])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $payload = app(SaloonBookingController::class)
            ->buildMonthlyIncomeChartPayload($this->requestForDashboardMonthlyChart($request));

        return response()->json([
            'chartData' => $payload['chartData'],
            'summaryTotals' => $payload['summaryTotals'],
            'monthLabel' => $payload['monthLabel'],
            'weekendOnly' => $payload['weekendOnly'],
        ]);
    }

    public function bookings()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::BOOKINGS_LIST])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('saloon.bookings');
    }

    public function booking_page()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::BOOKING_PAGE])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $services = SalonService::orderBy('name')->get(['id', 'name', 'price']);
        $accounts = Account::orderBy('account_name')->get(['id', 'account_name', 'account_no', 'account_branch']);
        $staff = SalonStaff::orderBy('name')->get(['id', 'name']);

        return view('saloon.booking_page', compact('services', 'accounts', 'staff'));
    }
}
