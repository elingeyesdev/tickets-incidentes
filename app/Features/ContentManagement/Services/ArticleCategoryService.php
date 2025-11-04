<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Services;

use App\Features\ContentManagement\Models\ArticleCategory;
use Illuminate\Support\Collection;

/**
 * ArticleCategoryService
 *
 * Handles business logic for Help Center article categories.
 * Categories are global (not company-specific) and defined in migrations.
 *
 * Feature: Content Management
 */
class ArticleCategoryService
{
    /**
     * Get all article categories in consistent order.
     *
     * Returns the 4 global article categories:
     * - ACCOUNT_PROFILE
     * - SECURITY_PRIVACY
     * - BILLING_PAYMENTS
     * - TECHNICAL_SUPPORT
     *
     * Categories are ordered by created_at (insertion order from migration)
     * to ensure consistent display order.
     *
     * @return Collection<ArticleCategory>
     * @throws \RuntimeException if query fails
     */
    public function getAllCategories(): Collection
    {
        try {
            return ArticleCategory::orderBy('created_at', 'asc')->get();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to retrieve article categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
