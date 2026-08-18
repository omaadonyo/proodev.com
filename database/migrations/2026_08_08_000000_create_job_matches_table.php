<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('job_posts')->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->string('recommendation', 20);
            $table->string('summary');
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->string('generated_by', 30);
            $table->timestamp('analyzed_at');
            $table->timestamps();

            $table->unique(['user_id', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_matches');
    }
};
