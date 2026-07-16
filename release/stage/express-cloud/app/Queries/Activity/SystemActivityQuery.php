<?php

declare(strict_types=1);

namespace App\Queries\Activity;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SystemActivityQuery
{
    /** @return CursorPaginator<int, \stdClass> */
    public function run(
        ?string $actor,
        ?string $entityType,
        ?string $from,
        ?string $to,
    ): CursorPaginator {
        $table = Schema::hasTable('activity_logs')
            ? 'activity_logs'
            : 'audit_logs';

        $query = DB::table($table);

        if ($actor !== null) {
            $query->where('actor_id', $actor);
        }

        if ($entityType !== null) {
            $query->where('entity_type', $entityType);
        }

        if ($from !== null) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query
            ->orderByDesc('created_at')
            ->cursorPaginate(60);
    }
}
