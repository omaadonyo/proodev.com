<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plagiarism_strikes', function (Blueprint $table) {
            $table->timestamp('overturned_at')->nullable()->after('notified_at');
            $table->foreignId('overturned_by')->nullable()->after('overturned_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plagiarism_strikes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overturned_by');
            $table->dropColumn('overturned_at');
        });
    }
};
