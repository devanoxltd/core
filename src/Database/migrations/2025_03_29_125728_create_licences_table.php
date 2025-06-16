<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('purchase_code')->nullable();
            $table->string('type')->default('standard');
            $table->dateTime('purchase_at')->nullable();
            $table->dateTime('support_until')->nullable();
            $table->boolean('update_notification')->default(true);
            $table->boolean('is_module')->default(false);
            $table->string('module_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
