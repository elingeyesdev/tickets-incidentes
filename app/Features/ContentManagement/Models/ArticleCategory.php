<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'article_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    /**
     * Get all articles that belong to this category.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(HelpCenterArticle::class, 'category_id');
    }
}
