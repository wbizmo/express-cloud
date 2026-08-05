<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class UnitOfMeasure extends Model
{
    use HasUlids;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'code', 'name', 'dimension', 'conversion_numerator',
        'conversion_denominator', 'decimal_places', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_numerator' => 'integer',
            'conversion_denominator' => 'integer',
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function toBase(int $quantityMilliunits): int
    {
        if ($this->conversion_denominator <= 0) {
            throw new \DomainException('Unit conversion denominator must be positive.');
        }

        return (int) round(
            $quantityMilliunits
            * $this->conversion_numerator
            / $this->conversion_denominator,
        );
    }
}
