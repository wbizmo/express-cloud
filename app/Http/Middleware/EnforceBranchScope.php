<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Account;
use App\Services\Organisation\BranchAccess;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnforceBranchScope
{
    private const INPUT_KEYS = [
        'branch_id',
        'source_branch_id',
        'destination_branch_id',
        'from_branch_id',
        'to_branch_id',
    ];

    public function __construct(private BranchAccess $branches) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Account|null $actor */
        $actor = $request->user();

        abort_unless($actor !== null, 404);

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                $this->branches->enforceModel($actor, $parameter);
            }
        }

        foreach ($this->requestBranchIds($request->all()) as $branchId) {
            $this->branches->enforce($actor, $branchId);
        }

        return $next($request);
    }

    /** @param array<string, mixed> $payload
     * @return list<string>
     */
    private function requestBranchIds(array $payload): array
    {
        $ids = [];

        foreach ($payload as $key => $value) {
            if (in_array((string) $key, self::INPUT_KEYS, true)) {
                foreach ((array) $value as $branchId) {
                    if (is_string($branchId) && trim($branchId) !== '') {
                        $ids[] = trim($branchId);
                    }
                }
            }

            if (is_array($value)) {
                array_push($ids, ...$this->requestBranchIds($value));
            }
        }

        return array_values(array_unique($ids));
    }
}
