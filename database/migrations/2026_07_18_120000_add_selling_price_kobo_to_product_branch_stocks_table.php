<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_branch_stock', function (Blueprint $table): void {
            $table->unsignedBigInteger('selling_price_kobo')
                ->nullable()
                ->after('quantity_milliunits')
                ->comment('Optional branch selling price. Null uses the product default price.');
        });
    }

    public function down(): void
    {
        Schema::table('product_branch_stock', function (Blueprint $table): void {
            $table->dropColumn('selling_price_kobo');
        });
    }
};
