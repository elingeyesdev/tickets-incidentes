<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Http\Requests\Articles\StoreArticleRequest;
use App\Features\ContentManagement\Http\Requests\Articles\UpdateArticleRequest;
use App\Features\ContentManagement\Http\Resources\ArticleResource;
use App\Features\ContentManagement\Services\ArticleService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->articleService->createArticle(
            $request->validated(),
            JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN'),
            auth()->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => ArticleResource::make($article),
        ], 201);
    }

    public function update(string $id, UpdateArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleService->updateArticle(
                $id,
                $request->validated(),
                JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')
            );

            return response()->json([
                'success' => true,
                'message' => 'Artículo actualizado exitosamente',
                'data' => ArticleResource::make($article),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}