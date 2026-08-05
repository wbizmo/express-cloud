<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PosHeldSale;
use Illuminate\Console\Command;

final class ExpireHeldPosSales extends Command
{
    protected $signature = 'pos:expire-held-sales';

    protected $description = 'Expire stale held POS carts without affecting confirmed sales.';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) config('pos.held_sale_expiry_hours', 24));
        $count = PosHeldSale::query()
            ->where('status', 'held')
            ->where('held_at', '<', $cutoff)
            ->update(['status' => 'expired', 'updated_at' => now()]);
        $this->info("Expired {$count} held POS cart(s).");

        return self::SUCCESS;
    }
}
