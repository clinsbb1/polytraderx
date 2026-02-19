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
            if (!Schema::hasColumn('daily_summaries', 'telegram_notified_at')) {
                $table->timestamp('telegram_notified_at')->nullable()->after('created_at');
                $table->index(['user_id', 'telegram_notified_at'], 'daily_summaries_user_notified_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('daily_summaries', 'telegram_notified_at')) {
                $table->dropIndex('daily_summaries_user_notified_idx');
                $table->dropColumn('telegram_notified_at');
            }
        });
    }
};

