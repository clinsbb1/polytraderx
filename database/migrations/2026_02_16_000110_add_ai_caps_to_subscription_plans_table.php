<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('ai_monthly_token_cap')->nullable()->after('max_ai_brain_calls_per_month');
            $table->unsignedInteger('ai_brain_calls_per_day')->nullable()->after('ai_monthly_token_cap');
            $table->unsignedInteger('ai_muscles_calls_per_day')->nullable()->after('ai_brain_calls_per_day');
            $table->unsignedInteger('ai_max_tokens_per_request')->nullable()->after('ai_muscles_calls_per_day');
        });

        DB::table('subscription_plans')->where('slug', 'free')->update([
            'ai_monthly_token_cap' => 20000,
            'ai_brain_calls_per_day' => 2,
            'ai_muscles_calls_per_day' => 5,
            'ai_max_tokens_per_request' => 3500,
        ]);

        DB::table('subscription_plans')->where('slug', 'pro')->update([
            'ai_monthly_token_cap' => 150000,
            'ai_brain_calls_per_day' => 10,
            'ai_muscles_calls_per_day' => 100,
            'ai_max_tokens_per_request' => 6000,
        ]);

        DB::table('subscription_plans')->where('slug', 'advanced')->update([
            'ai_monthly_token_cap' => 400000,
            'ai_brain_calls_per_day' => 25,
            'ai_muscles_calls_per_day' => 250,
            'ai_max_tokens_per_request' => 9000,
        ]);

        DB::table('subscription_plans')->where('slug', 'lifetime')->update([
            'ai_monthly_token_cap' => 400000,
            'ai_brain_calls_per_day' => 25,
            'ai_muscles_calls_per_day' => 250,
            'ai_max_tokens_per_request' => 9000,
        ]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'ai_monthly_token_cap',
                'ai_brain_calls_per_day',
                'ai_muscles_calls_per_day',
                'ai_max_tokens_per_request',
            ]);
        });
    }
};
