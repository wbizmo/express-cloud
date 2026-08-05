<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // Insert missing permissions
        $editId = Str::ulid()->toString();
        $voidId = Str::ulid()->toString();

        DB::insert("
            INSERT OR IGNORE INTO permissions (id, name, slug, \"group\", description, created_at, updated_at)
            VALUES
                (?, 'Edit / reissue an invoice', 'sales.edit', 'Commercial', 'Edit / reissue an invoice', ?, ?),
                (?, 'Void a sale', 'sales.void', 'Commercial', 'Void a sale', ?, ?)
        ", [$editId, $now, $now, $voidId, $now, $now]);

        // Get system-owner role ID
        $roleId = DB::table('roles')->where('slug', 'system-owner')->value('id');

        // Get permission IDs
        $editPerm = DB::table('permissions')->where('slug', 'sales.edit')->value('id');
        $voidPerm = DB::table('permissions')->where('slug', 'sales.void')->value('id');

        // Assign to system-owner
        if ($roleId && $editPerm) {
            DB::insert('INSERT OR IGNORE INTO permission_role (permission_id, role_id) VALUES (?, ?)', [$editPerm, $roleId]);
        }
        if ($roleId && $voidPerm) {
            DB::insert('INSERT OR IGNORE INTO permission_role (permission_id, role_id) VALUES (?, ?)', [$voidPerm, $roleId]);
        }

        echo "Done. Permissions added and assigned to system-owner.\n";
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['sales.edit', 'sales.void'])->delete();
    }
};
