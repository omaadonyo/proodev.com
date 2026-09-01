<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repo_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('owner');
            $table->string('repo');
            $table->string('source')->default('github');
            $table->string('origin')->default('manual');
            $table->timestamps();

            $table->unique(['owner', 'repo', 'source']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repo_claims');
    }
};