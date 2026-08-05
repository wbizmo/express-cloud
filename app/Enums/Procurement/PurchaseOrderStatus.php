<?php

declare(strict_types=1);

namespace App\Enums\Procurement;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case PartiallyCancelled = 'partially_cancelled';
    case Cancelled = 'cancelled';

    public function receivable(): bool
    {
        return in_array($this, [self::Approved, self::PartiallyReceived], true);
    }

    public function editable(): bool
    {
        return in_array($this, [self::Draft, self::Approved], true);
    }

    public function closed(): bool
    {
        return in_array($this, [self::Received, self::PartiallyCancelled, self::Cancelled], true);
    }
}
