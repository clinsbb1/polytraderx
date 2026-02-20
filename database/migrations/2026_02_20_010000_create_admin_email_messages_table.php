<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_email_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('announcement_id')->nullable()->constrained('announcements')->nullOnDelete();
            $table->string('recipient_email', 320);
            $table->uuid('batch_id')->nullable()->index();
            $table->boolean('is_broadcast')->default(false)->index();
            $table->string('subject', 512);
            $table->string('headline');
            $table->json('lines');
            $table->string('action_text')->nullable();
            $table->string('action_url', 2048)->nullable();
            $table->text('small_print')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable()->index();
            $table->boolean('success')->default(false)->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['announcement_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_email_messages');
    }
};
