<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('plan', 30)->default('Demo');
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('next_plan', 30)->nullable();
            $table->json('devices')->nullable();
            $table->json('device_names')->nullable();
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('active');
            $table->string('activation_code')->nullable();
            $table->timestamp('activation_code_expires_at')->nullable();
            $table->string('activation_token')->nullable();
            $table->timestamp('activation_token_expires_at')->nullable();
            $table->string('password_reset_code')->nullable();
            $table->timestamp('password_reset_code_expires_at')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_token_expires_at')->nullable();
            $table->string('payment_card_last_four', 4)->nullable();
            $table->string('payment_card_name', 120)->nullable();
            $table->string('payment_card_expiry', 5)->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('watch_access_code', 6)->nullable()->index();
            $table->timestamp('watch_access_code_expires_at')->nullable();
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('relationship', 60)->nullable();
            $table->boolean('notify_sms')->default(true);
            $table->boolean('notify_call')->default(false);
            $table->boolean('notify_email')->default(false);
            $table->unsignedSmallInteger('priority')->default(1);
            $table->boolean('verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'priority']);
        });

        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 40);
            $table->string('status', 30);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('message', 500)->nullable();
            $table->json('contacts_notified')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 30);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MXN');
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_name', 120)->nullable();
            $table->string('status', 30);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_id', 30)->unique();
            $table->string('name', 50);
            $table->unsignedInteger('price_amount');
            $table->json('features');
            $table->unsignedSmallInteger('max_contacts');
            $table->timestamps();
        });

        Schema::create('site_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('hero_badge', 80)->nullable();
            $table->string('hero_title', 150)->nullable();
            $table->string('hero_subtitle', 400)->nullable();
            $table->json('features')->nullable();
            $table->json('faqs')->nullable();
            $table->string('cta_title', 150)->nullable();
            $table->string('cta_subtitle', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('contacts');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropIndex(['watch_access_code']);
            $table->dropColumn([
                'username', 'phone', 'plan', 'plan_expires_at', 'next_plan', 'devices', 'device_names', 'role', 'status',
                'activation_code', 'activation_code_expires_at', 'activation_token', 'activation_token_expires_at',
                'password_reset_code', 'password_reset_code_expires_at', 'password_reset_token',
                'password_reset_token_expires_at', 'payment_card_last_four', 'payment_card_name',
                'payment_card_expiry', 'auto_renew', 'watch_access_code', 'watch_access_code_expires_at',
            ]);
        });
    }
};
