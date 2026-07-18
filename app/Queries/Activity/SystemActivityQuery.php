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

        $actorColumn = Schema::hasColumn($table, 'actor_account_id')
            ? 'actor_account_id'
            : 'actor_id';

        $query = DB::table($table.' as activity')
            ->leftJoin('accounts as actor_account', 'actor_account.id', '=', 'activity.'.$actorColumn)
            ->leftJoin('branches as actor_branch', 'actor_branch.id', '=', 'activity.branch_id')
            ->select([
                'activity.*',
                'actor_account.first_name as actor_first_name',
                'actor_account.last_name as actor_last_name',
                'actor_branch.name as actor_branch_name',
            ]);

        if ($actor !== null) {
            $query->where('activity.'.$actorColumn, $actor);
        }

        if ($entityType !== null) {
            $query->where('activity.entity_type', $entityType);
        }

        if ($from !== null) {
            $query->whereDate('activity.created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('activity.created_at', '<=', $to);
        }

        return $query
            ->orderByDesc('activity.created_at')
            ->cursorPaginate(60);
    }
}
