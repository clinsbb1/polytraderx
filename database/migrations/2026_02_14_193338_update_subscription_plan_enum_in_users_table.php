<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, expand the ENUM to include both old and new values
        DB::statement("ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free_trial', 'basic', 'pro', 'free', 'advanced', 'lifetime') DEFAULT 'free_trial'");

        // Now update existing users with old plan slugs to new ones
        DB::table('users')->where('subscription_plan', 'free_trial')->update(['subscription_plan' => 'free']);
        DB::table('users')->where('subscription_plan', 'basic')->update(['subscription_plan' => 'pro']);
        // 'pro' stays 'pro'

        // Finally, remove old values from ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free', 'pro', 'advanced', 'lifetime') DEFAULT 'free'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free_trial', 'basic', 'pro') DEFAULT 'free_trial'");

        // Revert data
        DB::table('users')->where('subscription_plan', 'free')->update(['subscription_plan' => 'free_trial']);
        DB::table('users')->where('subscription_plan', 'pro')->update(['subscription_plan' => 'basic']);
        DB::table('users')->where('subscription_plan', 'advanced')->update(['subscription_plan' => 'basic']);
        DB::table('users')->where('subscription_plan', 'lifetime')->update(['subscription_plan' => 'pro']);
    }
};
