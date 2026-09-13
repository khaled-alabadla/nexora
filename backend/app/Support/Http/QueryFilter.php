<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Whitelisted filter / search / sort for list endpoints (docs/API.md §6–8).
 *
 * Every column an endpoint exposes to the query string is named explicitly by
 * its controller — nothing here ever turns a request key into a raw column
 * name without the caller listing it first.
 */
final class QueryFilter
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $filterable  exact-match columns, e.g. ?status=active
     * @param  list<string>  $searchable  columns OR-LIKE'd against ?search=
     * @param  list<string>  $sortable  columns allowed in ?sort= (a leading "-" means descending)
     * @return Builder<*>
     */
    public function apply(
        Builder $query,
        array $filterable = [],
        array $searchable = [],
        array $sortable = [],
        string $defaultSort = '',
    ): Builder {
        foreach ($filterable as $column) {
            if ($this->request->filled($column)) {
                $query->where($column, $this->request->string($column)->toString());
            }
        }

        if ($searchable !== [] && $this->request->filled('search')) {
            $raw = $this->request->string('search')->toString();
            // Escape LIKE wildcards in the *value* so a literal "%"/"_" in the
            // search term isn't treated as a pattern — MySQL's default LIKE
            // escape character is backslash, which addcslashes also escapes.
            $term = '%'.addcslashes($raw, '\\%_').'%';

            $query->where(function (Builder $q) use ($searchable, $term): void {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', $term);
                }
            });
        }

        $sort = $this->request->string('sort', $defaultSort)->toString();

        if ($sort !== '') {
            $column = ltrim($sort, '-');

            if (in_array($column, $sortable, true)) {
                $query->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc');
            }
        }

        return $query;
    }

    /**
     * Clamp `?per_page=` into a sane range (docs/API.md §5).
     */
    public function perPage(int $default = 20, int $max = 100): int
    {
        return min($max, max(1, $this->request->integer('per_page', $default)));
    }
}
