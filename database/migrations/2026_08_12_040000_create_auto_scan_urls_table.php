<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_scan_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('status')->default('queued')->index();
            $table->timestamp('last_scanned_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'url']);
        });

        // Carry any URLs stored on the user column (from the merged launch)
        // into the new table as queued entries.
        DB::table('users')
            ->select('id', 'auto_scan_urls')
            ->whereNotNull('auto_scan_urls')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $urls = json_decode((string) $user->auto_scan_urls, true);

                    foreach (array_values(array_filter((array) $urls, 'is_string')) as $url) {
                        DB::table('auto_scan_urls')->insertOrIgnore([
                            'user_id' => $user->id,
                            'url' => trim((string) $url),
                            'status' => 'queued',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('auto_scan_urls');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('auto_scan_urls')->nullable()->after('last_auto_scan_at');
        });

        Schema::dropIfExists('auto_scan_urls');
    }
};
