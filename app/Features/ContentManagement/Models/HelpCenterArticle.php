<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpCenterArticle extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'help_center_articles';

    protected $fillable = [
        'company_id',
        'category_id',
        'author_id',
        'title',
        'excerpt',
        'content',
        'status',
        'views_count',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    protected $attributes = [
        'views_count' => 0,
        'status' => 'DRAFT',
    ];

    /**
     * Get the company that owns the article.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category that the article belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    /**
     * Get the author (user) that created the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope to filter only published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED');
    }

    /**
     * Scope to filter articles by category code.
     */
    public function scopeByCategory($query, string $categoryCode)
    {
        return $query->whereHas('category', function ($q) use ($categoryCode) {
            $q->where('code', $categoryCode);
        });
    }

    /**
     * Scope to search articles by term in title or content.
     * Uses case-insensitive search (ILIKE for PostgreSQL).
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'ILIKE', "%{$term}%")
                ->orWhere('content', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Increment the views count for the article.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get the formatted published date.
     * Returns a human-readable date format like "15 Oct 2024".
     */
    public function formattedPublishedDate(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        return $this->published_at->format('d M Y');
    }
}
