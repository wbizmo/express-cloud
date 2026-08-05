<?php

declare(strict_types=1);

namespace App\Services\Operations;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class TransactionRetrier
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(Closure $callback): mixed
    {
        $attempts = max(1, (int) config('operations.transaction_attempts', 5));
        $baseDelay = max(1, (int) config('operations.retry_base_delay_milliseconds', 20));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return DB::transaction($callback, 1);
            } catch (Throwable $exception) {
                if ($attempt >= $attempts || ! $this->isRetryable($exception)) {
                    throw $exception;
                }

                $delay = ($baseDelay * (2 ** ($attempt - 1))) + random_int(0, $baseDelay);
                usleep($delay * 1000);
            }
        }

        throw new \LogicException('The transaction retry loop terminated unexpectedly.');
    }

    private function isRetryable(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $code = (string) $exception->getCode();

        if ($exception instanceof QueryException) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');
            $driverCode = (string) ($exception->errorInfo[1] ?? '');

            if (in_array($sqlState, ['40001', '40P01'], true)) {
                return true;
            }

            if (in_array($driverCode, ['1205', '1213'], true)) {
                return true;
            }
        }

        return in_array($code, ['40001', '40P01'], true)
            || str_contains($message, 'deadlock')
            || str_contains($message, 'database is locked')
            || str_contains($message, 'lock wait timeout')
            || str_contains($message, 'serialization failure');
    }
}
