<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class SaloonBooking extends Model
{
    protected $fillable = [
        'booking_no',
        'customer_id',
        'team_id',
        'staff_id',
        'staff_ids',
        'booking_date',
        'booking_time',
        'booking_time_from',
        'booking_time_to',
        'total_services',
        'total_services_amount',
        'total_paid',
        'total_remaining',
        'status',
        'special_notes',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_services_amount' => 'decimal:3',
        'total_paid' => 'decimal:3',
        'total_remaining' => 'decimal:3',
        'staff_ids' => 'array',
    ];

    /**
     * Ordered salon_staff IDs for this booking (falls back to staff_id for legacy rows).
     */
    public function staffIdList(): array
    {
        $ids = $this->staff_ids;
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] && $this->staff_id) {
            return [(int) $this->staff_id];
        }

        return $ids;
    }

    /**
     * Single query: map staff id => SalonStaff for a set of bookings (list views).
     */
    public static function staffByIdMapForBookings(iterable $bookings): Collection
    {
        $all = collect();
        foreach ($bookings as $b) {
            if (!$b instanceof self) {
                continue;
            }
            $all = $all->merge($b->staffIdList());
        }
        $unique = $all->unique()->filter()->values();
        if ($unique->isEmpty()) {
            return collect();
        }

        return SalonStaff::query()->whereIn('id', $unique->all())->get()->keyBy('id');
    }

    public static function formatTimeHm(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }

        $s = (string) $value;

        return strlen($s) >= 5 ? substr($s, 0, 5) : $s;
    }

    /**
     * Start time for schedule column (prefers new from/to fields).
     */
    public function bookingScheduleStart(): mixed
    {
        return $this->booking_time_from ?? $this->booking_time;
    }

    /**
     * Human-readable time range for list views (legacy single booking_time supported).
     */
    public function bookingTimeRangeDisplay(): string
    {
        $from = self::formatTimeHm($this->booking_time_from);
        $to = self::formatTimeHm($this->booking_time_to);
        if ($from && $to) {
            return $from.' – '.$to;
        }
        if ($from) {
            return $from;
        }
        if ($to) {
            return $to;
        }
        $legacy = self::formatTimeHm($this->booking_time);

        return $legacy ?? '—';
    }

    /**
     * Comma-separated service names from the first booking detail row (list / dashboard).
     */
    public function servicesLineSummary(): string
    {
        $detail = $this->relationLoaded('detail')
            ? $this->detail->first()
            : $this->detail()->first();

        if (!$detail || !is_array($detail->services_json)) {
            return '—';
        }

        $names = [];
        foreach ($detail->services_json as $row) {
            if (!empty($row['name'])) {
                $names[] = (string) $row['name'];
            }
        }

        return $names !== [] ? implode(', ', $names) : '—';
    }

    public static function formatBookingStaffNames(self $booking, Collection $staffById): string
    {
        $names = [];
        foreach ($booking->staffIdList() as $sid) {
            $s = $staffById->get($sid);
            if ($s) {
                $names[] = $s->name;
            }
        }

        return $names !== [] ? implode(', ', $names) : '—';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SalonCustomer::class, 'customer_id');
    }

    public function salonTeam(): BelongsTo
    {
        return $this->belongsTo(SalonTeam::class, 'team_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(SalonStaff::class, 'staff_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(SaloonBookingDetail::class, 'saloon_booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaloonBookingPayment::class, 'saloon_booking_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SaloonBookingHistory::class, 'saloon_booking_id');
    }
}
