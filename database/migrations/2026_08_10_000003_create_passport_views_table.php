<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passport_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['passport_owner_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_views');
    }
};
