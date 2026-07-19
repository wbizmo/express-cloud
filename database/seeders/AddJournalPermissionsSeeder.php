<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

final class AddJournalPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'accounting.journals.view', 'name' => 'View Journal Entries', 'group' => 'accounting'],
            ['slug' => 'accounting.journals.manage', 'name' => 'Manage Journal Entries', 'group' => 'accounting'],
        ];

        foreach ($permissions as $perm) {
            Permission::query()->updateOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'description' => $perm['name'],
                ]
            );
        }
    }
}
