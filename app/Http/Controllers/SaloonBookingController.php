<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SalonCustomer;
use App\Models\SalonStaff;
use App\Models\SalonService;
use App\Models\SalonTeam;
use App\Models\SalonExpense;
use App\Models\SaloonBooking;
use App\Models\SaloonBookingDetail;
use App\Models\SaloonBookingHistory;
use App\Models\SaloonBookingPayment;
use App\Support\SalonBookingAvailability;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaloonBookingController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::VIEW_BOOKINGS])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $search = trim((string) $request->get('q', ''));
        $like = $search !== '' ? '%' . addcslashes($search, '%_\\') . '%' : '';

        $bookings = SaloonBooking::query()
            ->with(['customer', 'staff', 'salonTeam'])
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($qry) use ($like) {
                    $qry->where('booking_no', 'like', $like)
                        ->orWhereHas('customer', function ($cq) use ($like) {
                            $cq->where('name', 'like', $like)
                                ->orWhere('phone', 'like', $like);
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $staffById = SaloonBooking::staffByIdMapForBookings($bookings);
        $accounts = Account::query()
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_no', 'account_branch']);

        if ($request->ajax()) {
            return view('saloon.view_bookings', compact('bookings', 'search', 'staffById', 'accounts'))->render();
        }

        return view('saloon.view_bookings', compact('bookings', 'search', 'staffById', 'accounts'));
    }

    /**
     * Day schedule data for booking management UI and dashboard (same queries & timeline).
     *
     * @return array{
     *     bookings: \Illuminate\Support\Collection,
     *     selectedDate: \Carbon\Carbon,
     *     dateStr: string,
     *     teamFilter: string,
     *     dayTotals: array{bookings:int,total:float,paid:float,remaining:float},
     *     unscheduled: \Illuminate\Support\Collection,
     *     staffById: mixed,
     *     staffTimeline: array,
     *     salonTeams: \Illuminate\Support\Collection
     * }
     */
    public function buildDaySchedulePayload(Request $request): array
    {
        $dateInput = $request->get('date', now()->format('Y-m-d'));
        try {
            $selectedDate = Carbon::parse($dateInput)->startOfDay();
        } catch (\Throwable $e) {
            $selectedDate = now()->startOfDay();
        }
        $dateStr = $selectedDate->format('Y-m-d');

        $salonTeams = SalonTeam::query()->active()->ordered()->get();
        $allowedTeamIds = $salonTeams->pluck('id')->map(fn ($id) => (string) $id)->all();

        $teamFilter = (string) $request->get('team', '');
        if ($teamFilter === 'all') {
            $teamFilter = '';
        }
        if ($teamFilter !== '' && !in_array($teamFilter, $allowedTeamIds, true)) {
            $teamFilter = '';
        }

        $query = SaloonBooking::query()
            ->with(['customer', 'staff', 'payments.account', 'detail', 'salonTeam'])
            ->whereDate('booking_date', $dateStr);

        if ($teamFilter !== '') {
            $query->where('team_id', (int) $teamFilter);
        }

        $bookings = $query
            ->orderByRaw('CASE WHEN COALESCE(booking_time_from, booking_time) IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('COALESCE(booking_time_from, booking_time)')
            ->orderBy('id')
            ->get();

        $dayTotals = [
            'bookings' => $bookings->count(),
            'total' => round((float) $bookings->sum('total_services_amount'), 3),
            'paid' => round((float) $bookings->sum('total_paid'), 3),
            'remaining' => round((float) $bookings->sum('total_remaining'), 3),
        ];

        $unscheduled = collect();
        foreach ($bookings as $booking) {
            $t = $booking->bookingScheduleStart();
            if ($t === null || $t === '') {
                $unscheduled->push($booking);
            }
        }

        $staffById = SaloonBooking::staffByIdMapForBookings($bookings);
        $staffTimeline = $this->buildStaffTimelineData($bookings, $dateStr, $teamFilter, session('locale'));

        return compact(
            'bookings',
            'selectedDate',
            'dateStr',
            'teamFilter',
            'dayTotals',
            'unscheduled',
            'staffById',
            'staffTimeline',
            'salonTeams'
        );
    }

    public function bookingManagement(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::BOOKING_MANAGEMENT])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('saloon.booking_management', $this->buildDaySchedulePayload($request));
    }

    public function show(SaloonBooking $booking)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::VIEW_BOOKINGS, SalonPermission::BOOKING_MANAGEMENT])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $booking->load(['customer', 'staff', 'payments.account']);
        $detail = $booking->detail()->first();
        $services = $detail && is_array($detail->services_json) ? $detail->services_json : [];

        $staffIdList = $booking->staffIdList();
        $staffMembers = collect();
        if ($staffIdList !== []) {
            $byId = SalonStaff::query()->whereIn('id', $staffIdList)->get()->keyBy('id');
            foreach ($staffIdList as $sid) {
                if ($byId->has($sid)) {
                    $staffMembers->push($byId->get($sid));
                }
            }
        }

        return response()->json([
            'booking' => $booking,
            'services' => $services,
            'payments' => $booking->payments,
            'staff_members' => $staffMembers->values(),
        ]);
    }

    public function approve(Request $request, SaloonBooking $booking)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::VIEW_BOOKINGS, SalonPermission::BOOKING_MANAGEMENT])) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        if ($booking->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft bookings can be approved.',
            ], 422);
        }

        $user = Auth::user();
        $userId = Auth::id();
        $userName = $user->user_name ?? $user->name ?? 'system';

        DB::transaction(function () use ($booking, $userId, $userName) {
            $before = $booking->toArray();
            $booking->status = 'confirmed';
            $booking->updated_by = $userName;
            $booking->save();

            SaloonBookingHistory::create([
                'saloon_booking_id' => $booking->id,
                'action_type' => 'approved',
                'snapshot' => [
                    'before' => $before,
                    'after' => $booking->fresh()->toArray(),
                ],
                'action_at' => now(),
                'action_by' => $userName,
                'user_id' => $userId,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking approved.',
            'booking' => $booking->fresh(['customer', 'staff', 'salonTeam']),
        ]);
    }

    public function destroy(SaloonBooking $booking)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::VIEW_BOOKINGS, SalonPermission::BOOKING_MANAGEMENT])) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        if ($booking->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft bookings can be deleted.',
            ], 422);
        }

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted.',
        ]);
    }

    public function addPayment(Request $request, SaloonBooking $booking)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::VIEW_BOOKINGS, SalonPermission::BOOKING_MANAGEMENT])) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'amount' => 'required|numeric|gt:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $userId = Auth::id();
        $userName = $user->user_name ?? $user->name ?? 'system';

        $updatedBooking = DB::transaction(function () use ($booking, $validated, $userId, $userName) {
            $locked = SaloonBooking::query()->lockForUpdate()->findOrFail($booking->id);

            $remainingBefore = round((float) $locked->total_remaining, 3);
            $amount = round((float) $validated['amount'], 3);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount must be greater than zero.'],
                ]);
            }
            if ($amount > $remainingBefore) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount cannot be greater than remaining amount.'],
                ]);
            }

            $account = Account::query()->findOrFail((int) $validated['account_id']);
            $paidBefore = round((float) $locked->total_paid, 3);
            $paidAfter = round($paidBefore + $amount, 3);
            $remainingAfter = round(max((float) $locked->total_services_amount - $paidAfter, 0), 3);

            SaloonBookingPayment::create([
                'saloon_booking_id' => $locked->id,
                'account_id' => (int) $validated['account_id'],
                'payment_method' => (string) $account->account_name,
                'amount' => $amount,
                'payment_at' => now(),
                'reference_no' => null,
                'notes' => $validated['notes'] ?? null,
                'added_by' => $userName,
                'user_id' => $userId,
            ]);

            $locked->total_paid = $paidAfter;
            $locked->total_remaining = $remainingAfter;
            $locked->updated_by = $userName;
            $locked->save();

            SaloonBookingHistory::create([
                'saloon_booking_id' => $locked->id,
                'action_type' => 'payment_added',
                'snapshot' => [
                    'total' => round((float) $locked->total_services_amount, 3),
                    'paid_before' => $paidBefore,
                    'paid_added' => $amount,
                    'paid_after' => $paidAfter,
                    'remaining_before' => $remainingBefore,
                    'remaining_after' => $remainingAfter,
                    'account_id' => (int) $validated['account_id'],
                    'account_name' => (string) $account->account_name,
                    'notes' => $validated['notes'] ?? null,
                ],
                'action_at' => now(),
                'action_by' => $userName,
                'user_id' => $userId,
            ]);

            return $locked->fresh(['payments.account']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment saved successfully.',
            'booking' => $updatedBooking,
        ]);
    }

    public function searchCustomers(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [
            SalonPermission::BOOKING_PAGE,
            SalonPermission::VIEW_BOOKINGS,
            SalonPermission::BOOKING_MANAGEMENT,
        ])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $q = trim((string) $request->get('q', ''));

        $customers = SalonCustomer::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%');
            })
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [
            SalonPermission::BOOKING_PAGE,
            SalonPermission::VIEW_BOOKINGS,
            SalonPermission::BOOKING_MANAGEMENT,
        ])) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:salon_customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'staff_id' => 'nullable|integer|exists:salon_staff,id',
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:salon_staff,id',
            'booking_date' => 'required|date',
            'booking_time_from' => 'required|date_format:H:i',
            'booking_time_to' => 'required|date_format:H:i',
            'special_notes' => 'nullable|string',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'nullable|integer|exists:salon_services,id',
            'services.*.name' => 'required|string|max:255',
            'services.*.price' => 'required|numeric|min:0',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'payment_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:draft,confirmed',
        ]);

        $status = $validated['status'] ?? 'confirmed';
        if (!in_array($status, ['draft', 'confirmed'], true)) {
            $status = 'confirmed';
        }

        $paidRoundedPre = round((float) ($validated['payment_amount'] ?? 0), 3);

        if ($paidRoundedPre > 0 && empty($validated['account_id'])) {
            throw ValidationException::withMessages([
                'account_id' => 'Select an account for this payment.',
            ]);
        }

        if ($status === 'confirmed') {
            if (!empty($validated['account_id']) && $paidRoundedPre <= 0) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'Enter an amount when an account is selected.',
                ]);
            }
        }

        $services = $this->normalizeServices($validated['services']);
        $seenServiceIds = [];
        $seenServiceNames = [];
        foreach ($services as $service) {
            $sid = isset($service['service_id']) ? (int) $service['service_id'] : 0;
            $nameKey = mb_strtolower(trim((string) ($service['name'] ?? '')));
            if ($sid > 0) {
                if (isset($seenServiceIds[$sid])) {
                    throw ValidationException::withMessages([
                        'services' => ['The same service cannot be selected twice.'],
                    ]);
                }
                $seenServiceIds[$sid] = true;
            } elseif ($nameKey !== '') {
                if (isset($seenServiceNames[$nameKey])) {
                    throw ValidationException::withMessages([
                        'services' => ['The same service cannot be selected twice.'],
                    ]);
                }
                $seenServiceNames[$nameKey] = true;
            }
        }
        $servicesTotal = round((float) collect($services)->sum('price'), 3);
        $paidRounded = round((float) ($validated['payment_amount'] ?? 0), 3);

        if ($paidRounded > $servicesTotal) {
            throw ValidationException::withMessages([
                'payment_amount' => 'Paid amount cannot be greater than the total amount.',
            ]);
        }

        $timeFrom = $validated['booking_time_from'] ?? null;
        $timeTo = $validated['booking_time_to'] ?? null;
        if ($timeFrom !== null && $timeFrom !== '' && $timeTo !== null && $timeTo !== '') {
            $start = Carbon::parse('2000-01-01 '.$timeFrom);
            $end = Carbon::parse('2000-01-01 '.$timeTo);
            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'booking_time_to' => [trans('messages.saloon_booking_err_time_order')],
                ]);
            }
        }

        $user = Auth::user();
        $userId = Auth::id();
        $userName = $user->user_name ?? $user->name ?? 'system';

        $staffIds = collect($validated['staff_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        if ($staffIds->isEmpty() && !empty($validated['staff_id'])) {
            $staffIds = collect([(int) $validated['staff_id']]);
        }
        $firstStaffId = $staffIds->first();

        $teamFromStaffId = null;
        if ($firstStaffId) {
            $staffMember = SalonStaff::query()->find($firstStaffId);
            if ($staffMember && $staffMember->team_id) {
                $teamFromStaffId = (int) $staffMember->team_id;
            }
        }

        $bookingDateStr = null;
        if (!empty($validated['booking_date'])) {
            try {
                $bookingDateStr = Carbon::parse($validated['booking_date'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $bookingDateStr = null;
            }
        }
        $timeFromHm = $timeFrom ? SaloonBooking::formatTimeHm($timeFrom) : null;
        $timeToHm = $timeTo ? SaloonBooking::formatTimeHm($timeTo) : null;
        if ($staffIds->isNotEmpty() && $bookingDateStr && $timeFromHm && $timeToHm) {
            $slotCheck = SalonBookingAvailability::evaluateSlotRequest(
                $staffIds->all(),
                $bookingDateStr,
                $timeFromHm,
                $timeToHm
            );
            if (!$slotCheck['available']) {
                throw ValidationException::withMessages([
                    'booking_slot' => [$this->formatSlotConflictForUser($slotCheck)],
                ]);
            }
        }

        $result = DB::transaction(function () use ($validated, $userId, $userName, $services, $servicesTotal, $status, $teamFromStaffId, $staffIds, $firstStaffId, $timeFrom, $timeTo) {
            $customer = $this->resolveCustomer($validated, $userId, $userName);
            $paymentsInput = [];
            $paymentsTotal = 0;
            $accountIdForPayment = !empty($validated['account_id']) && round((float) ($validated['payment_amount'] ?? 0), 3) > 0
                ? (int) $validated['account_id']
                : null;
            if ($accountIdForPayment) {
                $account = Account::find($accountIdForPayment);
                $amount = round((float) $validated['payment_amount'], 3);
                $paymentsTotal = $amount;
                $paymentsInput[] = [
                    'account_id' => $accountIdForPayment,
                    'payment_method' => $account ? (string) $account->account_name : 'account',
                    'amount' => $amount,
                    'payment_at' => now()->format('Y-m-d H:i:s'),
                ];
            }

            $booking = SaloonBooking::create([
                'booking_no' => 'TEMP',
                'customer_id' => $customer->id,
                'team_id' => $teamFromStaffId,
                'staff_id' => $firstStaffId ?: null,
                'staff_ids' => $staffIds->isEmpty() ? null : $staffIds->all(),
                'booking_date' => $validated['booking_date'] ?? null,
                'booking_time_from' => $timeFrom,
                'booking_time_to' => $timeTo,
                'booking_time' => $timeFrom,
                'total_services' => count($services),
                'total_services_amount' => $servicesTotal,
                'total_paid' => $paymentsTotal,
                'total_remaining' => max($servicesTotal - $paymentsTotal, 0),
                'status' => $status,
                'special_notes' => $validated['special_notes'] ?? null,
                'added_by' => $userName,
                'user_id' => $userId,
            ]);

            $booking->booking_no = $this->generateBookingNo($booking->id);
            $booking->save();

            $remainingComputed = max($servicesTotal - $paymentsTotal, 0);
            $paymentSummary = [
                'total_services_amount' => round((float) $servicesTotal, 3),
                'paid_amount' => round((float) $paymentsTotal, 3),
                'remaining_amount' => round((float) $remainingComputed, 3),
                'account_id' => $accountIdForPayment,
                'client_total_amount' => isset($validated['total_amount']) ? round((float) $validated['total_amount'], 3) : null,
                'client_remaining_amount' => isset($validated['remaining_amount']) ? round((float) $validated['remaining_amount'], 3) : null,
            ];

            SaloonBookingDetail::create([
                'saloon_booking_id' => $booking->id,
                'services_json' => $services,
                'services_count' => count($services),
                'services_total_amount' => $servicesTotal,
            ]);

            foreach ($paymentsInput as $payment) {
                SaloonBookingPayment::create([
                    'saloon_booking_id' => $booking->id,
                    'account_id' => $payment['account_id'] ?? null,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'payment_at' => !empty($payment['payment_at']) ? Carbon::parse($payment['payment_at']) : now(),
                    'reference_no' => null,
                    'notes' => null,
                    'added_by' => $userName,
                    'user_id' => $userId,
                ]);
            }

            SaloonBookingHistory::create([
                'saloon_booking_id' => $booking->id,
                'action_type' => $status === 'draft' ? 'draft_created' : 'created',
                'snapshot' => [
                    'booking' => $booking->fresh()->toArray(),
                    'services' => $services,
                    'payments' => $paymentsInput,
                    'payment_summary' => $paymentSummary,
                ],
                'action_at' => now(),
                'action_by' => $userName,
                'user_id' => $userId,
            ]);

            return $booking->fresh(['customer', 'staff', 'salonTeam', 'detail', 'payments.account']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking saved successfully.',
            'booking' => $result,
        ]);
    }

    private function resolveCustomer(array $validated, ?int $userId, string $userName): SalonCustomer
    {
        if (!empty($validated['customer_id'])) {
            $customer = SalonCustomer::find($validated['customer_id']);
            if (!$customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Selected customer does not exist.',
                ]);
            }
            return $customer;
        }

        $phone = trim((string) $validated['customer_phone']);
        $name = trim((string) $validated['customer_name']);

        $existing = SalonCustomer::where('phone', $phone)->first();
        if ($existing) {
            if (empty($existing->name) && $name !== '') {
                $existing->name = $name;
                $existing->updated_by = $userName;
                $existing->save();
            }
            return $existing;
        }

        return SalonCustomer::create([
            'name' => $name,
            'phone' => $phone,
            'added_by' => $userName,
            'user_id' => $userId,
        ]);
    }

    private function normalizeServices(array $services): array
    {
        $serviceIds = collect($services)
            ->pluck('service_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $serviceMap = $serviceIds->isEmpty()
            ? collect()
            : SalonService::whereIn('id', $serviceIds)->get()->keyBy('id');

        return collect($services)->map(function ($service) use ($serviceMap) {
            $serviceId = !empty($service['service_id']) ? (int) $service['service_id'] : null;
            $dbService = $serviceId ? $serviceMap->get($serviceId) : null;
            $price = $dbService ? (float) $dbService->price : (float) $service['price'];
            $name = $dbService ? (string) $dbService->name : (string) $service['name'];

            return [
                'service_id' => $serviceId,
                'name' => $name,
                'price' => round($price, 3),
            ];
        })->values()->all();
    }

    private function generateBookingNo(int $id): string
    {
        return 'Bk-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Geometry + metadata for one booking on the 07:00–22:00 timeline, or null if no / out of range.
     */
    private function bookingTimelineSegment(SaloonBooking $booking, string $dateStr): ?array
    {
        $from = $booking->booking_time_from ?? $booking->booking_time;
        if ($from === null || $from === '') {
            return null;
        }
        $fromHm = SaloonBooking::formatTimeHm($from);
        if ($fromHm === null || $fromHm === '') {
            return null;
        }

        $start = Carbon::parse($dateStr.' '.$fromHm.':00');
        $toRaw = $booking->booking_time_to;
        if ($toRaw !== null && $toRaw !== '') {
            $toHm = SaloonBooking::formatTimeHm($toRaw);
            $end = $toHm ? Carbon::parse($dateStr.' '.$toHm.':00') : $start->copy()->addMinutes(60);
        } else {
            $end = $start->copy()->addMinutes(60);
        }
        if ($end->lte($start)) {
            $end = $start->copy()->addMinutes(60);
        }

        $timelineStart = Carbon::parse($dateStr.' 07:00:00');
        $timelineEnd = Carbon::parse($dateStr.' 22:00:00');
        if ($end->lte($timelineStart) || $start->gte($timelineEnd)) {
            return null;
        }

        $segStart = $start->greaterThan($timelineStart) ? $start->copy() : $timelineStart->copy();
        $segEnd = $end->lessThan($timelineEnd) ? $end->copy() : $timelineEnd->copy();
        if ($segEnd->lte($segStart)) {
            return null;
        }

        $totalMin = $timelineStart->diffInMinutes($timelineEnd);
        if ($totalMin <= 0) {
            return null;
        }
        $leftMin = $timelineStart->diffInMinutes($segStart);
        $durMin = $segStart->diffInMinutes($segEnd);

        return [
            'left_pct' => round(($leftMin / $totalMin) * 100, 4),
            'width_pct' => max(0.5, round(($durMin / $totalMin) * 100, 4)),
            'booking_no' => $booking->booking_no,
            'time_label' => $booking->bookingTimeRangeDisplay(),
            'customer' => $booking->customer->name ?? '—',
            'status' => $booking->status,
            'booking_id' => $booking->id,
        ];
    }

    /**
     * Staff rows grouped by team + timeline segments (07:00–22:00).
     */
    private function buildStaffTimelineData(Collection $bookings, string $dateStr, string $teamFilter, ?string $locale): array
    {
        $loc = $locale ?: 'en';
        $timelineStart = Carbon::parse($dateStr.' 07:00:00');
        $timelineEnd = Carbon::parse($dateStr.' 22:00:00');
        $totalMinutes = max(1, $timelineStart->diffInMinutes($timelineEnd));

        $hourMarkers = [];
        for ($h = 7; $h <= 22; $h++) {
            $tick = Carbon::parse($dateStr.sprintf(' %02d:00:00', $h));
            $leftMin = $timelineStart->diffInMinutes($tick);
            $hourMarkers[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'left_pct' => round(min(100, max(0, ($leftMin / $totalMinutes) * 100)), 4),
                'anchor_end' => $h === 22,
            ];
        }

        $segmentsByStaffId = [];
        $unassignedSegments = [];

        foreach ($bookings as $booking) {
            $seg = $this->bookingTimelineSegment($booking, $dateStr);
            if ($seg === null) {
                continue;
            }
            $ids = $booking->staffIdList();
            if ($ids === []) {
                $unassignedSegments[] = $seg;
                continue;
            }
            foreach ($ids as $sid) {
                $sid = (int) $sid;
                if ($sid <= 0) {
                    continue;
                }
                if (!isset($segmentsByStaffId[$sid])) {
                    $segmentsByStaffId[$sid] = [];
                }
                $segmentsByStaffId[$sid][] = $seg;
            }
        }

        foreach ($segmentsByStaffId as $sid => $list) {
            usort($list, fn ($a, $b) => $a['left_pct'] <=> $b['left_pct']);
            $segmentsByStaffId[$sid] = $list;
        }
        usort($unassignedSegments, fn ($a, $b) => $a['left_pct'] <=> $b['left_pct']);

        $bookingStaffIds = $bookings
            ->flatMap(fn (SaloonBooking $b) => $b->staffIdList())
            ->unique()
            ->filter()
            ->values();

        $staffQuery = SalonStaff::query()->orderBy('name');
        if ($teamFilter !== '') {
            $tid = (int) $teamFilter;
            $staffQuery->where(function ($q) use ($tid, $bookingStaffIds) {
                $q->where('team_id', $tid);
                if ($bookingStaffIds->isNotEmpty()) {
                    $q->orWhereIn('id', $bookingStaffIds->all());
                }
            });
        }
        $allStaff = $staffQuery
            ->orderByRaw('CASE WHEN team_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('team_id')
            ->get();

        $grouped = $allStaff->groupBy(function ($s) {
            return (string) ($s->team_id ?? 0);
        });

        $teamsCatalog = SalonTeam::query()->active()->ordered()->get();

        $teamsOut = [];
        foreach ($teamsCatalog as $teamModel) {
            $members = $grouped->get((string) $teamModel->id, collect());
            if ($members->isEmpty()) {
                continue;
            }
            $rows = [];
            foreach ($members as $staff) {
                $rows[] = [
                    'staff' => $staff,
                    'segments' => $segmentsByStaffId[$staff->id] ?? [],
                ];
            }
            $teamsOut[] = [
                'key' => (string) $teamModel->id,
                'label' => $teamModel->displayName($loc),
                'rows' => $rows,
            ];
        }

        $noTeamStaff = $grouped->get('0', collect());
        if ($noTeamStaff->isNotEmpty()) {
            $rows = [];
            foreach ($noTeamStaff as $staff) {
                $rows[] = [
                    'staff' => $staff,
                    'segments' => $segmentsByStaffId[$staff->id] ?? [],
                ];
            }
            $teamsOut[] = [
                'key' => '0',
                'label' => trans('messages.booking_management_team_other', [], $loc),
                'rows' => $rows,
            ];
        }

        return [
            'total_minutes' => $totalMinutes,
            'hour_markers' => $hourMarkers,
            'teams' => $teamsOut,
            'unassigned' => $unassignedSegments,
        ];
    }

    public function availabilityRange(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [
            SalonPermission::BOOKING_PAGE,
            SalonPermission::VIEW_BOOKINGS,
            SalonPermission::BOOKING_MANAGEMENT,
        ])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:salon_staff,id',
            'start' => 'nullable|date_format:Y-m-d',
            'days' => 'nullable|integer|min:1|max:45',
        ]);

        $staffIds = collect($validated['staff_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $daysCount = (int) ($validated['days'] ?? 31);
        $daysCount = max(1, min($daysCount, 45));

        $start = !empty($validated['start'])
            ? Carbon::parse($validated['start'])->startOfDay()
            : now()->startOfDay();
        $end = $start->copy()->addDays($daysCount - 1)->startOfDay();

        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $bookings = SaloonBooking::query()
            ->with(['customer'])
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['draft', 'confirmed'])
            ->get();

        $totalSlots = SalonBookingAvailability::totalSlots();
        $staffModels = SalonStaff::query()->whereIn('id', $staffIds)->get()->keyBy('id');
        $loc = app()->getLocale();

        $staffOut = [];
        foreach ($staffIds as $sid) {
            $member = $staffModels->get($sid);
            if (!$member) {
                continue;
            }

            $mine = $bookings->filter(function (SaloonBooking $b) use ($sid) {
                return in_array($sid, $b->staffIdList(), true);
            });

            $days = [];
            foreach ($dates as $ds) {
                $dayBookings = $mine->filter(fn (SaloonBooking $b) => SalonBookingAvailability::bookingDateString($b) === $ds);
                $bookingCount = $dayBookings->count();

                $masks = $dayBookings->map(fn (SaloonBooking $b) => SalonBookingAvailability::bookingSlotMaskOnDate($b, $ds))->all();
                $merged = SalonBookingAvailability::mergeSlotMasks($masks);
                $bookedCount = SalonBookingAvailability::countBookedSlots($merged);
                $level = SalonBookingAvailability::dayLevel($bookedCount, $totalSlots, $bookingCount);

                $days[$ds] = [
                    'booking_count' => $bookingCount,
                    'booked_slot_count' => $bookedCount,
                    'total_slots' => $totalSlots,
                    'level' => $level,
                ];
            }

            $staffOut[] = [
                'id' => (int) $sid,
                'name' => $member->name,
                'days' => $days,
            ];
        }

        $rangeLabel = $start->copy()->locale($loc)->translatedFormat('j M')
            .' – '
            .$end->copy()->locale($loc)->translatedFormat('j M Y');

        return response()->json([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days_count' => $daysCount,
            'range_label' => $rangeLabel,
            'dates' => $dates,
            'slot_minutes' => SalonBookingAvailability::SLOT_MINUTES,
            'day_start' => sprintf('%02d:00', SalonBookingAvailability::DAY_START_HOUR),
            'day_end' => sprintf('%02d:00', SalonBookingAvailability::DAY_END_HOUR),
            'total_slots' => $totalSlots,
            'staff' => $staffOut,
        ]);
    }

    public function availabilityDay(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [
            SalonPermission::BOOKING_PAGE,
            SalonPermission::VIEW_BOOKINGS,
            SalonPermission::BOOKING_MANAGEMENT,
        ])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'staff_id' => 'required|integer|exists:salon_staff,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $sid = (int) $validated['staff_id'];
        $dateStr = $validated['date'];

        $staff = SalonStaff::query()->findOrFail($sid);

        $bookings = SaloonBooking::query()
            ->with(['customer'])
            ->whereDate('booking_date', $dateStr)
            ->whereIn('status', ['draft', 'confirmed'])
            ->get()
            ->filter(fn (SaloonBooking $b) => in_array($sid, $b->staffIdList(), true))
            ->values();

        $timelineStart = Carbon::parse($dateStr.' 07:00:00');
        $timelineEnd = Carbon::parse($dateStr.' 22:00:00');
        $totalMinutes = max(1, $timelineStart->diffInMinutes($timelineEnd));

        $hourMarkers = [];
        for ($h = 7; $h <= 22; $h++) {
            $tick = Carbon::parse($dateStr.sprintf(' %02d:00:00', $h));
            $leftMin = $timelineStart->diffInMinutes($tick);
            $hourMarkers[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'left_pct' => round(min(100, max(0, ($leftMin / $totalMinutes) * 100)), 4),
                'anchor_end' => $h === 22,
            ];
        }

        $segments = [];
        foreach ($bookings as $booking) {
            $seg = $this->bookingTimelineSegment($booking, $dateStr);
            if ($seg !== null) {
                $segments[] = $seg;
            }
        }
        usort($segments, fn ($a, $b) => $a['left_pct'] <=> $b['left_pct']);

        $totalSlots = SalonBookingAvailability::totalSlots();
        $slots = [];
        for ($i = 0; $i < $totalSlots; $i++) {
            $items = [];
            foreach ($bookings as $booking) {
                $mask = SalonBookingAvailability::bookingSlotMaskOnDate($booking, $dateStr);
                if (!empty($mask[$i])) {
                    $items[] = [
                        'booking_id' => $booking->id,
                        'booking_no' => $booking->booking_no,
                        'time_label' => $booking->bookingTimeRangeDisplay(),
                        'customer' => $booking->customer->name ?? '—',
                        'status' => $booking->status,
                    ];
                }
            }
            $slots[] = [
                'index' => $i,
                'label' => SalonBookingAvailability::slotRangeLabel($i),
                'booked' => $items !== [],
                'bookings' => $items,
            ];
        }

        return response()->json([
            'staff' => ['id' => $staff->id, 'name' => $staff->name],
            'date' => $dateStr,
            'total_minutes' => $totalMinutes,
            'hour_markers' => $hourMarkers,
            'segments' => $segments,
            'slots' => $slots,
        ]);
    }

    public function availabilityCheck(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [
            SalonPermission::BOOKING_PAGE,
            SalonPermission::VIEW_BOOKINGS,
            SalonPermission::BOOKING_MANAGEMENT,
        ])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:salon_staff,id',
            'booking_date' => 'required|date',
            'booking_time_from' => 'required|date_format:H:i',
            'booking_time_to' => 'required|date_format:H:i',
            'exclude_booking_id' => 'nullable|integer|exists:saloon_bookings,id',
        ]);

        $dateStr = Carbon::parse($validated['booking_date'])->format('Y-m-d');
        $staffIds = collect($validated['staff_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $excludeId = isset($validated['exclude_booking_id']) ? (int) $validated['exclude_booking_id'] : null;

        $result = SalonBookingAvailability::evaluateSlotRequest(
            $staffIds,
            $dateStr,
            $validated['booking_time_from'],
            $validated['booking_time_to'],
            null,
            $excludeId > 0 ? $excludeId : null
        );

        return response()->json([
            'available' => $result['available'],
            'conflicts' => $result['conflicts'],
            'suggested_time_from' => $result['suggested_time_from'],
            'suggested_time_to' => $result['suggested_time_to'],
        ]);
    }

    private function formatSlotConflictForUser(array $check): string
    {
        $lines = [];
        foreach ($check['conflicts'] ?? [] as $c) {
            $lines[] = '#'.($c['booking_no'] ?? '').' ('.($c['time_label'] ?? '').')';
        }
        $summary = $lines !== [] ? implode(', ', $lines) : '';
        $base = trans('messages.saloon_booking_slot_conflict_short');
        if ($summary !== '') {
            $base .= ' '.$summary.'.';
        }
        if (!empty($check['suggested_time_from']) && !empty($check['suggested_time_to'])) {
            $base .= ' '.trans('messages.saloon_booking_slot_conflict_suggest_line', [
                'from' => $check['suggested_time_from'],
                'to' => $check['suggested_time_to'],
            ]);
        }

        return trim($base);
    }

    private function resolveIncomeExpenseFilter(Request $request): array
    {
        $monthInput = (string) $request->get('month', now()->format('Y-m'));
        $fromInput = trim((string) $request->get('from_date', ''));
        $toInput = trim((string) $request->get('to_date', ''));

        $fromDate = null;
        $toDate = null;

        if ($fromInput !== '' && $toInput !== '') {
            try {
                $fromDate = Carbon::parse($fromInput)->startOfDay();
                $toDate = Carbon::parse($toInput)->endOfDay();
                if ($toDate->lt($fromDate)) {
                    [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
                }
            } catch (\Throwable $e) {
                $fromDate = null;
                $toDate = null;
                $fromInput = '';
                $toInput = '';
            }
        }

        if (!$fromDate || !$toDate) {
            try {
                $monthStart = Carbon::parse($monthInput.'-01')->startOfMonth();
            } catch (\Throwable $e) {
                $monthStart = now()->startOfMonth();
                $monthInput = $monthStart->format('Y-m');
            }
            $fromDate = $monthStart->copy()->startOfDay();
            $toDate = $monthStart->copy()->endOfMonth()->endOfDay();
            $fromInput = '';
            $toInput = '';
        } else {
            $fromInput = $fromDate->toDateString();
            $toInput = $toDate->toDateString();
        }

        return [
            'month' => $monthInput,
            'from_input' => $fromInput,
            'to_input' => $toInput,
            'from' => $fromDate,
            'to' => $toDate,
            'label' => $fromDate->toDateString().' to '.$toDate->toDateString(),
        ];
    }

    public function incomeExpenseReport(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::INCOME_EXPENSE_REPORT])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $filter = $this->resolveIncomeExpenseFilter($request);
        $fromDate = $filter['from']->toDateString();
        $toDate = $filter['to']->toDateString();

        $bookings = SaloonBooking::query()
            ->whereBetween('booking_date', [$fromDate, $toDate])
            ->whereIn('status', ['draft', 'confirmed'])
            ->get();

        $expenses = SalonExpense::query()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->get();

        $daily = [];
        foreach ($bookings as $b) {
            if (!$b->booking_date) {
                continue;
            }
            $dateKey = $b->booking_date->format('Y-m-d');
            if (!isset($daily[$dateKey])) {
                $daily[$dateKey] = [
                    'date' => $dateKey,
                    'bookings_count' => 0,
                    'total_amount' => 0.0,
                    'received_amount' => 0.0,
                    'remaining_amount' => 0.0,
                    'expense_amount' => 0.0,
                    'income_amount' => 0.0,
                ];
            }
            $daily[$dateKey]['bookings_count']++;
            $daily[$dateKey]['total_amount'] += (float) $b->total_services_amount;
            $daily[$dateKey]['received_amount'] += (float) $b->total_paid;
            $daily[$dateKey]['remaining_amount'] += (float) $b->total_remaining;
        }

        foreach ($expenses as $e) {
            if (!$e->expense_date) {
                continue;
            }
            $dateKey = $e->expense_date->format('Y-m-d');
            if (!isset($daily[$dateKey])) {
                $daily[$dateKey] = [
                    'date' => $dateKey,
                    'bookings_count' => 0,
                    'total_amount' => 0.0,
                    'received_amount' => 0.0,
                    'remaining_amount' => 0.0,
                    'expense_amount' => 0.0,
                    'income_amount' => 0.0,
                ];
            }
            $daily[$dateKey]['expense_amount'] += (float) $e->amount;
        }

        foreach ($daily as $k => $row) {
            $daily[$k]['total_amount'] = round((float) $row['total_amount'], 3);
            $daily[$k]['received_amount'] = round((float) $row['received_amount'], 3);
            $daily[$k]['remaining_amount'] = round((float) $row['remaining_amount'], 3);
            $daily[$k]['expense_amount'] = round((float) $row['expense_amount'], 3);
            $daily[$k]['income_amount'] = round((float) $daily[$k]['received_amount'] - (float) $daily[$k]['expense_amount'], 3);
        }

        ksort($daily);
        $rows = collect(array_values($daily));

        $summary = [
            'received_amount' => round((float) $rows->sum('received_amount'), 3),
            'pending_amount' => round((float) $rows->sum('remaining_amount'), 3),
            'total_amount' => round((float) $rows->sum('total_amount'), 3),
            'expense_amount' => round((float) $rows->sum('expense_amount'), 3),
            'income_amount' => round((float) ($rows->sum('received_amount') - $rows->sum('expense_amount')), 3),
        ];

        $dailyRows = $rows->forPage((int) $request->get('page', 1), 15)->values();
        $paginatedRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $dailyRows,
            $rows->count(),
            15,
            (int) $request->get('page', 1),
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return view('saloon.income_expense_report', [
            'rows' => $paginatedRows,
            'summary' => $summary,
            'monthInput' => $filter['month'],
            'fromDate' => $filter['from_input'],
            'toDate' => $filter['to_input'],
            'selectedRangeLabel' => $filter['label'],
        ]);
    }

    public function incomeExpenseReportExportExcel(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::INCOME_EXPENSE_REPORT])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $filter = $this->resolveIncomeExpenseFilter($request);
        $fromDate = $filter['from']->toDateString();
        $toDate = $filter['to']->toDateString();

        $bookings = SaloonBooking::query()
            ->whereBetween('booking_date', [$fromDate, $toDate])
            ->whereIn('status', ['draft', 'confirmed'])
            ->get();
        $expenses = SalonExpense::query()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->get();

        $daily = [];
        foreach ($bookings as $b) {
            if (!$b->booking_date) {
                continue;
            }
            $dateKey = $b->booking_date->format('Y-m-d');
            if (!isset($daily[$dateKey])) {
                $daily[$dateKey] = ['bookings_count' => 0, 'total_amount' => 0.0, 'received_amount' => 0.0, 'remaining_amount' => 0.0, 'expense_amount' => 0.0];
            }
            $daily[$dateKey]['bookings_count']++;
            $daily[$dateKey]['total_amount'] += (float) $b->total_services_amount;
            $daily[$dateKey]['received_amount'] += (float) $b->total_paid;
            $daily[$dateKey]['remaining_amount'] += (float) $b->total_remaining;
        }
        foreach ($expenses as $e) {
            if (!$e->expense_date) {
                continue;
            }
            $dateKey = $e->expense_date->format('Y-m-d');
            if (!isset($daily[$dateKey])) {
                $daily[$dateKey] = ['bookings_count' => 0, 'total_amount' => 0.0, 'received_amount' => 0.0, 'remaining_amount' => 0.0, 'expense_amount' => 0.0];
            }
            $daily[$dateKey]['expense_amount'] += (float) $e->amount;
        }
        ksort($daily);

        $filename = 'saloon_income_expense_report_'.$filter['from']->format('Ymd').'_to_'.$filter['to']->format('Ymd').'.csv';
        return response()->streamDownload(function () use ($daily, $filter) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Saloon Income Expense Report']);
            fputcsv($out, ['Date Range', $filter['label']]);
            fputcsv($out, []);
            fputcsv($out, ['Date', 'Total Bookings', 'Total Bookings Amount', 'Amount Received', 'Remaining Amount', 'Expense', 'Total Income']);

            $totals = ['total_amount' => 0.0, 'received_amount' => 0.0, 'remaining_amount' => 0.0, 'expense_amount' => 0.0];
            foreach ($daily as $date => $row) {
                $income = round((float) $row['received_amount'] - (float) $row['expense_amount'], 3);
                fputcsv($out, [
                    $date,
                    (int) $row['bookings_count'],
                    number_format((float) $row['total_amount'], 3, '.', ''),
                    number_format((float) $row['received_amount'], 3, '.', ''),
                    number_format((float) $row['remaining_amount'], 3, '.', ''),
                    number_format((float) $row['expense_amount'], 3, '.', ''),
                    number_format($income, 3, '.', ''),
                ]);
                $totals['total_amount'] += (float) $row['total_amount'];
                $totals['received_amount'] += (float) $row['received_amount'];
                $totals['remaining_amount'] += (float) $row['remaining_amount'];
                $totals['expense_amount'] += (float) $row['expense_amount'];
            }
            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', number_format($totals['total_amount'], 3, '.', ''), number_format($totals['received_amount'], 3, '.', ''), number_format($totals['remaining_amount'], 3, '.', ''), number_format($totals['expense_amount'], 3, '.', ''), number_format($totals['received_amount'] - $totals['expense_amount'], 3, '.', '')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Chart + summary for monthly income (shared: full report page & salon dashboard).
     *
     * @return array{
     *     monthInput: string,
     *     teamFilter: string,
     *     weekendOnly: bool,
     *     salonTeams: \Illuminate\Support\Collection,
     *     chartData: array,
     *     summaryTotals: array,
     *     monthLabel: string,
     *     monthStart: \Carbon\Carbon,
     *     monthEnd: \Carbon\Carbon
     * }
     */
    public function buildMonthlyIncomeChartPayload(Request $request): array
    {
        $monthInput = $request->get('month', now()->format('Y-m'));
        $weekendOnly = $request->boolean('weekend_only');
        try {
            $monthStart = Carbon::parse($monthInput.'-01')->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
            $monthInput = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $salonTeams = SalonTeam::query()->active()->ordered()->get();
        $allowedTeamIds = $salonTeams->pluck('id')->map(fn ($id) => (string) $id)->all();

        $teamFilter = (string) $request->get('team', '');
        if ($teamFilter === 'all') {
            $teamFilter = '';
        }
        if ($teamFilter !== '' && !in_array($teamFilter, $allowedTeamIds, true)) {
            $teamFilter = '';
        }

        $query = SaloonBooking::query()
            ->with(['customer', 'staff', 'salonTeam'])
            ->whereBetween('booking_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('status', ['draft', 'confirmed']);

        if ($teamFilter !== '') {
            $query->where('team_id', (int) $teamFilter);
        }
        if ($weekendOnly) {
            $query->whereRaw('DAYOFWEEK(booking_date) IN (5,6,7)');
        }

        $allBookings = $query->get();

        $labels = [];
        $dailyData = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $isWeekend = in_array($cursor->dayOfWeekIso, [4, 5, 6], true);
            if (!$weekendOnly || $isWeekend) {
                $key = $cursor->toDateString();
                $labels[$key] = $cursor->format('D').' '.$cursor->format('d');
                $dailyData[$key] = ['bookings' => 0, 'amount' => 0, 'paid' => 0, 'remaining' => 0];
            }
            $cursor->addDay();
        }
        foreach ($allBookings as $b) {
            if (!$b->booking_date) {
                continue;
            }
            $key = $b->booking_date->toDateString();
            if (!isset($dailyData[$key])) {
                continue;
            }
            $dailyData[$key]['bookings']++;
            $dailyData[$key]['amount'] = round($dailyData[$key]['amount'] + (float) $b->total_services_amount, 3);
            $dailyData[$key]['paid'] = round($dailyData[$key]['paid'] + (float) $b->total_paid, 3);
            $dailyData[$key]['remaining'] = round($dailyData[$key]['remaining'] + (float) $b->total_remaining, 3);
        }

        $chartData = [
            'labels' => array_values($labels),
            'bookings' => array_values(array_map(fn ($d) => $d['bookings'], $dailyData)),
            'amount' => array_values(array_map(fn ($d) => $d['amount'], $dailyData)),
            'paid' => array_values(array_map(fn ($d) => $d['paid'], $dailyData)),
            'remaining' => array_values(array_map(fn ($d) => $d['remaining'], $dailyData)),
        ];

        $summaryTotals = [
            'bookings' => $allBookings->count(),
            'amount' => round((float) $allBookings->sum('total_services_amount'), 3),
            'paid' => round((float) $allBookings->sum('total_paid'), 3),
            'remaining' => round((float) $allBookings->sum('total_remaining'), 3),
            'weekend_income' => round((float) $allBookings->sum('total_paid'), 3),
        ];

        $loc = session('locale', 'en');
        $monthLabel = $monthStart->copy()->locale($loc)->translatedFormat('F Y');

        return compact(
            'monthInput',
            'teamFilter',
            'weekendOnly',
            'salonTeams',
            'chartData',
            'summaryTotals',
            'monthLabel',
            'monthStart',
            'monthEnd'
        );
    }

    public function monthlyIncomeReport(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::MONTHLY_INCOME_REPORT])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $payload = $this->buildMonthlyIncomeChartPayload($request);
        $monthStart = $payload['monthStart'];
        $monthEnd = $payload['monthEnd'];
        $teamFilter = $payload['teamFilter'];
        $weekendOnly = $payload['weekendOnly'];

        $paginatedQuery = SaloonBooking::query()
            ->with(['customer', 'staff', 'salonTeam'])
            ->whereBetween('booking_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('status', ['draft', 'confirmed']);

        if ($teamFilter !== '') {
            $paginatedQuery->where('team_id', (int) $teamFilter);
        }
        if ($weekendOnly) {
            $paginatedQuery->whereRaw('DAYOFWEEK(booking_date) IN (5,6,7)');
        }

        $bookings = $paginatedQuery->latest('booking_date')->latest('id')
            ->paginate(15)
            ->withQueryString();

        $staffById = SaloonBooking::staffByIdMapForBookings($bookings);

        if ($request->ajax()) {
            return response()->json([
                'chartData' => $payload['chartData'],
                'summaryTotals' => $payload['summaryTotals'],
                'monthLabel' => $payload['monthLabel'],
                'weekendOnly' => $payload['weekendOnly'],
                'tableHtml' => view('saloon._monthly_report_table', compact('bookings', 'staffById'))->render(),
            ]);
        }

        return view('saloon.monthly_income_report', [
            'bookings' => $bookings,
            'staffById' => $staffById,
            'salonTeams' => $payload['salonTeams'],
            'teamFilter' => $payload['teamFilter'],
            'weekendOnly' => $payload['weekendOnly'],
            'monthInput' => $payload['monthInput'],
            'monthLabel' => $payload['monthLabel'],
            'chartData' => $payload['chartData'],
            'summaryTotals' => $payload['summaryTotals'],
        ]);
    }

    public function monthlyIncomeReportExportExcel(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::MONTHLY_INCOME_REPORT])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $monthInput = (string) $request->get('month', now()->format('Y-m'));
        $teamFilter = (string) $request->get('team', '');
        $weekendOnly = $request->boolean('weekend_only');
        try {
            $monthStart = Carbon::parse($monthInput.'-01')->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $query = SaloonBooking::query()
            ->with(['customer', 'salonTeam'])
            ->whereBetween('booking_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('status', ['draft', 'confirmed']);
        if ($teamFilter !== '' && $teamFilter !== 'all') {
            $query->where('team_id', (int) $teamFilter);
        }
        if ($weekendOnly) {
            $query->whereRaw('DAYOFWEEK(booking_date) IN (5,6,7)');
        }
        $rows = $query->orderBy('booking_date')->orderBy('id')->get();

        $filename = 'saloon_monthly_income_'.$monthStart->format('Ym').($weekendOnly ? '_weekend' : '').'.csv';
        $totals = [
            'amount' => round((float) $rows->sum('total_services_amount'), 3),
            'paid' => round((float) $rows->sum('total_paid'), 3),
            'remaining' => round((float) $rows->sum('total_remaining'), 3),
        ];

        return response()->streamDownload(function () use ($rows, $monthStart, $monthEnd, $totals, $weekendOnly) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Saloon Monthly Income Report']);
            fputcsv($out, ['Range', $monthStart->toDateString().' to '.$monthEnd->toDateString()]);
            fputcsv($out, ['Weekend Only', $weekendOnly ? 'Yes' : 'No']);
            fputcsv($out, []);
            fputcsv($out, ['#', 'Booking No', 'Customer', 'Team', 'Date', 'Time', 'Total', 'Paid', 'Remaining', 'Status']);
            foreach ($rows as $idx => $b) {
                fputcsv($out, [
                    $idx + 1,
                    (string) $b->booking_no,
                    (string) ($b->customer->name ?? ''),
                    (string) ($b->salonTeam?->displayName(session('locale')) ?? ''),
                    $b->booking_date ? $b->booking_date->format('Y-m-d') : '',
                    $b->bookingTimeRangeDisplay(),
                    number_format((float) $b->total_services_amount, 3, '.', ''),
                    number_format((float) $b->total_paid, 3, '.', ''),
                    number_format((float) $b->total_remaining, 3, '.', ''),
                    (string) $b->status,
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', '', '', '', '', number_format($totals['amount'], 3, '.', ''), number_format($totals['paid'], 3, '.', ''), number_format($totals['remaining'], 3, '.', ''), '']);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
