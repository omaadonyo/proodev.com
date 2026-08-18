<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plagiarism_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evidence_id')->nullable()->constrained('evidence')->nullOnDelete();
            $table->string('repo_owner');
            $table->string('repo_name');
            $table->string('repo_url');
            $table->unsignedTinyInteger('strike_number')->default(1);
            $table->string('action')->default('warning'); // warning | banned
            $table->text('reason');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['offender_id', 'strike_number']);
            $table->index('repo_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plagiarism_strikes');
    }
};
