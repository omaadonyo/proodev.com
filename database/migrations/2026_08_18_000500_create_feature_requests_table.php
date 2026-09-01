<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('votes')->default(0);
            $table->enum('status', ['pending', 'approved', 'built'])->default('pending');
            $table->unsignedInteger('admin_target')->default(0);
            $table->unsignedInteger('votes_to_build')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('built_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'votes']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_requests');
    }
};