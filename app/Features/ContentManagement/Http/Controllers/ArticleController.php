<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Http\Requests\Articles\ListArticleRequest;
use App\Features\ContentManagement\Http\Requests\Articles\StoreArticleRequest;
use App\Features\ContentManagement\Http\Requests\Articles\UpdateArticleRequest;
use App\Features\ContentManagement\Http\Resources\ArticleResource;
use App\Features\ContentManagement\Models\HelpCenterArticle;
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

    public function index(ListArticleRequest $request): JsonResponse
    {
        try {
            // Obtener filtros validados
            $filters = $request->validated();

            // El Service maneja TODA la lógica
            $articles = $this->articleService->listArticles(
                auth()->user(),
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => ArticleResource::collection($articles),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            // Convert code to integer to ensure strict comparison works
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;

            if ($statusCode === 403) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            }

            if ($statusCode === 404) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = auth()->user();

            // Llamar al service que tiene la lógica
            $article = $this->articleService->viewArticle($user, $id);

            return response()->json([
                'success' => true,
                'message' => 'Article retrieved successfully',
                'data' => ArticleResource::make($article),
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);

        } catch (\Exception $e) {
            // Convert code to integer
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;

            // Manejar códigos específicos
            if ($code === 401) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 401);
            }

            if ($code === 404) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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

    public function publish(string $article): JsonResponse
    {
        try {
            $published = $this->articleService->publishArticle(
                $article,
                JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')
            );

            return response()->json([
                'success' => true,
                'message' => 'Artículo publicado exitosamente',
                'data' => ArticleResource::make($published),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode === 403) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            }
            if ($statusCode === 400) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function unpublish(string $article): JsonResponse
    {
        try {
            $unpublished = $this->articleService->unpublishArticle(
                $article,
                JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')
            );

            return response()->json([
                'success' => true,
                'message' => 'Artículo despublicado y regresado a borrador',
                'data' => ArticleResource::make($unpublished),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode === 403) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            }
            if ($statusCode === 400) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function destroy(string $article): JsonResponse
    {
        try {
            $this->articleService->deleteArticle(
                $article,
                JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')
            );

            return response()->json([
                'success' => true,
                'message' => 'Artículo eliminado permanentemente',
            ], 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;

            if ($statusCode === 404) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            }

            if ($statusCode === 403) {
                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];

                // Para PUBLISHED: agregar código específico
                if (str_contains($e->getMessage(), 'publicado')) {
                    $response['code'] = 'CANNOT_DELETE_PUBLISHED_ARTICLE';
                }

                return response()->json($response, 403);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}