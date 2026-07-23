<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InertiaTablePaginator
{
    public const PER_PAGE = 10;

    /**
     * Formato uniforme para tablas Inertia.
     *
     * @return array{data: list<mixed>, meta: array<string, mixed>, links: array{prev: ?string, next: ?string}}
     */
    public static function make(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => array_values($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => max(1, $paginator->lastPage()),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
