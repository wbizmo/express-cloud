<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;

final class BranchAddressResolver
{
    /**
     * Resolve the branch whose name/address should print on a document.
     *
     * Rule, as specified by the business:
     *  - All-branch access            -> head office.
     *  - Access to more than one,
     *    but not all, branches        -> the branch the action itself
     *                                    belongs to (e.g. the sale's branch).
     *  - Anything else (access to
     *    exactly one branch, or none) -> head office.
     *
     * NOTE: the third case means a single-branch cashier's own receipts
     * print the head office address rather than their own branch's. That is
     * unusual for most retail setups, so if that is not actually the intent,
     * swap the last two branches below (single-branch -> own branch,
     * "otherwise" -> head office) rather than assume this is a mistake here.
     */
    public function resolve(Account $actor, Branch $actionBranch): Branch
    {
        if ($actor->is_allowed_all_branches) {
            return $this->headOffice($actionBranch);
        }

        $assignedCount = $actor->branches()->count();

        if ($assignedCount > 1) {
            return $actionBranch;
        }

        return $this->headOffice($actionBranch);
    }

    private function headOffice(Branch $fallback): Branch
    {
        return Branch::query()
            ->where('is_head_office', true)
            ->first() ?? $fallback;
    }
}
