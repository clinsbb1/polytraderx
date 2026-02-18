<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcements', 'dashboard_until_at')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dateTime('dashboard_until_at')->nullable()->after('show_on_dashboard');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('announcements', 'dashboard_until_at')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('dashboard_until_at');
            });
        }
    }
};
