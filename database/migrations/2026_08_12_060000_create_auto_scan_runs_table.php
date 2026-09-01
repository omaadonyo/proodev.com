<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_scan_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('scanned')->default(0);
            $table->unsignedInteger('new_evidence')->default(0);
            $table->unsignedInteger('new_projects')->default(0);
            $table->unsignedInteger('new_journal')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_scan_runs');
    }
};
