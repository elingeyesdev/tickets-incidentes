<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Requests\FollowCompanyRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyFollowInfoResource;
use App\Features\CompanyManagement\Http\Resources\CompanyFollowResource;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * CompanyFollowerController
 *
 * Controlador REST para gestionar el seguimiento de empresas por parte de los usuarios.
 *
 * Fase 2: Métodos de LECTURA
 * - followed(): Listar empresas seguidas por el usuario autenticado
 * - isFollowing(): Verificar si el usuario autenticado sigue una empresa específica
 *
 * Fase 3: Métodos de ESCRITURA
 * - follow(): Seguir una empresa
 * - unfollow(): Dejar de seguir una empresa
 */
class CompanyFollowerController extends Controller
{
    /**
     * List companies followed by authenticated user
     */
    #[OA\Get(
        path: '/api/companies/followed',
        operationId: 'list_followed_companies',
        description: 'Returns all companies that the authenticated user is following, ordered by most recent follow first. Includes company details and user-specific metrics like ticket count.',
        summary: 'List companies followed by authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Company Followers'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of companies followed by the authenticated user',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(
                                        property: 'company',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'companyCode', type: 'string'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'logoUrl', type: 'string', format: 'uri', nullable: true),
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(property: 'followedAt', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'myTicketsCount', type: 'integer'),
                                    new OA\Property(property: 'lastTicketCreatedAt', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'hasUnreadAnnouncements', type: 'boolean'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function followed()
    {
        $follows = CompanyFollower::where('user_id', JWTHelper::getUserId())
            ->with(['company.industry'])  // Eager load company and industry para evitar N+1
            ->orderBy('followed_at', 'desc')
            ->get();

        return CompanyFollowInfoResource::collection($follows);
    }

    /**
     * Check if user follows a company
     */
    #[OA\Get(
        path: '/api/companies/{company}/is-following',
        operationId: 'check_if_following',
        description: 'Verifies if the authenticated user is currently following the specified company.',
        summary: 'Check if user follows a company',
        security: [['bearerAuth' => []]],
        tags: ['Company Followers'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'Company UUID or identifier',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Follow status retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'isFollowing', type: 'boolean'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Company not found'),
        ]
    )]
    public function isFollowing(Company $company): JsonResponse
    {
        $isFollowing = CompanyFollower::where('user_id', JWTHelper::getUserId())
            ->where('company_id', $company->id)
            ->exists();

        return response()->json([
            'data' => [
                'isFollowing' => $isFollowing,
            ],
        ]);
    }

    /**
     * Follow a company
     */
    #[OA\Post(
        path: '/api/companies/{company}/follow',
        operationId: 'follow_company',
        description: 'Allows the authenticated user to start following a company. If already following, returns current follow status. Rate limited to 20 requests per hour.',
        summary: 'Follow a company',
        security: [['bearerAuth' => []]],
        tags: ['Company Followers'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'Company UUID or identifier',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Already following the company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'company',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'companyCode', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'logoUrl', type: 'string', format: 'uri', nullable: true),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'followedAt', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 201,
                description: 'Successfully started following the company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'company',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'companyCode', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'logoUrl', type: 'string', format: 'uri', nullable: true),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'followedAt', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Company not found'),
            new OA\Response(response: 409, description: 'Already following this company'),
            new OA\Response(response: 429, description: 'Too many follow requests (rate limit exceeded)'),
        ]
    )]
    public function follow(Company $company, FollowCompanyRequest $request, CompanyFollowService $followService): JsonResponse
    {
        // Load industry relationship for CompanyMinimalResource
        $company->load('industry');

        // Llamar al Service para seguir la empresa
        $follower = $followService->follow(JWTHelper::getAuthenticatedUser(), $company);

        // Preparar datos para el Resource
        $data = [
            'success' => true,
            'message' => "Ahora sigues a {$company->name}.",
            'company' => $company,
            'followed_at' => $follower->followed_at,
        ];

        return (new CompanyFollowResource($data))->response();
    }

    /**
     * Unfollow a company
     */
    #[OA\Delete(
        path: '/api/companies/{company}/unfollow',
        operationId: 'unfollow_company',
        description: 'Allows the authenticated user to stop following a company. Returns an error if the user is not currently following the company.',
        summary: 'Unfollow a company',
        security: [['bearerAuth' => []]],
        tags: ['Company Followers'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'Company UUID or identifier',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully unfollowed the company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Company not found'),
            new OA\Response(response: 409, description: 'User is not following this company'),
        ]
    )]
    public function unfollow(Company $company, CompanyFollowService $followService): JsonResponse
    {
        // Llamar al Service para dejar de seguir
        $success = $followService->unfollow(JWTHelper::getAuthenticatedUser(), $company);

        // Preparar datos para el Resource
        $data = [
            'success' => $success,
            'message' => "Dejaste de seguir a {$company->name}.",
        ];

        return (new CompanyFollowResource($data))->response();
    }
}
