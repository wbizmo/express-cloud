<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Account;
use App\Models\LisaConversation;
use Illuminate\Support\Str;

final readonly class LisaConversationEngine
{
    public function __construct(private LisaBusinessContext $context) {}

    /** @return array{reply:string, context:array<string,mixed>} */
    public function answer(Account $actor, LisaConversation $conversation, string $question): array
    {
        $business = $this->context->for($actor);
        $normalized = Str::lower(trim($question));
        $name = trim(($actor->first_name ?? '').' '.($actor->last_name ?? '')) ?: 'there';

        if ($this->isOutsideBusinessScope($normalized)) {
            return [
                'reply' => "Sorry {$name}, I can only help with Express Cloud workflows and business information you are authorised to access. Please contact your manager or administrator for anything outside that scope.",
                'context' => $business,
            ];
        }

        $reply = match (true) {
            Str::contains($normalized, ['how do i create a sale', 'how to create a sale']) =>
                'Open Create Sale, select the branch you are transacting from, optionally select or add a customer, add products with the product selector or barcode scanner, confirm quantities and payment, then complete the transaction.',
            Str::contains($normalized, ['how do i transfer stock', 'how to transfer stock']) =>
                'Open Inventory, choose Stock Transfer, select the source branch, destination branch and product, verify current and projected balances, enter the quantity and reference note, then submit.',
            Str::contains($normalized, ['how do i add a customer', 'how to add a customer']) =>
                'From Create Sale or POS, use New Customer beside the customer selector. Only the name is required. A blank customer remains a walk-in sale.',
            Str::contains($normalized, ['today sales', "today's sales", 'revenue today']) =>
                sprintf(
                    '%s, your authorised scope has %d sale(s) today worth %s, with %s outstanding.',
                    $name,
                    (int) ($business['today']['sales_count'] ?? 0),
                    $this->money((int) ($business['today']['sales_total_kobo'] ?? 0)),
                    $this->money((int) ($business['today']['unpaid_total_kobo'] ?? 0)),
                ),
            Str::contains($normalized, ['low stock', 'stockout', 'zero stock']) =>
                sprintf(
                    'Within your authorised branches, %d stock row(s) are at or below reorder level and %d are at zero.',
                    (int) ($business['inventory']['low_stock_rows'] ?? 0),
                    (int) ($business['inventory']['zero_stock_rows'] ?? 0),
                ),
            Str::contains($normalized, ['purchase order', 'purchases', 'procurement']) =>
                sprintf(
                    'There are %d open purchase order(s) within your authorised branch scope. Use Purchasing to review suppliers, orders, receipts and direct purchases.',
                    (int) ($business['procurement']['open_purchase_orders'] ?? 0),
                ),
            default =>
                "I understand your request, {$name}. I can answer questions about sales, customers, inventory, purchasing, reports, staff performance and how to operate Express Cloud. Ask for a metric, branch comparison or step-by-step workflow.",
        };

        return ['reply' => $reply, 'context' => $business];
    }

    private function isOutsideBusinessScope(string $question): bool
    {
        foreach (['politics', 'relationship advice', 'medical diagnosis', 'betting', 'football score', 'write malware'] as $blocked) {
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