<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Add yearly pricing option
            $table->decimal('yearly_price', 10, 2)->nullable()->after('price_usd');

            // Rename and expand limits
            $table->renameColumn('max_daily_trades', 'max_signals_per_day');
            $table->integer('max_ai_muscles_calls_per_day')->default(0)->after('max_signals_per_day');
            $table->integer('max_ai_brain_calls_per_day')->default(0)->after('max_ai_muscles_calls_per_day');
            $table->integer('max_ai_brain_calls_per_month')->default(0)->after('max_ai_brain_calls_per_day');

            // Feature flags
            $table->boolean('csv_export_enabled')->default(false)->after('max_ai_brain_calls_per_month');
            $table->boolean('strategy_health_metrics')->default(false)->after('csv_export_enabled');
            $table->boolean('telegram_enabled')->default(false)->after('strategy_health_metrics');
            $table->integer('historical_days')->default(7)->after('telegram_enabled');
            $table->boolean('priority_processing')->default(false)->after('historical_days');

            // Lifetime plan specific
            $table->integer('lifetime_cap')->nullable()->after('priority_processing');
            $table->integer('lifetime_sold')->default(0)->after('lifetime_cap');

            // Rename has_ai columns for consistency
            $table->renameColumn('has_ai_muscles', 'ai_muscles_enabled');
            $table->renameColumn('has_ai_brain', 'ai_brain_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'yearly_price',
                'max_ai_muscles_calls_per_day',
                'max_ai_brain_calls_per_day',
                'max_ai_brain_calls_per_month',
                'csv_export_enabled',
                'strategy_health_metrics',
                'telegram_enabled',
                'historical_days',
                'priority_processing',
                'lifetime_cap',
                'lifetime_sold',
            ]);

            $table->renameColumn('max_signals_per_day', 'max_daily_trades');
            $table->renameColumn('ai_muscles_enabled', 'has_ai_muscles');
            $table->renameColumn('ai_brain_enabled', 'has_ai_brain');
        });
    }
};
