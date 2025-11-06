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
use OpenApi\Attributes as OA;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {
    }

    #[OA\Get(
        path: '/api/help-center/articles',
        operationId: 'list_articles',
        description: 'List help center articles with advanced filtering, searching, sorting, and pagination. Visibility rules vary by user role: END_USER sees only PUBLISHED articles from followed companies, COMPANY_ADMIN sees all articles (PUBLISHED + DRAFT) from their company, PLATFORM_ADMIN sees all articles from all companies.',
        summary: 'List help center articles',
        tags: ['Help Center: Articles'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number for pagination (1-indexed)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of items per page (max 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100, minimum: 1)
            ),
            new OA\Parameter(
                name: 'search',
                description: 'Search term (case-insensitive) to search in title and content fields',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255)
            ),
            new OA\Parameter(
                name: 'category',
                description: 'Filter by category code (ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, TECHNICAL_SUPPORT)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['ACCOUNT_PROFILE', 'SECURITY_PRIVACY', 'BILLING_PAYMENTS', 'TECHNICAL_SUPPORT'])
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Filter by article status. Default: PUBLISHED for END_USER, ALL for COMPANY_ADMIN/PLATFORM_ADMIN',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['DRAFT', 'PUBLISHED'])
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort field and direction. Use "-" prefix for descending. Options: title, views (views_count), created_at',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['title', '-title', 'views', '-views', 'created_at', '-created_at'])
            ),
            new OA\Parameter(
                name: 'company_id',
                description: 'Filter by company ID (only for COMPANY_ADMIN of that company or PLATFORM_ADMIN). END_USER cannot use this parameter.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Articles retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'author_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                    new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
                                    new OA\Property(property: 'content', type: 'string'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED']),
                                    new OA\Property(property: 'views_count', type: 'integer', minimum: 0, example: 0),
                                    new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', minimum: 1),
                                new OA\Property(property: 'per_page', type: 'integer', minimum: 1),
                                new OA\Property(property: 'total', type: 'integer', minimum: 0),
                                new OA\Property(property: 'last_page', type: 'integer', minimum: 1),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User does not have permission to access articles from specified company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Company or category does not exist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Resource not found'),
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

    #[OA\Get(
        path: '/api/help-center/articles/{id}',
        operationId: 'view_article',
        description: 'Retrieve a single help center article by ID. Visibility rules: END_USER can only view PUBLISHED articles from companies they follow. COMPANY_ADMIN can view any article (PUBLISHED or DRAFT) from their company. PLATFORM_ADMIN can view any article from any company. Automatically increments views_count by 1 when a PUBLISHED article is viewed (DRAFT articles do not increment views_count).',
        summary: 'View a single article',
        tags: ['Help Center: Articles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Article unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Article retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Article retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED']),
                                new OA\Property(property: 'views_count', description: 'Incremented by 1 if article status is PUBLISHED', type: 'integer', minimum: 0),
                                new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User does not have permission to view this article (e.g., DRAFT article from different company, or PUBLISHED article from non-followed company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Article does not exist or has been deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article not found'),
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

    #[OA\Post(
        path: '/api/help-center/articles',
        operationId: 'create_article',
        description: 'Create a new help center article in DRAFT status. Only COMPANY_ADMIN users can create articles. Company ID is automatically inferred from JWT token. Author ID is set to the authenticated user. Articles always start in DRAFT status regardless of the action parameter. Category must be one of the 4 global categories.',
        summary: 'Create a new article',
        tags: ['Help Center: Articles'],
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Article data to create',
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'title', 'content'],
                properties: [
                    new OA\Property(property: 'category_id', description: 'Article category ID (must exist and be a global category)', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'title', description: 'Article title', type: 'string', maxLength: 255, minLength: 3),
                    new OA\Property(property: 'content', description: 'Article content (full body)', type: 'string', maxLength: 20000, minLength: 50),
                    new OA\Property(property: 'excerpt', description: 'Brief excerpt/summary (optional)', type: 'string', maxLength: 500, nullable: true),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Article created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', description: 'Set from JWT token', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', description: 'Set to authenticated user ID', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', description: 'Always DRAFT on creation', type: 'string', enum: ['DRAFT'], example: 'DRAFT'),
                                new OA\Property(property: 'views_count', description: 'Always 0 on creation', type: 'integer', example: 0),
                                new OA\Property(property: 'published_at', description: 'Always null on creation', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Only COMPANY_ADMIN can create articles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(property: 'category_id', type: 'array', items: new OA\Items(type: 'string'), example: ['The category_id field is required.']),
                                new OA\Property(property: 'title', type: 'array', items: new OA\Items(type: 'string'), example: ['The title must be at least 3 characters.']),
                                new OA\Property(property: 'content', type: 'array', items: new OA\Items(type: 'string'), example: ['The content must be at least 50 characters.']),
                                new OA\Property(property: 'excerpt', type: 'array', items: new OA\Items(type: 'string'), example: ['The excerpt must not be greater than 500 characters.']),
                            ],
                            type: 'object'
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

    #[OA\Put(
        path: '/api/help-center/articles/{id}',
        operationId: 'update_article',
        description: 'Update an existing help center article. Only COMPANY_ADMIN can update articles from their company. Articles can be updated in any status (DRAFT or PUBLISHED). For PUBLISHED articles, published_at timestamp is preserved. Views count is always preserved. Title must be unique per company. Category can be changed to any valid global category. Partial updates are supported.',
        summary: 'Update an article',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Article fields to update (all fields optional for partial updates)',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'category_id', description: 'Article category ID (optional, must exist)', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'title', description: 'Article title (optional, must be unique per company)', type: 'string', maxLength: 255, minLength: 3),
                    new OA\Property(property: 'content', description: 'Article content (optional)', type: 'string', maxLength: 20000, minLength: 50),
                    new OA\Property(property: 'excerpt', description: 'Brief excerpt/summary (optional)', type: 'string', maxLength: 500, nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Help Center: Articles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Article unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Article updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Artículo actualizado exitosamente'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED']),
                                new OA\Property(property: 'views_count', description: 'Preserved from original article', type: 'integer', minimum: 0),
                                new OA\Property(property: 'published_at', description: 'Preserved if article is PUBLISHED', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User is not COMPANY_ADMIN of the article\'s company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Article does not exist or has been deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(property: 'title', type: 'array', items: new OA\Items(type: 'string'), example: ['The title has already been taken.']),
                                new OA\Property(property: 'category_id', type: 'array', items: new OA\Items(type: 'string'), example: ['The selected category_id is invalid.']),
                            ],
                            type: 'object'
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

    #[OA\Post(
        path: '/api/help-center/articles/{id}/publish',
        operationId: 'publish_article',
        description: 'Publish a help center article from DRAFT to PUBLISHED status. Only COMPANY_ADMIN can publish articles from their company. Article must be in DRAFT status to publish. Sets published_at to current timestamp and fires ArticlePublished event. Published articles become visible to END_USERs who follow the company.',
        summary: 'Publish an article',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Help Center: Articles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Article unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Article published successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Artículo publicado exitosamente'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['PUBLISHED'], example: 'PUBLISHED'),
                                new OA\Property(property: 'views_count', type: 'integer', minimum: 0),
                                new OA\Property(property: 'published_at', description: 'Set to current timestamp', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Article is already in PUBLISHED status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article is already published'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User is not COMPANY_ADMIN of the article\'s company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Article does not exist or has been deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article not found'),
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

    #[OA\Post(
        path: '/api/help-center/articles/{id}/unpublish',
        operationId: 'unpublish_article',
        description: 'Unpublish a help center article from PUBLISHED back to DRAFT status. Only COMPANY_ADMIN can unpublish articles from their company. Article must be in PUBLISHED status to unpublish. Sets published_at to null. Views count is preserved. Unpublished articles become invisible to END_USERs.',
        summary: 'Unpublish an article',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Help Center: Articles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Article unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Article unpublished successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Artículo despublicado y regresado a borrador'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['DRAFT'], example: 'DRAFT'),
                                new OA\Property(property: 'views_count', description: 'Preserved from published article', type: 'integer', minimum: 0),
                                new OA\Property(property: 'published_at', description: 'Set to null', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Article is in DRAFT status and cannot be unpublished',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article is not published'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User is not COMPANY_ADMIN of the article\'s company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Article does not exist or has been deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article not found'),
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

    #[OA\Delete(
        path: '/api/help-center/articles/{id}',
        operationId: 'delete_article',
        description: 'Permanently delete a help center article using soft delete. Only COMPANY_ADMIN can delete articles from their company. Articles must be in DRAFT status to be deleted. PUBLISHED articles cannot be deleted and will return 403 Forbidden. DELETE is idempotent - subsequent calls to a deleted article return 404. Deleted articles are soft-deleted and can be recovered from database if needed.',
        summary: 'Delete an article',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Help Center: Articles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Article unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Article deleted successfully (soft delete)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Artículo eliminado permanentemente'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User is not COMPANY_ADMIN of the article\'s company, or article is PUBLISHED and cannot be deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'No se puede eliminar un artículo publicado'),
                        new OA\Property(property: 'code', description: 'Error code present only when trying to delete PUBLISHED article', type: 'string', example: 'CANNOT_DELETE_PUBLISHED_ARTICLE', nullable: true),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Article does not exist or has already been deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Article not found'),
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
