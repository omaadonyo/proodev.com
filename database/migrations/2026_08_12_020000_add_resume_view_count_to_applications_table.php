<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedInteger('resume_view_count')->default(0)->after('resume_path');
            $table->timestamp('last_resume_viewed_at')->nullable()->after('resume_view_count');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['resume_view_count', 'last_resume_viewed_at']);
        });
    }
};
