<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->json('skills')->nullable();
            $table->json('technologies')->nullable();
            $table->json('matched_ids')->nullable();
            $table->boolean('include_technologies')->default(false);
            $table->timestamps();

            // One active match per recruiter.
            $table->unique('recruiter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_matches');
    }
};
