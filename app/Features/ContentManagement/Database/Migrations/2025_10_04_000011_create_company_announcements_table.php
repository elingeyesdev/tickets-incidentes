<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('author_id');

            $table->string('title', 255);
            $table->text('content');

            $table->enum('type', ['MAINTENANCE', 'INCIDENT', 'NEWS', 'ALERT']);
            $table->enum('status', ['DRAFT', 'SCHEDULED', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->jsonb('metadata')->default('{}');

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('business.companies')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('auth.users')->onDelete('cascade');

            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('type');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_announcements');
    }
};
