<?php

declare(strict_types=1);

namespace App\Support\Documents;

final readonly class OperationReportData
{
    /**
     * @param  list<array<string, scalar|null>>  $rows
     * @param  array<string, scalar|null>  $summary
     */
    public function __construct(
        public string $title,
        public string $reference,
        public string $date,
        public array $rows,
        public array $summary,
        public ?string $notes = null,
    ) {}
}
