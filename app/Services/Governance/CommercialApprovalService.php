<?php

declare(strict_types=1);

namespace App\Services\Governance;

use App\Models\Account;
use App\Models\CommercialApprovalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class CommercialApprovalService
{
    /** @param array<string, mixed> $changes */
    public function request(
        string $requestType,
        Model $subject,
        Account $actor,
        array $changes,
        string $memo,
        ?string $branchId = null,
    ): CommercialApprovalRequest {
        if (trim($memo) === '') {
            throw new \DomainException('A business memo is required for approval-controlled actions.');
        }

        return CommercialApprovalRequest::query()->create([
            'request_type' => $requestType,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'branch_id' => $branchId,
            'requested_by_account_id' => $actor->getKey(),
            'requested_changes' => $changes,
            'business_memo' => trim($memo),
            'status' => 'pending',
        ]);
    }

    public function decide(
        CommercialApprovalRequest $request,
        Account $actor,
        bool $approve,
        string $note,
    ): CommercialApprovalRequest {
        return DB::transaction(function () use ($request, $actor, $approve, $note): CommercialApprovalRequest {
            /** @var CommercialApprovalRequest $locked */
            $locked = CommercialApprovalRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'pending') {
                throw new \DomainException('This approval request has already been decided.');
            }
            if ((string) $locked->requested_by_account_id === (string) $actor->getKey()) {
                throw new \DomainException('The maker cannot approve their own request.');
            }
            if (trim($note) === '') {
                throw new \DomainException('A decision note is required.');
            }

            $locked->forceFill([
                'status' => $approve ? 'approved' : 'rejected',
                'decided_by_account_id' => $actor->getKey(),
                'decision_note' => trim($note),
                'decided_at' => now(),
            ])->save();

            return $locked;
        }, 3);
    }
}
