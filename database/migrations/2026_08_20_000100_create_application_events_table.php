<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Auditable hiring timeline for every application.
        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage', 40); // HiringStage value
            $table->boolean('candidate_visible')->default(true);
            $table->string('feedback_category', 60)->nullable(); // FeedbackCategory value
            $table->text('feedback_note')->nullable(); // candidate-facing optional note
            $table->json('metadata')->nullable(); // internal context, never exposed to candidates
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'created_at']);
        });

        // Employer-controlled transparency settings.
        Schema::table('companies', function (Blueprint $table) {
            $table->json('hiring_settings')->nullable()->after('plan_renews_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('hiring_settings');
        });

        Schema::dropIfExists('application_events');
    }
};