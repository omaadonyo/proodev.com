<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->after('provider');
            $table->string('gateway_reference', 120)->nullable()->after('reference');
            $table->json('gateway_data')->nullable()->after('metadata');
        });

        Schema::create('payment_method_settings', function (Blueprint $table) {
            $table->id();
            $table->string('method', 30)->unique();
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_settings');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'gateway_reference', 'gateway_data']);
        });
    }
};
