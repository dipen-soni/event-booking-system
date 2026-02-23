<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * CommonQueryScopes
 *
 * Reusable Eloquent query scopes for filtering and searching.
 * Use in any model that has a 'date' column and/or a 'title' column.
 *
 * Usage:
 *   Event::filterByDate('2026-03-01', '2026-06-01')->get();
 *   Event::searchByTitle('music')->get();
 */
trait CommonQueryScopes
{
    /**
     * Scope: Filter records by a date range.
     *
     * @param  Builder      $query
     * @param  string|null  $from   Start date (inclusive)
     * @param  string|null  $to     End date (inclusive)
     * @param  string       $column The date column to filter on (default: 'date')
     * @return Builder
     */
    public function scopeFilterByDate(Builder $query, ?string $from = null, ?string $to = null, string $column = 'date'): Builder
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }

        if ($to) {
            $query->where($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Scope: Search records by title (and optionally description).
     *
     * @param  Builder  $query
     * @param  string   $keyword      The keyword to search for
     * @param  bool     $includeDesc  Also search in the 'description' column? (default: true)
     * @return Builder
     */
    public function scopeSearchByTitle(Builder $query, string $keyword, bool $includeDesc = true): Builder
    {
        return $query->where(function (Builder $q) use ($keyword, $includeDesc) {
            $q->where('title', 'like', "%{$keyword}%");

            if ($includeDesc) {
                $q->orWhere('description', 'like', "%{$keyword}%");
            }
        });
    }
}
