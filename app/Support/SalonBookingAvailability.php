<?php

namespace App\Support;

use App\Models\SaloonBooking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SalonBookingAvailability
{
    public const SLOT_MINUTES = 60;

    public const DAY_START_HOUR = 7;

    public const DAY_END_HOUR = 22;

    public static function totalSlots(): int
    {
        return (int) ((self::DAY_END_HOUR - self::DAY_START_HOUR) * 60 / self::SLOT_MINUTES);
    }

    public static function bookingDateString(SaloonBooking $booking): ?string
    {
        $d = $booking->booking_date;
        if ($d === null) {
            return null;
        }
        if ($d instanceof CarbonInterface) {
            return $d->format('Y-m-d');
        }

        return substr((string) $d, 0, 10);
    }

    /**
     * @return array<int, bool>
     */
    public static function bookingSlotMaskOnDate(SaloonBooking $booking, string $dateStr): array
    {
        $n = self::totalSlots();
        $mask = array_fill(0, $n, false);

        if (self::bookingDateString($booking) !== $dateStr) {
            return $mask;
        }

        $from = $booking->booking_time_from ?? $booking->booking_time;
        if ($from === null || $from === '') {
            return $mask;
        }

        $fromHm = SaloonBooking::formatTimeHm($from);
        if ($fromHm === null || $fromHm === '') {
            return $mask;
        }

        $start = Carbon::parse($dateStr.' '.$fromHm.':00');
        $toRaw = $booking->booking_time_to;
        if ($toRaw !== null && $toRaw !== '') {
            $toHm = SaloonBooking::formatTimeHm($toRaw);
            $end = $toHm ? Carbon::parse($dateStr.' '.$toHm.':00') : $start->copy()->addMinutes(self::SLOT_MINUTES);
        } else {
            $end = $start->copy()->addMinutes(self::SLOT_MINUTES);
        }
        if ($end->lte($start)) {
            $end = $start->copy()->addMinutes(self::SLOT_MINUTES);
        }

        $timelineStart = Carbon::parse($dateStr.sprintf(' %02d:00:00', self::DAY_START_HOUR));
        $timelineEnd = Carbon::parse($dateStr.sprintf(' %02d:00:00', self::DAY_END_HOUR));
        if ($end->lte($timelineStart) || $start->gte($timelineEnd)) {
            return $mask;
        }

        $segStart = $start->greaterThan($timelineStart) ? $start->copy() : $timelineStart->copy();
        $segEnd = $end->lessThan($timelineEnd) ? $end->copy() : $timelineEnd->copy();
        if ($segEnd->lte($segStart)) {
            return $mask;
        }

        $slotMin = self::SLOT_MINUTES;
        $t = $timelineStart->copy();
        for ($i = 0; $i < $n; $i++) {
            $slotEnd = $t->copy()->addMinutes($slotMin);
            if ($segStart->lt($slotEnd) && $segEnd->gt($t)) {
                $mask[$i] = true;
            }
            $t->addMinutes($slotMin);
        }

        return $mask;
    }

    /**
     * @param  array<int, array<int, bool>>  $masks
     * @return array<int, bool>
     */
    public static function mergeSlotMasks(array $masks): array
    {
        $n = self::totalSlots();
        $out = array_fill(0, $n, false);
        foreach ($masks as $m) {
            for ($i = 0; $i < $n; $i++) {
                if (!empty($m[$i])) {
                    $out[$i] = true;
                }
            }
        }

        return $out;
    }

    public static function slotRangeLabel(int $index): string
    {
        $startMin = self::DAY_START_HOUR * 60 + $index * self::SLOT_MINUTES;
        $endMin = $startMin + self::SLOT_MINUTES;
        $sh = intdiv($startMin, 60);
        $sm = $startMin % 60;
        $eh = intdiv($endMin, 60);
        $em = $endMin % 60;

        return sprintf('%02d:%02d–%02d:%02d', $sh, $sm, $eh, $em);
    }

    public static function countBookedSlots(array $mask): int
    {
        return count(array_filter($mask));
    }

    /**
     * @return 'free'|'partial'|'full'
     */
    public static function dayLevel(int $bookedSlots, int $totalSlots, int $bookingCount): string
    {
        if ($bookingCount === 0) {
            return 'free';
        }
        if ($bookedSlots >= $totalSlots) {
            return 'full';
        }

        return 'partial';
    }

    /**
     * Booking time segment clipped to operating hours (07:00–22:00), or null if none / outside.
     *
     * @return array{start: Carbon, end: Carbon}|null
     */
    public static function bookingIntervalClippedToOperatingHours(SaloonBooking $booking, string $dateStr): ?array
    {
        if (self::bookingDateString($booking) !== $dateStr) {
            return null;
        }

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
            $end = $toHm ? Carbon::parse($dateStr.' '.$toHm.':00') : $start->copy()->addMinutes(self::SLOT_MINUTES);
        } else {
            $end = $start->copy()->addMinutes(self::SLOT_MINUTES);
        }
        if ($end->lte($start)) {
            $end = $start->copy()->addMinutes(self::SLOT_MINUTES);
        }

        $timelineStart = Carbon::parse($dateStr.sprintf(' %02d:00:00', self::DAY_START_HOUR));
        $timelineEnd = Carbon::parse($dateStr.sprintf(' %02d:00:00', self::DAY_END_HOUR));
        if ($end->lte($timelineStart) || $start->gte($timelineEnd)) {
            return null;
        }

        $segStart = $start->greaterThan($timelineStart) ? $start->copy() : $timelineStart->copy();
        $segEnd = $end->lessThan($timelineEnd) ? $end->copy() : $timelineEnd->copy();
        if ($segEnd->lte($segStart)) {
            return null;
        }

        return ['start' => $segStart, 'end' => $segEnd];
    }

    public static function intervalsOverlap(Carbon $a0, Carbon $a1, Carbon $b0, Carbon $b1): bool
    {
        return $a0->lt($b1) && $b0->lt($a1);
    }

    /**
     * @param  array<int, int>  $staffIds
     * @param  Collection<int, SaloonBooking>  $dayBookings
     */
    public static function windowOverlapsStaffBookings(
        array $staffIds,
        string $dateStr,
        Carbon $windowStart,
        Carbon $windowEnd,
        Collection $dayBookings
    ): bool {
        foreach ($staffIds as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) {
                continue;
            }
            foreach ($dayBookings as $booking) {
                if (!in_array($sid, $booking->staffIdList(), true)) {
                    continue;
                }
                $iv = self::bookingIntervalClippedToOperatingHours($booking, $dateStr);
                if ($iv === null) {
                    continue;
                }
                if (self::intervalsOverlap($windowStart, $windowEnd, $iv['start'], $iv['end'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $staffIds
     * @param  Collection<int, SaloonBooking>  $dayBookings
     * @return list<array{booking_id: int, booking_no: string, time_label: string}>
     */
    public static function conflictSummariesForWindow(
        array $staffIds,
        string $dateStr,
        Carbon $windowStart,
        Carbon $windowEnd,
        Collection $dayBookings
    ): array {
        $seen = [];
        $out = [];
        foreach ($staffIds as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) {
                continue;
            }
            foreach ($dayBookings as $booking) {
                if (!in_array($sid, $booking->staffIdList(), true)) {
                    continue;
                }
                $iv = self::bookingIntervalClippedToOperatingHours($booking, $dateStr);
                if ($iv === null) {
                    continue;
                }
                if (!self::intervalsOverlap($windowStart, $windowEnd, $iv['start'], $iv['end'])) {
                    continue;
                }
                $bid = (int) $booking->id;
                if (isset($seen[$bid])) {
                    continue;
                }
                $seen[$bid] = true;
                $out[] = [
                    'booking_id' => $bid,
                    'booking_no' => (string) $booking->booking_no,
                    'time_label' => $booking->bookingTimeRangeDisplay(),
                ];
            }
        }

        return $out;
    }

    /**
     * @param  array<int, int>  $staffIds
     * @param  Collection<int, SaloonBooking>  $dayBookings
     * @return array{from: string, to: string}|null
     */
    public static function findNextAvailableWindow(
        array $staffIds,
        string $dateStr,
        int $durationMinutes,
        Carbon $requestedStart,
        Collection $dayBookings
    ): ?array {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if ($staffIds === []) {
            return null;
        }

        $durationMinutes = max(15, min($durationMinutes, 24 * 60));
        $dayStart = Carbon::parse($dateStr.sprintf(' %02d:00:00', self::DAY_START_HOUR));
        $dayEnd = Carbon::parse($dateStr.sprintf(' %02d:00:00', self::DAY_END_HOUR));
        $latestStart = $dayEnd->copy()->subMinutes($durationMinutes);
        if ($latestStart->lt($dayStart)) {
            return null;
        }

        $tryScan = function (Carbon $from, Carbon $untilExclusive) use ($staffIds, $dateStr, $durationMinutes, $dayBookings, $dayEnd, $latestStart): ?array {
            $t = $from->copy()->second(0);
            $t->minute((int) (floor($t->minute / 15) * 15));
            while ($t->lt($untilExclusive) && $t->lte($latestStart)) {
                $cEnd = $t->copy()->addMinutes($durationMinutes);
                if ($cEnd->gt($dayEnd)) {
                    break;
                }
                if (!self::windowOverlapsStaffBookings($staffIds, $dateStr, $t, $cEnd, $dayBookings)) {
                    return [
                        'from' => $t->format('H:i'),
                        'to' => $cEnd->format('H:i'),
                    ];
                }
                $t->addMinutes(15);
            }

            return null;
        };

        $alignedReq = $requestedStart->copy()->second(0);
        $alignedReq->minute((int) (floor($alignedReq->minute / 15) * 15));

        $first = $tryScan($alignedReq, $dayEnd->copy()->addSecond());
        if ($first !== null) {
            return $first;
        }

        return $tryScan($dayStart, $alignedReq);
    }

    /**
     * @param  array<int, int>  $staffIds
     * @return array{
     *     available: bool,
     *     conflicts: list<array{booking_id: int, booking_no: string, time_label: string}>,
     *     suggested_time_from: ?string,
     *     suggested_time_to: ?string
     * }
     */
    public static function evaluateSlotRequest(
        array $staffIds,
        string $dateStr,
        string $timeFromHm,
        string $timeToHm,
        ?Collection $dayBookings = null,
        ?int $excludeBookingId = null
    ): array {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if ($staffIds === []) {
            return [
                'available' => true,
                'conflicts' => [],
                'suggested_time_from' => null,
                'suggested_time_to' => null,
            ];
        }

        $reqStart = Carbon::parse($dateStr.' '.$timeFromHm.':00');
        $reqEnd = Carbon::parse($dateStr.' '.$timeToHm.':00');
        if ($reqEnd->lte($reqStart)) {
            return [
                'available' => true,
                'conflicts' => [],
                'suggested_time_from' => null,
                'suggested_time_to' => null,
            ];
        }

        $bookings = $dayBookings ?? SaloonBooking::query()
            ->whereDate('booking_date', $dateStr)
            ->whereIn('status', ['draft', 'confirmed'])
            ->get();

        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $bookings = $bookings->where('id', '!=', $excludeBookingId)->values();
        }

        $conflicts = self::conflictSummariesForWindow($staffIds, $dateStr, $reqStart, $reqEnd, $bookings);
        if ($conflicts === []) {
            return [
                'available' => true,
                'conflicts' => [],
                'suggested_time_from' => null,
                'suggested_time_to' => null,
            ];
        }

        $duration = (int) max(15, $reqStart->diffInMinutes($reqEnd));
        $suggested = self::findNextAvailableWindow($staffIds, $dateStr, $duration, $reqStart, $bookings);

        return [
            'available' => false,
            'conflicts' => $conflicts,
            'suggested_time_from' => $suggested['from'] ?? null,
            'suggested_time_to' => $suggested['to'] ?? null,
        ];
    }
}
