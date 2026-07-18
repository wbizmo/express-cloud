<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class DailyOperationsDigest
{
    public function generate(CarbonImmutable $date): array
    {
        $dir = 'reports/daily/'.$date->format('Y-m-d');
        Storage::disk('local')->makeDirectory($dir);
        $sets = ['executive-summary' => $this->summary($date), 'branch-performance' => $this->branches($date), 'sales-and-payments' => $this->sales($date), 'inventory-movements' => $this->inventory($date), 'purchases-and-suppliers' => $this->purchases($date), 'audit-activity' => $this->audit($date)];
        $files = [];
        foreach ($sets as $name => $rows) {
            $path = $dir.'/'.$name.'-'.$date->format('Y-m-d').'.csv';
            $this->csv($path, $rows);
            $files[] = $path;
        }
        $html = '<h1>Previous-day operations report</h1><p><strong>Reporting date: '.$date->format('l, d F Y').'</strong></p><p>Generated '.count($files).' separate spreadsheets for branches, sales, inventory, purchases and audit activity.</p>';

        return ['files' => $files, 'summary_html' => $html];
    }

    private function summary($d): array
    {
        return [['Report Date' => $d->toDateString(), 'Sales Count' => Sale::query()->whereDate('created_at', $d)->count(), 'Sales Total' => Sale::query()->whereDate('created_at', $d)->sum('grand_total_kobo') / 100, 'Stock Movements' => StockMovement::query()->whereDate('occurred_at', $d)->count(), 'Purchases' => PurchaseOrder::query()->whereDate('created_at', $d)->count(), 'Audit Events' => AuditLog::query()->whereDate('created_at', $d)->count()]];
    }

    private function branches($d): array
    {
        return Branch::query()->orderBy('name')->get()->map(fn ($b) => ['Branch' => $b->name, 'Sales Count' => Sale::query()->where('branch_id', $b->getKey())->whereDate('created_at', $d)->count(), 'Sales Total' => Sale::query()->where('branch_id', $b->getKey())->whereDate('created_at', $d)->sum('grand_total_kobo') / 100, 'Stock Movements' => StockMovement::query()->where('branch_id', $b->getKey())->whereDate('occurred_at', $d)->count()])->all();
    }

    private function sales($d): array
    {
        return Sale::query()->with(['branch', 'customer', 'account'])->whereDate('created_at', $d)->get()->map(fn ($s) => ['Invoice' => $s->document_number ?? $s->reference ?? $s->getKey(), 'Branch' => $s->branch?->name, 'Customer' => $s->customer?->name ?? 'Walk-in Customer', 'Staff' => trim(($s->account?->first_name ?? '').' '.($s->account?->last_name ?? '')), 'Status' => $s->status, 'Total' => $s->grand_total_kobo / 100, 'Outstanding' => $s->balance_due_kobo / 100])->all();
    }

    private function inventory($d): array
    {
        return StockMovement::query()->with(['branch', 'product', 'account'])->whereDate('occurred_at', $d)->get()->map(fn ($m) => ['Branch' => $m->branch?->name, 'Product' => $m->product?->name, 'SKU' => $m->product?->sku, 'Type' => $m->movement_type, 'Quantity Delta' => $m->quantity_delta_milliunits / 1000, 'Balance After' => $m->balance_after_milliunits / 1000, 'Staff' => trim(($m->account?->first_name ?? '').' '.($m->account?->last_name ?? ''))])->all();
    }

    private function purchases($d): array
    {
        return PurchaseOrder::query()->with(['branch', 'supplier'])->whereDate('created_at', $d)->get()->map(fn ($o) => ['Order' => $o->order_number ?? $o->getKey(), 'Branch' => $o->branch?->name, 'Supplier' => $o->supplier?->name, 'Status' => $o->status, 'Total' => ($o->total_kobo ?? 0) / 100])->all();
    }

    private function audit($d): array
    {
        return AuditLog::query()->with(['branch', 'account'])->whereDate('created_at', $d)->get()->map(fn ($a) => ['Time' => $a->created_at?->toDateTimeString(), 'Branch' => $a->branch?->name, 'Staff' => trim(($a->account?->first_name ?? '').' '.($a->account?->last_name ?? '')), 'Action' => $a->action, 'Subject Type' => $a->subject_type, 'Subject ID' => $a->subject_id, 'IP' => $a->ip_address])->all();
    }

    private function csv($path, $rows): void
    {
        $f = fopen('php://temp', 'w+');
        if (! $rows) {
            fputcsv($f, ['No records']);
        } else {
            fputcsv($f, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($f, array_values($r));
            }
        }rewind($f);
        Storage::disk('local')->put($path,stream_get_contents($f) ?: '');
        fclose($f);
    }
}
