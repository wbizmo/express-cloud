<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Account;
use App\Models\LisaConversation;
use Illuminate\Support\Str;

final readonly class LisaConversationEngine
{
    public function __construct(private BusinessSnapshotService $snapshots) {}

    /** @return array{reply:string,context:array<string,mixed>,snapshot_id:string,evidence_hash:string} */
    public function answer(Account $actor, LisaConversation $conversation, string $question): array
    {
        $snapshot = $this->snapshots->for($actor, is_string($conversation->branch_id) ? $conversation->branch_id : null);
        $business = (array) ($snapshot['metrics'] ?? []);
        $normalized = Str::lower(trim($question));
        $name = $actor->displayName() !== '' ? $actor->displayName() : 'there';

        if ($this->isOutsideBusinessScope($normalized)) {
            $reply = "Sorry {$name}, I can only help with Express Cloud workflows and business information within your authorised scope.";
        } else {
            $reply = match (true) {
                Str::contains($normalized, ['how do i create a sale', 'how to create a sale']) => 'Open Create Sale, select the branch, customer and products, confirm quantities and tender, then submit. Express Cloud will not mark the transaction final until stock and accounting are confirmed by the server.',
                Str::contains($normalized, ['how do i transfer stock', 'how to transfer stock']) => 'Open Inventory, choose Stock Transfer, select the source and destination warehouse, confirm availability, enter the quantity and reference, then submit.',
                Str::contains($normalized, ['today sales', "today's sales", 'revenue today']) => sprintf(
                    '%s, your authorised scope has %d sale(s) today worth %s, with %s outstanding.',
                    $name,
                    (int) data_get($business, 'today.sales_count', 0),
                    $this->money((int) data_get($business, 'today.sales_total_kobo', 0)),
                    $this->money((int) data_get($business, 'today.unpaid_total_kobo', 0)),
                ),
                Str::contains($normalized, ['low stock', 'stockout', 'zero stock']) => sprintf(
                    'Within your authorised scope, %d stock row(s) are at or below reorder level and %d are at zero.',
                    (int) data_get($business, 'inventory.low_stock_rows', 0),
                    (int) data_get($business, 'inventory.zero_stock_rows', 0),
                ),
                Str::contains($normalized, ['purchase order', 'procurement']) => sprintf(
                    'There are %d open purchase order(s) within your authorised scope.',
                    (int) data_get($business, 'procurement.open_purchase_orders', 0),
                ),
                default => "I understand your request, {$name}. Ask about sales, customers, inventory, procurement, accounting, staff performance or an Express Cloud workflow.",
            };
        }

        return [
            'reply' => $reply,
            'context' => $business,
            'snapshot_id' => (string) $snapshot['snapshot_id'],
            'evidence_hash' => (string) $snapshot['evidence_hash'],
        ];
    }

    private function isOutsideBusinessScope(string $question): bool
    {
        foreach (['politics', 'relationship advice', 'medical diagnosis', 'betting', 'football score', 'write malware', 'raw sql'] as $blocked) {
            if (str_contains($question, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function money(int $kobo): string
    {
        return '₦'.number_format($kobo / 100, 2);
    }
}
