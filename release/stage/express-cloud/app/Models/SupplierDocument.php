<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierDocument extends Model
{
    use HasUlids;

    protected $table = 'supplier_documents';

    /** @var list<string> */
    protected $fillable = [
        'supplier_id',
        'supplier_bill_id',
        'uploaded_by_account_id',
        'original_filename',
        'stored_path',
        'mime_type',
        'size_bytes',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<SupplierBill, $this> */
    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }
}
