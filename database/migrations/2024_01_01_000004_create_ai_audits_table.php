<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_audits', function (Blueprint $table) {
            $table->id();
            $table->enum('trigger', ['post_loss', 'daily_review', 'weekly_review', 'manual']);
            $table->json('losing_trade_ids');
            $table->longText('analysis');
            $table->json('suggested_fixes');
            $table->enum('status', ['pending_review', 'approved', 'rejected', 'auto_applied']);
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audits');
    }
};
