<?php

declare(strict_types=1);

namespace App\Enums\Accounting;

enum JournalStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
