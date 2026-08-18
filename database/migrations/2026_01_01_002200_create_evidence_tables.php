<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('title');
            $table->string('url', 2048);
            $table->string('source', 30)->default('web')->index();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('ai_score')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
        });

        Schema::create('evidence_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->json('technologies')->nullable();
            $table->json('engineering_areas')->nullable();
            $table->string('complexity', 20)->default('simple');
            $table->text('architecture_observations')->nullable();
            $table->json('skills')->nullable();
            $table->json('knowledge_domains')->nullable();
            $table->json('highlights')->nullable();
            $table->json('strengths')->nullable();
            $table->json('references')->nullable();
            $table->string('generated_by', 30)->default('rule-based-fallback');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('evidence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_analyses');
        Schema::dropIfExists('evidence');
    }
};
