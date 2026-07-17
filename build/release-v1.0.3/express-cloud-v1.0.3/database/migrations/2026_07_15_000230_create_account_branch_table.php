<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->boolean('is_allowed_all_branches')
                ->default(false)
                ->after('status')
                ->index();
        });

        Schema::create('account_branch', function (Blueprint $table): void {
            $table->foreignUlid('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['account_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_branch');

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('is_allowed_all_branches');
        });
    }
};
