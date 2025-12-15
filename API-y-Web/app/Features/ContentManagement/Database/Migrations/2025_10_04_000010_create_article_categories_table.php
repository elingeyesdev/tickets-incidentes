<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed the 4 global Help Center categories (required)
        // These categories are fixed and used across all companies
        $now = now();
        $categories = [
            [
                'id' => Str::uuid()->toString(),
                'code' => 'ACCOUNT_PROFILE',
                'name' => 'Account & Profile',
                'description' => 'Manage your account settings, profile information, and personal preferences',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'code' => 'SECURITY_PRIVACY',
                'name' => 'Security & Privacy',
                'description' => 'Learn about security features, privacy settings, and data protection',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'code' => 'BILLING_PAYMENTS',
                'name' => 'Billing & Payments',
                'description' => 'Information about billing, payments, subscriptions, and invoices',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'code' => 'TECHNICAL_SUPPORT',
                'name' => 'Technical Support',
                'description' => 'Troubleshooting guides, technical documentation, and system requirements',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('article_categories')->insert($categories);
    }

    public function down(): void
    {
        Schema::dropIfExists('article_categories');
    }
};
