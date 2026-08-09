<?php

declare(strict_types=1);

use Devanox\Core\Enums\Tenant\Status;
use Illuminate\Database\Eloquent\Model;
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
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();

            // Creates the correct column type (UUID vs BigInt) based on the app's User model
            /** @var class-string<Model> $userModel */
            $userModel = config('auth.providers.users.model', 'App\\Models\\User');
            $table->foreignIdFor($userModel, 'user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default(Status::Active);
            $table->json('config')->nullable();
            $table->boolean('is_self_hosted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
