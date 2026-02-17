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
        if (!Schema::hasTable('admin_telegram_messages')) {
            return;
        }

        Schema::table('admin_telegram_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_telegram_messages', 'status')) {
                $table->string('status', 20)->default('pending')->index()->after('image_path');
            }

            if (!Schema::hasColumn('admin_telegram_messages', 'attempts')) {
                $table->unsignedSmallInteger('attempts')->default(0)->after('status');
            }

            if (!Schema::hasColumn('admin_telegram_messages', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable()->after('attempts');
            }
        });

        DB::table('admin_telegram_messages')
            ->where('success', true)
            ->update(['status' => 'sent']);

        DB::table('admin_telegram_messages')
            ->where('success', false)
            ->whereNotNull('error_message')
            ->update(['status' => 'failed']);

        // Make sent_at nullable so queued records do not look sent before processing.
        Schema::table('admin_telegram_messages', function (Blueprint $table) {
            if (Schema::hasColumn('admin_telegram_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_telegram_messages')) {
            return;
        }

        Schema::table('admin_telegram_messages', function (Blueprint $table) {
            if (Schema::hasColumn('admin_telegram_messages', 'last_attempt_at')) {
                $table->dropColumn('last_attempt_at');
            }

            if (Schema::hasColumn('admin_telegram_messages', 'attempts')) {
                $table->dropColumn('attempts');
            }

            if (Schema::hasColumn('admin_telegram_messages', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
