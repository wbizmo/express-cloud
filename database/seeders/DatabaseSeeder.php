<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionBootstrapSeeder::class);
        $this->call(Sprint4EnterprisePermissionSeeder::class);
        $this->call(AddJournalPermissionsSeeder::class);
        $this->call(EnterpriseAccountingInventorySeeder::class);
    }
}
