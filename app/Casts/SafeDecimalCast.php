<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast attribute to decimal string, treating null/empty/non-numeric as zero.
 * Use as SafeDecimalCast:3 (or :2) to avoid "Unable to cast value to a decimal" when DB has invalid values.
 */
class SafeDecimalCast implements CastsAttributes
{
    protected int $decimals;

    public function __construct(int $decimals = 3)
    {
        $this->decimals = $decimals;
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $this->zero();
        }
        if (is_numeric($value)) {
            return number_format((float) $value, $this->decimals, '.', '');
        }
        return $this->zero();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return number_format((float) $value, $this->decimals, '.', '');
        }
        return null;
    }

    protected function zero(): string
    {
        return number_format(0.0, $this->decimals, '.', '');
    }
}
