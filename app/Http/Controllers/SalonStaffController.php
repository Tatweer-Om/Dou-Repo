<?php

namespace App\Http\Controllers;

use App\Models\SalonStaff;
use App\Models\SalonTeam;
use App\Models\SaloonBooking;
use App\Support\SalonBookingAvailability;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SalonStaffController extends Controller
{
    /**
     * Staff list permission, or own profile when salon scope and users.salon_staff_id matches.
     */
    private function userCanAccessSalonStaffProfile(SalonStaff $salonstaff): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $permissions = $user->permissions ?? [];
        if (SalonPermission::userHasAny($permissions, [SalonPermission::STAFF])) {
            return true;
        }

        return ($user->user_scope ?? '') === 'saloon'
            && !empty($user->salon_staff_id)
            && (int) $user->salon_staff_id === (int) $salonstaff->id;
    }

    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!SalonPermission::userHasAny($permissions, [SalonPermission::STAFF])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $staff = SalonStaff::query()->with('salonTeam')->latest()->paginate(10)->withQueryString();
        $teams = SalonTeam::query()->active()->ordered()->get();

        if ($request->ajax()) {
            return view('saloon.staff', compact('staff', 'teams'))->render();
        }

        return view('saloon.staff', compact('staff', 'teams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'required',
            'email'      => 'nullable|email|max:255',
            'team_id'    => 'required|integer|exists:salon_teams,id',
            'address'    => 'nullable|string',
            'staff_file' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,bmp,pdf|max:2048',
        ]);

        $userId = Auth::id();
        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        $staffFile = '';

        if ($request->hasFile('staff_file')) {
            $folderPath = public_path('uploads/staff_files');

            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            $staffFile = time() . '_' . uniqid() . '.' . $request->file('staff_file')->extension();
            $request->file('staff_file')->move($folderPath, $staffFile);
        }

        $staff = SalonStaff::create([
            'name'        => $request->name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'team_id'    => (int) $request->team_id,
            'address'     => $request->address,
            'staff_image' => $staffFile,
            'added_by'    => $userName,
            'user_id'     => $userId,
        ]);

        return response()->json([
            'success'  => true,
            'staff_id' => $staff->id,
            'message'  => 'Staff added successfully',
        ]);
    }

    public function show(SalonStaff $salonstaff)
    {
        $salonstaff->load('salonTeam');

        return response()->json($salonstaff);
    }

    public function update(Request $request, SalonStaff $salonstaff)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'required',
            'email'      => 'nullable|email|max:255',
            'team_id'    => 'required|integer|exists:salon_teams,id',
            'address'    => 'nullable|string',
            'staff_file' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,bmp,pdf|max:2048',
        ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        if ($request->hasFile('staff_file')) {
            if ($salonstaff->staff_image) {
                $oldFilePath = public_path('uploads/staff_files/' . $salonstaff->staff_image);

                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }

            $folderPath = public_path('uploads/staff_files');

            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            $staffFile = time() . '_' . uniqid() . '.' . $request->file('staff_file')->extension();
            $request->file('staff_file')->move($folderPath, $staffFile);

            $salonstaff->staff_image = $staffFile;
        }

        $salonstaff->name = $request->name;
        $salonstaff->phone = $request->phone;
        $salonstaff->email = $request->email;
        $salonstaff->team_id = (int) $request->team_id;
        $salonstaff->address = $request->address;
        $salonstaff->updated_by = $userName;
        $salonstaff->save();

        return response()->json([
            'success' => true,
            'staff'   => $salonstaff,
            'message' => 'Staff updated successfully',
        ]);
    }

    public function destroy(SalonStaff $salonstaff)
    {
        if ($salonstaff->staff_image) {
            $filePath = public_path('uploads/staff_files/' . $salonstaff->staff_image);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $salonstaff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully',
        ]);
    }

    public function profile(SalonStaff $salonstaff)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        if (!$this->userCanAccessSalonStaffProfile($salonstaff)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $salonstaff->load('salonTeam');
        $staffId = (int) $salonstaff->id;

        $allBookings = SaloonBooking::query()
            ->with(['customer', 'payments'])
            ->whereIn('status', ['draft', 'confirmed'])
            ->get()
            ->filter(fn (SaloonBooking $b) => in_array($staffId, $b->staffIdList(), true))
            ->values();

        $totalBookings = $allBookings->count();
        $totalAmount = round((float) $allBookings->sum('total_services_amount'), 3);
        $totalPaid = round((float) $allBookings->sum('total_paid'), 3);
        $totalRemaining = round((float) $allBookings->sum('total_remaining'), 3);

        $bookingsPaginated = SaloonBooking::query()
            ->with(['customer', 'staff', 'salonTeam'])
            ->whereIn('status', ['draft', 'confirmed'])
            ->get()
            ->filter(fn (SaloonBooking $b) => in_array($staffId, $b->staffIdList(), true))
            ->sortByDesc('id');

        $page = max(1, (int) request('page', 1));
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $bookingsPaginated->forPage($page, $perPage)->values(),
            $bookingsPaginated->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $staffById = SaloonBooking::staffByIdMapForBookings($paginated);

        $stats = compact('totalBookings', 'totalAmount', 'totalPaid', 'totalRemaining');

        if (request()->ajax()) {
            return view('saloon.staff_profile', [
                'staff' => $salonstaff,
                'stats' => $stats,
                'bookings' => $paginated,
                'staffById' => $staffById,
            ])->render();
        }

        return view('saloon.staff_profile', [
            'staff' => $salonstaff,
            'stats' => $stats,
            'bookings' => $paginated,
            'staffById' => $staffById,
        ]);
    }

    public function profileAvailabilityRange(Request $request, SalonStaff $salonstaff)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanAccessSalonStaffProfile($salonstaff)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $staffId = (int) $salonstaff->id;

        $validated = $request->validate([
            'start' => 'nullable|date_format:Y-m-d',
            'days' => 'nullable|integer|min:1|max:45',
        ]);

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

        $mine = $bookings->filter(fn (SaloonBooking $b) => in_array($staffId, $b->staffIdList(), true));

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

        $loc = session('locale', 'en');
        $rangeLabel = $start->copy()->locale($loc)->translatedFormat('j M')
            .' – '
            .$end->copy()->locale($loc)->translatedFormat('j M Y');

        return response()->json([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days_count' => $daysCount,
            'range_label' => $rangeLabel,
            'dates' => $dates,
            'total_slots' => $totalSlots,
            'staff' => [[
                'id' => $staffId,
                'name' => $salonstaff->name,
                'days' => $days,
            ]],
        ]);
    }

    public function profileAvailabilityDay(Request $request, SalonStaff $salonstaff)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanAccessSalonStaffProfile($salonstaff)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $staffId = (int) $salonstaff->id;

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $dateStr = $validated['date'];

        $bookings = SaloonBooking::query()
            ->with(['customer', 'payments'])
            ->whereDate('booking_date', $dateStr)
            ->whereIn('status', ['draft', 'confirmed'])
            ->get()
            ->filter(fn (SaloonBooking $b) => in_array($staffId, $b->staffIdList(), true))
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
            $seg = $this->profileBookingTimelineSegment($booking, $dateStr);
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
                        'total_amount' => number_format((float) $booking->total_services_amount, 3),
                        'paid' => number_format((float) $booking->total_paid, 3),
                        'remaining' => number_format((float) $booking->total_remaining, 3),
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

        $dayBookingsList = $bookings->map(fn (SaloonBooking $b) => [
            'booking_id' => $b->id,
            'booking_no' => $b->booking_no,
            'customer' => $b->customer->name ?? '—',
            'time_label' => $b->bookingTimeRangeDisplay(),
            'status' => $b->status,
            'total_amount' => number_format((float) $b->total_services_amount, 3),
            'paid' => number_format((float) $b->total_paid, 3),
            'remaining' => number_format((float) $b->total_remaining, 3),
        ])->values();

        return response()->json([
            'staff' => ['id' => $salonstaff->id, 'name' => $salonstaff->name],
            'date' => $dateStr,
            'total_minutes' => $totalMinutes,
            'hour_markers' => $hourMarkers,
            'segments' => $segments,
            'slots' => $slots,
            'day_bookings' => $dayBookingsList,
        ]);
    }

    private function profileBookingTimelineSegment(SaloonBooking $booking, string $dateStr): ?array
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
            'total_amount' => number_format((float) $booking->total_services_amount, 3),
            'paid' => number_format((float) $booking->total_paid, 3),
            'remaining' => number_format((float) $booking->total_remaining, 3),
        ];
    }
}