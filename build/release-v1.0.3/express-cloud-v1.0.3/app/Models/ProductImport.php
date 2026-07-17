<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Imports\ImportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductImport extends Model
{
    use HasUlids;

    protected $table = 'product_imports';

    /** @var list<string> */
    protected $fillable = [
        'account_id',
        'original_filename',
        'stored_path',
        'error_report_path',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'created_products',
        'updated_products',
        'created_categories',
        'created_brands',
        'created_suppliers',
        'summary',
        'validated_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'created_products' => 'integer',
            'updated_products' => 'integer',
            'created_categories' => 'integer',
            'created_brands' => 'integer',
            'created_suppliers' => 'integer',
            'summary' => 'array',
            'validated_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return HasMany<ProductImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ProductImportRow::class);
    }
}
