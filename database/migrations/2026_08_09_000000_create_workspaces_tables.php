<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['owner_id', 'slug']);
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // owner | member
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
        });

        $tables = [
            'talent_pools',
            'recruiter_notes',
            'recruiter_interviews',
            'recruiter_placements',
            'talent_alerts',
            'resume_validations',
            'candidate_intelligence_reports',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('workspace_id')->nullable()->after('id')->constrained('workspaces')->nullOnDelete();
                $blueprint->index('workspace_id');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'talent_pools',
            'recruiter_notes',
            'recruiter_interviews',
            'recruiter_placements',
            'talent_alerts',
            'resume_validations',
            'candidate_intelligence_reports',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['workspace_id']);
                $blueprint->dropIndex(['workspace_id']);
                $blueprint->dropColumn('workspace_id');
            });
        }

        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
