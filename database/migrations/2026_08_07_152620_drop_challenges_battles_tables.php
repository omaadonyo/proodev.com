<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('battle_entries');
        Schema::dropIfExists('battles');
        Schema::dropIfExists('challenge_submissions');
        Schema::dropIfExists('challenges');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible tables are recreated by the original feature migrations.
    }
};
