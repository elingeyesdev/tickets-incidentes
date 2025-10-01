<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Queries;

/**
 * BaseQuery
 *
 * Clase base para todas las queries GraphQL
 * Provee implementación dummy por defecto que retorna null
 *
 * Los resolvers específicos deben extender esta clase
 * y sobreescribir __invoke() con su lógica real
 */
abstract class BaseQuery
{
    /**
     * Resolver dummy por defecto
     *
     * Retorna null para permitir que Apollo Sandbox funcione
     * sin implementación real de backend
     *
     * @param  mixed  $root
     * @param  array<string, mixed>  $args
     * @return mixed
     */
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real en clases hijas
        // Por ahora retorna null para schema-first approach
        return null;
    }
}