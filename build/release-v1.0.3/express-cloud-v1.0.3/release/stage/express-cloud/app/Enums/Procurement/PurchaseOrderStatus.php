<?php

declare(strict_types=1);

namespace App\Enums\Procurement;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function receivable(): bool
    {
        return in_array(
            $this,
            [self::Approved, self::PartiallyReceived],
            true,
        );
    }
}
