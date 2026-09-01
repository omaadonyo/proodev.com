<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline', 180)->nullable();
            $table->text('problem');
            $table->text('solution');
            $table->longText('architecture')->nullable();
            $table->json('tech_stack')->nullable();
            $table->json('screenshots')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->json('engineering_decisions')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('ai_summary')->nullable();
            $table->string('verification_status', 20)->default('unverified')->index();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedInteger('recognition_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
