<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductImportRow extends Model
{
    use HasUlids;

    protected $table = 'product_import_rows';

    /** @var list<string> */
    protected $fillable = [
        'product_import_id',
        'row_number',
        'payload',
        'errors',
        'is_valid',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'payload' => 'array',
            'errors' => 'array',
            'is_valid' => 'boolean',
            'processed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ProductImport, $this> */
    public function productImport(): BelongsTo
    {
        return $this->belongsTo(ProductImport::class);
    }
}
