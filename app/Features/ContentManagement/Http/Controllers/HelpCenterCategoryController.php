<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Http\Resources\ArticleCategoryResource;
use App\Features\ContentManagement\Services\ArticleCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HelpCenterCategoryController extends Controller
{
    public function __construct(
        private readonly ArticleCategoryService $articleCategoryService
    ) {
    }

    public function index(): JsonResponse
    {
        $categories = $this->articleCategoryService->getAllCategories();

        return response()->json([
            'success' => true,
            'data' => ArticleCategoryResource::collection($categories),
        ], 200);
    }
}
