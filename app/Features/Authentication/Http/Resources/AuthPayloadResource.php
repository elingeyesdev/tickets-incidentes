<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Auth Payload Resource
 *
 * Transforma el payload de autenticación a JSON.
 * REPLICA EXACTAMENTE RegisterMutation.mapToGraphQLResponse() y LoginMutation.mapToGraphQLResponse()
 *
 * Estructura idéntica a GraphQL para que el cliente use el mismo código para ambos.
 *
 * PATRÓN: Usa nested UserAuthInfoResource para transformar usuario (como GraphQL usa resolvers).
 * No inlina toda la transformación - delega a recursos especializados.
 */
class AuthPayloadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Replicates EXACTLY what GraphQL mutations do:
     * - Tokens con camelCase
     * - refreshToken: mensaje informativo (token real en cookie)
     * - user: estructura transformada por UserAuthInfoResource
     * - sessionId: desde servicio o generado
     * - loginTimestamp: timestamp actual
     *
     * NOTA: Usa delegación a UserAuthInfoResource en lugar de inlinar transformación.
     * Esto replica el patrón de Lighthouse que usa resolvers para cada campo.
     */
    public function toArray(Request $request): array
    {
        return [
            // Tokens - EXACTAMENTE como GraphQL
            'accessToken' => $this['access_token'],
            'refreshToken' => 'Refresh token set in httpOnly cookie',
            'tokenType' => 'Bearer',
            'expiresIn' => $this['expires_in'],

            // Usuario - DELEGADO a UserAuthInfoResource (patrón de composición)
            // Esto replica como GraphQL resuelve user fields con field resolvers
            'user' => new UserAuthInfoResource($this['user']),

            // Metadata de sesión
            'sessionId' => $this['session_id'] ?? Str::uuid()->toString(),
            'loginTimestamp' => now()->toIso8601String(),
        ];
    }
}
