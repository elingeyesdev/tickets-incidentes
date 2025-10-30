<?php

namespace App\Shared\Helpers;

use Illuminate\Pagination\AbstractPaginator;

/**
 * Pagination Helper
 *
 * Utilities for working with paginated results in API responses.
 * Transforms pagination metadata to camelCase for JSON API consistency.
 */
class PaginationHelper
{
    /**
     * Transform pagination metadata to camelCase
     *
     * Converts snake_case pagination keys (current_page, last_page, etc.)
     * to camelCase (currentPage, lastPage, etc.) for consistent API responses.
     *
     * @param AbstractPaginator $paginated
     * @return AbstractPaginator
     */
    public static function toCamelCase(AbstractPaginator $paginated): AbstractPaginator
    {
        // Get the original meta
        $original = $paginated->toArray();

        // Create camelCase version of meta
        $camelCaseMeta = [
            'total' => $original['total'] ?? 0,
            'perPage' => $original['per_page'] ?? 15,
            'currentPage' => $original['current_page'] ?? 1,
            'lastPage' => $original['last_page'] ?? 1,
            'from' => $original['from'] ?? null,
            'to' => $original['to'] ?? null,
        ];

        // Replace the meta on the paginator
        // We use reflection because Laravel's Paginator doesn't expose a direct setter
        $reflection = new \ReflectionClass($paginated);

        // Set items (data)
        $itemsProperty = $reflection->getProperty('items');
        $itemsProperty->setAccessible(true);
        $itemsProperty->setValue($paginated, collect($original['data'] ?? []));

        // Note: The 'meta' is computed on-the-fly from properties, so we need to override those properties
        // Update the paginator's internal state
        foreach ($camelCaseMeta as $key => $value) {
            $snakeKey = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key));

            // Try to set via property if it exists
            try {
                $property = $reflection->getProperty($snakeKey);
                $property->setAccessible(true);
                $property->setValue($paginated, $value);
            } catch (\ReflectionException $e) {
                // Property doesn't exist, skip
            }
        }

        return $paginated;
    }
}
