<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class DocumentBranding extends Model
{
    use HasUlids;

    protected $fillable = [
        'business_name',
        'logo_path',
        'address',
        'phone',
        'email',
        'receipt_footer',
        'document_terms',
        'updated_by_account_id',
    ];
}
