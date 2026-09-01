<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('credit_balance')->default(0)->after('vouch_credits');
            $table->unsignedTinyInteger('daily_evidence_count')->default(0)->after('credit_balance');
            $table->date('daily_evidence_date')->nullable()->after('daily_evidence_count');
            $table->boolean('is_verified')->default(false)->after('daily_evidence_date');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->string('short_domain')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'credit_balance',
                'daily_evidence_count',
                'daily_evidence_date',
                'is_verified',
                'verified_at',
                'short_domain',
            ]);
        });
    }
};
