<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Factories;

use App\Features\ContentManagement\Models\ArticleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Features\ContentManagement\Models\ArticleCategory>
 */
class ArticleCategoryFactory extends Factory
{
    protected $model = ArticleCategory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $categories = [
            ['code' => 'ACCOUNT_PROFILE', 'name' => 'Account & Profile'],
            ['code' => 'SECURITY_PRIVACY', 'name' => 'Security & Privacy'],
            ['code' => 'BILLING_PAYMENTS', 'name' => 'Billing & Payments'],
            ['code' => 'TECHNICAL_SUPPORT', 'name' => 'Technical Support'],
        ];

        $category = $this->faker->randomElement($categories);

        // Make code unique by appending a unique number
        $uniqueCode = $category['code'] . '_' . $this->faker->unique()->numberBetween(1000, 999999);

        return [
            'code' => $uniqueCode,
            'name' => $category['name'],
            'description' => $this->faker->sentence(),
        ];
    }
}
