<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Factories;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Features\ContentManagement\Models\HelpCenterArticle>
 */
class HelpCenterArticleFactory extends Factory
{
    protected $model = HelpCenterArticle::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'author_id' => User::factory(),
            'category_id' => ArticleCategory::factory(),
            'title' => $this->faker->sentence(),
            'excerpt' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(5, true),
            'status' => 'DRAFT',
            'views_count' => 0,
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);
    }

    /**
     * Set the article with a specific number of views.
     */
    public function withViews(int $views): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => $views,
        ]);
    }
}
