<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_id')->unique()->after('name');
            $table->string('timezone')->default('UTC')->after('email');
            $table->boolean('is_active')->default(true)->after('timezone');
            $table->boolean('is_superadmin')->default(false)->after('is_active');
            $table->enum('subscription_plan', ['free_trial', 'basic', 'pro'])->default('free_trial')->after('is_superadmin');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_ends_at');
            $table->boolean('onboarding_completed')->default(true)->after('trial_ends_at');
            $table->timestamp('last_bot_heartbeat')->nullable()->after('onboarding_completed');
            $table->string('avatar_url')->nullable()->after('last_bot_heartbeat');
            $table->string('telegram_chat_id')->nullable()->after('avatar_url');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_chat_id');
            $table->string('google_id')->nullable()->unique()->after('telegram_linked_at');
            $table->unsignedBigInteger('referred_by')->nullable()->after('google_id');

            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'account_id', 'timezone', 'is_active', 'is_superadmin', 'subscription_plan',
                'subscription_ends_at', 'trial_ends_at', 'onboarding_completed',
                'last_bot_heartbeat', 'avatar_url', 'telegram_chat_id', 'telegram_linked_at',
                'google_id', 'referred_by',
            ]);
        });
    }
};
