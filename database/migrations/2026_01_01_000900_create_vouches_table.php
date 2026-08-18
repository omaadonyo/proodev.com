<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vouchee_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('weight')->default(1);
            $table->timestamps();

            $table->index(['vouchee_id', 'status']);
            $table->index(['voucher_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouches');
    }
};
