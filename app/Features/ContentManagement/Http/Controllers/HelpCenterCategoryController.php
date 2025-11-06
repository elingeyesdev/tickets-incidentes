<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Http\Resources\ArticleCategoryResource;
use App\Features\ContentManagement\Services\ArticleCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

class HelpCenterCategoryController extends Controller
{
    public function __construct(
        private readonly ArticleCategoryService $articleCategoryService
    ) {
    }

    #[OA\Get(
        path: '/api/help-center/categories',
        operationId: 'list_article_categories',
        description: 'Retrieve all available help center article categories. Returns the 4 global categories: ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, and TECHNICAL_SUPPORT. These categories are used to organize and filter help center articles. All users (authenticated or not) can view categories to understand the available article organization structure.',
        summary: 'List all help center categories',
        tags: ['Help Center: Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', description: 'Category unique identifier', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'code', description: 'Unique category code used for filtering articles', type: 'string', enum: ['ACCOUNT_PROFILE', 'SECURITY_PRIVACY', 'BILLING_PAYMENTS', 'TECHNICAL_SUPPORT']),
                                    new OA\Property(property: 'name', description: 'Human-readable category name', type: 'string'),
                                    new OA\Property(property: 'description', description: 'Category description explaining the types of articles it contains', type: 'string', nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Unexpected error occurred',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'An unexpected error occurred'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $categories = $this->articleCategoryService->getAllCategories();

        return response()->json([
            'success' => true,
            'data' => ArticleCategoryResource::collection($categories),
        ], 200);
    }
}
