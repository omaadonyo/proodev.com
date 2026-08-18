<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouches', function (Blueprint $table) {
            $table->foreignId('evidence_id')->nullable()->after('skill_id')->constrained()->nullOnDelete();
            $table->string('confidence', 20)->nullable()->after('weight');
            $table->string('category', 80)->nullable()->after('confidence');
        });
    }

    public function down(): void
    {
        Schema::table('vouches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evidence_id');
            $table->dropColumn(['confidence', 'category']);
        });
    }
};
