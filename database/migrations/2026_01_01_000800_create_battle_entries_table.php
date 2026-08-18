<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('submission');
            $table->text('notes')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('rank')->nullable()->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['battle_id', 'user_id']);
            $table->index(['battle_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_entries');
    }
};
