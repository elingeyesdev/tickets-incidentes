<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business.help_center_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('author_id')->nullable();
            $table->uuid('category_id');

            $table->string('title', 255);
            $table->string('excerpt', 500)->nullable();
            $table->text('content');

            $table->enum('status', ['DRAFT', 'PUBLISHED'])->default('DRAFT');
            $table->integer('views_count')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('business.companies')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('auth.users')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('article_categories')->onDelete('restrict');

            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('category_id');
            $table->index('status');
            $table->index('views_count');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business.help_center_articles');
    }
};
