<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('headline')->nullable()->after('name');
            $table->text('bio')->nullable()->after('headline');
            $table->string('location')->nullable()->after('bio');
            $table->string('timezone')->default('UTC')->after('location');
            $table->string('github_url')->nullable()->after('timezone');
            $table->string('website_url')->nullable()->after('github_url');
            $table->string('linkedin_url')->nullable()->after('website_url');
            $table->unsignedBigInteger('experience_points')->default(0)->index()->after('linkedin_url');
            $table->unsignedInteger('reputation_score')->default(0)->after('experience_points');
            $table->unsignedInteger('streak_count')->default(0)->after('experience_points');
            $table->unsignedInteger('longest_streak')->default(0)->after('streak_count');
            $table->timestamp('last_activity_at')->nullable()->after('longest_streak');
            $table->unsignedInteger('vouch_credits')->default(3)->after('last_activity_at');
            $table->boolean('is_admin')->default(false)->after('vouch_credits');
            $table->boolean('public_passport')->default(true)->after('is_admin');
            $table->json('preferences')->nullable()->after('public_passport');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['experience_points']);
            $table->dropColumn([
                'username',
                'headline',
                'bio',
                'location',
                'timezone',
                'github_url',
                'website_url',
                'linkedin_url',
                'experience_points',
                'reputation_score',
                'streak_count',
                'longest_streak',
                'last_activity_at',
                'vouch_credits',
                'is_admin',
                'public_passport',
                'preferences',
            ]);
        });
    }
};
