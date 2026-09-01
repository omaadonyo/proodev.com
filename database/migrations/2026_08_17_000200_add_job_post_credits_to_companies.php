<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('job_post_credits')->default(1)->after('plan');
        });

        // Existing paid companies get a starter allowance so the new
        // per-post model doesn't strand them at zero.
        DB::table('companies')
            ->whereIn('plan', ['recruiter', 'intelligence'])
            ->update(['job_post_credits' => 3]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('job_post_credits');
        });
    }
};
