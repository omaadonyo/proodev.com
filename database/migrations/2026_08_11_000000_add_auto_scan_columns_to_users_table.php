<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('auto_scan_enabled')->default(false)->after('short_domain');
            $table->timestamp('auto_scan_active_until')->nullable()->after('auto_scan_enabled');
            $table->timestamp('last_auto_scan_at')->nullable()->after('auto_scan_active_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'auto_scan_enabled',
                'auto_scan_active_until',
                'last_auto_scan_at',
            ]);
        });
    }
};
