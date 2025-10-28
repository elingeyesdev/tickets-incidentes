<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Información base de OpenAPI para la API
 *
 * Esta clase contiene las anotaciones básicas de OpenAPI
 * para que L5-Swagger pueda generar la documentación correctamente.
 */
#[OA\Info(
    title: 'Helpdesk API - REST',
    description: 'API REST del Sistema Helpdesk. Migración de GraphQL a REST con autenticación JWT.',
    version: '1.0.0',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@helpdesk.local'
    )
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Development Server'
)]
class OpenApiInfo
{
    // Clase marcadora para anotaciones OpenAPI
    // No contiene métodos ni propiedades
}
