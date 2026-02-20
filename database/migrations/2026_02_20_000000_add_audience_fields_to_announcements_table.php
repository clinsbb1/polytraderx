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
        if (!Schema::hasColumn('announcements', 'audience_type')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('audience_type', 16)
                    ->default('all')
                    ->after('dashboard_until_at');
                $table->index('audience_type');
            });
        }

        if (!Schema::hasColumn('announcements', 'target_user_id')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->unsignedBigInteger('target_user_id')
                    ->nullable()
                    ->after('audience_type');
                $table->index('target_user_id');
                $table->foreign('target_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        DB::table('announcements')
            ->whereNull('audience_type')
            ->update(['audience_type' => 'all']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('announcements', 'target_user_id')) {
            Schema::table('announcements', function (Blueprint $table) {
                try {
                    $table->dropForeign(['target_user_id']);
                } catch (\Throwable) {
                    // Ignore if the constraint was already dropped.
                }
                try {
                    $table->dropIndex(['target_user_id']);
                } catch (\Throwable) {
                    // Ignore if the index was already dropped.
                }
                $table->dropColumn('target_user_id');
            });
        }

        if (Schema::hasColumn('announcements', 'audience_type')) {
            Schema::table('announcements', function (Blueprint $table) {
                try {
                    $table->dropIndex(['audience_type']);
                } catch (\Throwable) {
                    // Ignore if the index was already dropped.
                }
                $table->dropColumn('audience_type');
            });
        }
    }
};

