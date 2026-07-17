<?php

declare(strict_types=1);

namespace Tests\Unit\Accounting;

use App\Services\Accounting\JournalPoster;
use PHPUnit\Framework\TestCase;

final class JournalBalanceTest extends TestCase
{
    public function test_unbalanced_journal_is_rejected_before_database_work(): void
    {
        $reflection = new \ReflectionClass(JournalPoster::class);

        /** @var JournalPoster $poster */
        $poster = $reflection->newInstanceWithoutConstructor();

        $this->expectException(\DomainException::class);

        $poster->post(
            now(),
            'Invalid',
            [
                [
                    'account_id' => '01ACCOUNT000000000000000001',
                    'debit_kobo' => 100,
                ],
                [
                    'account_id' => '01ACCOUNT000000000000000002',
                    'credit_kobo' => 90,
                ],
            ],
        );
    }
}
