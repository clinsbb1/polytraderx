<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_chat_id', 64)->nullable();
            $table->string('batch_id', 64)->nullable()->index();
            $table->boolean('is_broadcast')->default(false)->index();
            $table->text('message');
            $table->string('image_path')->nullable();
            $table->boolean('success')->default(false)->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_telegram_messages');
    }
};

