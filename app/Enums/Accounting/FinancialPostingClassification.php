<?php

declare(strict_types=1);

namespace App\Enums\Accounting;

enum FinancialPostingClassification: string
{
    case Posted = 'posted';
    case NonPosting = 'non_posting';
}
