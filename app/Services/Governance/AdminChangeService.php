<?php

declare(strict_types=1);

namespace App\Services\Governance;

use App\Models\Account;
use App\Models\AdminChangeRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class AdminChangeService
{
    /** @var array<class-string<Model>, list<string>> */
    private const FIELDS = [
        Branch::class => ['name', 'address', 'phone', 'email', 'status'],
        Warehouse::class => ['name', 'type', 'address', 'status', 'allows_sales', 'allows_receipts'],
        Customer::class => ['name', 'phone', 'address', 'credit_limit_kobo', 'payment_terms_days', 'status'],
        PaymentMethod::class => ['name', 'method_type', 'is_active', 'is_visible_in_pos', 'requires_reference', 'ledger_account_id'],
        Department::class => ['name', 'branch_id', 'manager_account_id', 'status'],
        Employee::class => ['branch_id', 'department_id', 'job_role_id', 'employment_type', 'status'],
    ];

    /**
     * @param  class-string<Model>  $resourceType
     * @param  array<string, mixed>  $payload
     */
    public function submit(
        string $resourceType,
        ?string $resourceId,
        string $action,
        array $payload,
        Account $actor,
        string $memo,
    ): AdminChangeRequest {
        if (! isset(self::FIELDS[$resourceType])) {
            throw new \DomainException('This resource is not supported by maker-checker administration.');
        }
        if (! in_array($action, ['create', 'update', 'deactivate', 'reactivate'], true)) {
            throw new \DomainException('Unsupported maker-checker action.');
        }
        if (trim($memo) === '') {
            throw new \DomainException('A business memo is required.');
        }
        $safe = array_intersect_key($payload, array_flip(self::FIELDS[$resourceType]));

        return AdminChangeRequest::query()->create([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action' => $action,
            'payload' => $safe,
            'requested_by_account_id' => $actor->getKey(),
            'status' => 'pending',
            'business_memo' => trim($memo),
        ]);
    }

    public function decide(
        AdminChangeRequest $request,
        Account $actor,
        bool $approve,
        string $note,
    ): AdminChangeRequest {
        return DB::transaction(function () use ($request, $actor, $approve, $note): AdminChangeRequest {
            /** @var AdminChangeRequest $locked */
            $locked = AdminChangeRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new \DomainException('This change request has already been decided.');
            }
            if ((string) $locked->requested_by_account_id === (string) $actor->getKey()) {
                throw new \DomainException('The maker cannot approve their own administrative change.');
            }
            if (trim($note) === '') {
                throw new \DomainException('A decision note is required.');
            }

            if ($approve) {
                $this->apply($locked);
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

    private function apply(AdminChangeRequest $request): void
    {
        $class = $request->resource_type;
        if (! isset(self::FIELDS[$class])) {
            throw new \DomainException('Unsupported administrative resource class.');
        }
        $payload = array_intersect_key(
            is_array($request->payload) ? $request->payload : [],
            array_flip(self::FIELDS[$class]),
        );

        if ($request->action === 'create') {
            $class::query()->create($payload);

            return;
        }

        if ($request->resource_id === null) {
            throw new \DomainException('An existing resource ID is required.');
        }
        /** @var Model $model */
        $model = $class::query()->lockForUpdate()->findOrFail($request->resource_id);
        if ($request->action === 'deactivate') {
            $payload['status'] = 'inactive';
        } elseif ($request->action === 'reactivate') {
            $payload['status'] = 'active';
        }
        $model->fill($payload)->save();
    }
}
