<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('purchase_code')->nullable()->index();
            $table->string('type')->default('standard');
            $table->timestamp('purchase_at')->nullable();
            $table->dateTime('support_until')->nullable();
            $table->boolean('update_notification')->default(true);
            $table->boolean('is_module')->default(false);
            $table->string('module_name')->nullable()->index();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('signature')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
