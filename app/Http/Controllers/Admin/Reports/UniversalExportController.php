<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UniversalExportController
{
    public function sale(Sale $sale): StreamedResponse
    {
        return $this->download('sale-'.$sale->getKey().'.csv', [['Document' => $sale->document_number ?? $sale->reference ?? $sale->getKey(), 'Status' => $sale->status, 'Total' => $sale->grand_total_kobo / 100, 'Outstanding' => $sale->balance_due_kobo / 100]]);
    }

    public function movement(StockMovement $movement): StreamedResponse
    {
        return $this->download('stock-'.$movement->getKey().'.csv', [['Product' => $movement->product_id, 'Branch' => $movement->branch_id, 'Type' => $movement->movement_type, 'Delta' => $movement->quantity_delta_milliunits / 1000, 'Balance' => $movement->balance_after_milliunits / 1000]]);
    }

    public function purchase(PurchaseOrder $order): StreamedResponse
    {
        return $this->download('purchase-'.$order->getKey().'.csv', [['Order' => $order->order_number ?? $order->getKey(), 'Branch' => $order->branch_id, 'Supplier' => $order->supplier_id, 'Status' => $order->status, 'Total' => ($order->total_kobo ?? 0) / 100]]);
    }

    public function audit(Request $request): StreamedResponse
    {/** @var Account $actor */ $actor = $request->user();
        $q = AuditLog::query()->with(['branch', 'actor'])->latest('occurred_at');
        if (! $actor->is_allowed_all_branches) {
            $q->whereIn('branch_id', $actor->branches()->select('branches.id'));
        }if ($request->filled('branch')) {
            $q->where('branch_id', $request->string('branch')->toString());
        }$rows = $q->limit(10000)->get()->map(fn ($a) => ['Time' => $a->occurred_at?->toDateTimeString(), 'Branch' => $a->branch?->name, 'Staff' => $a->actor_name ?? trim((($a->actor?->first_name ?? '').' '.($a->actor?->last_name ?? ''))), 'Action' => $a->action, 'Subject' => $a->entity_type, 'Subject ID' => $a->entity_id, 'IP' => $a->ip_address])->all();

        return $this->download('audit-'.now()->format('Ymd-His').'.csv', $rows);
    }

    private function download($name, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $f = fopen('php://output', 'w');
            if (! $rows) {
                fputcsv($f, ['No records']);
            } else {
                fputcsv($f, array_keys($rows[0]));
                foreach ($rows as $r) {
                    fputcsv($f, array_values($r));
                }
            }fclose($f);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}
