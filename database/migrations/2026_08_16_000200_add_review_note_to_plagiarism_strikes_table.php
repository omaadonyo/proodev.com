<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plagiarism_strikes', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('plagiarism_strikes', function (Blueprint $table) {
            $table->dropColumn('review_note');
        });
    }
};
