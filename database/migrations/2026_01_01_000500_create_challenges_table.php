<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 40)->index();
            $table->string('difficulty', 20)->index();
            $table->string('summary', 220)->nullable();
            $table->longText('description');
            $table->json('requirements')->nullable();
            $table->string('starter_code_url')->nullable();
            $table->unsignedInteger('points')->default(50);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'difficulty', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
