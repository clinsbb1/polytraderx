<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credentials', function (Blueprint $table) {
            $table->text('polymarket_private_key')->nullable()->after('polymarket_wallet_address');
        });
    }

    public function down(): void
    {
        Schema::table('user_credentials', function (Blueprint $table) {
            $table->dropColumn('polymarket_private_key');
        });
    }
};

