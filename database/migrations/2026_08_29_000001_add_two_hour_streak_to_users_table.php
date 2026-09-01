<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('two_hour_streak_count')->default(0)->after('streak_count');
            $table->timestamp('last_two_hour_reward_at')->nullable()->after('last_activity_at');
            $table->unsignedInteger('two_hour_earned_xp')->default(0)->after('two_hour_streak_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_hour_streak_count', 'last_two_hour_reward_at', 'two_hour_earned_xp']);
        });
    }
};
