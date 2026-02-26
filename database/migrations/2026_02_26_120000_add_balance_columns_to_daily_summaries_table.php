<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_summaries', function (Blueprint $table) {
            $table->decimal('starting_balance', 10, 2)->nullable()->after('ai_cost_usd');
            $table->decimal('ending_balance', 10, 2)->nullable()->after('starting_balance');
        });
    }

    public function down(): void
    {
        Schema::table('daily_summaries', function (Blueprint $table) {
            $table->dropColumn(['starting_balance', 'ending_balance']);
        });
    }
};
