<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agency workspace: candidate collections / talent pools
        Schema::create('talent_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('kind', 20)->default('collection'); // collection | shortlist | talent_pool
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->unique(['recruiter_id', 'slug']);
        });

        Schema::create('talent_pool_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_pool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('saved'); // saved | shortlisted | contacted | interviewing | offered | placed | rejected
            $table->unsignedSmallInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['talent_pool_id', 'candidate_id']);
        });

        // Shared internal notes on candidates
        Schema::create('recruiter_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('talent_pool_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_shared')->default(true);
            $table->timestamps();
        });

        // Interview + placement tracking
        Schema::create('recruiter_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_posts')->nullOnDelete();
            $table->string('status', 20)->default('scheduled'); // scheduled | completed | cancelled | no_show
            $table->timestamp('scheduled_at')->nullable();
            $table->string('mode', 20)->nullable(); // video | onsite | phone
            $table->json('guide')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();
        });

        Schema::create('recruiter_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_title')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress | placed | closed
            $table->date('placed_at')->nullable();
            $table->timestamps();
        });

        // Talent discovery alerts
        Schema::create('talent_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('criteria')->nullable();
            $table->string('frequency', 20)->default('daily'); // realtime | daily | weekly
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        // Resume vs evidence validation
        Schema::create('resume_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->text('resume_text');
            $table->json('results')->nullable();
            $table->unsignedSmallInteger('confidence')->default(0);
            $table->string('generated_by', 30)->default('rule-based');
            $table->timestamps();
        });

        // Team fit analysis profiles
        Schema::create('team_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('strengths')->nullable();
            $table->json('gaps')->nullable();
            $table->json('desired_expertise')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Cached candidate intelligence reports
        Schema::create('candidate_intelligence_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->json('report')->nullable();
            $table->string('generated_by', 30)->default('evidence-engine');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['recruiter_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_intelligence_reports');
        Schema::dropIfExists('team_profiles');
        Schema::dropIfExists('resume_validations');
        Schema::dropIfExists('talent_alerts');
        Schema::dropIfExists('recruiter_placements');
        Schema::dropIfExists('recruiter_interviews');
        Schema::dropIfExists('recruiter_notes');
        Schema::dropIfExists('talent_pool_members');
        Schema::dropIfExists('talent_pools');
    }
};
