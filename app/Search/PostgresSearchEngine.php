<?php

declare(strict_types=1);

namespace App\Search;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\DatabaseEngine;

/**
 * Native Postgres Scout engine (`SCOUT_DRIVER=pgsql`).
 *
 * Keeps the Scout seam (`Model::search()->where()->paginate()`) so the
 * Typesense driver remains a config-only flip-back: `SCOUT_DRIVER=typesense`.
 *
 * Ranking: full-text match on the auto-maintained `search_vector` column,
 * with ILIKE partial-match and pg_trgm similarity fallbacks for prefix
 * and typo queries. No external index — `update()`/`delete()` are no-ops,
 * which also keeps the Typesense index jobs harmless while dormant.
 */
final class PostgresSearchEngine extends DatabaseEngine
{
    /**
     * No external index to update — `search_vector` is a generated column.
     *
     * @param  Collection  $models
     */
    public function update($models): void {}

    /**
     * No external index to delete from.
     *
     * @param  Collection  $models
     */
    public function delete($models): void {}

    /**
     * Build the search query for the given Scout builder.
     *
     * @return EloquentBuilder<Model>
     */
    protected function buildSearchQuery(Builder $builder)
    {
        $query = method_exists($builder->model, 'newScoutQuery')
            ? $builder->model->newScoutQuery($builder)
            : $builder->model->newQuery();

        $term = trim((string) $builder->query);
        $hasTerm = $term !== '' && $term !== '*';

        if ($hasTerm) {
            $this->addTextConstraints($builder, $query, $term);
        }

        $query = $this->constrainForSoftDeletes(
            $builder, $this->addScoutWheres($builder, $query->take($builder->limit))
        );

        if ($hasTerm && empty($builder->orders)) {
            $this->orderByPostgresRelevance($builder, $query, $term);
        } else {
            foreach ($builder->orders as $order) {
                $query->orderBy($order['column'], $order['direction']);
            }
            $query->orderBy($builder->model->getTable().'.'.$builder->model->getScoutKeyName(), 'desc');
        }

        return $query;
    }

    /**
     * Full-text match with partial-match and typo-tolerance fallbacks.
     *
     * @param  EloquentBuilder<Model>  $query
     */
    private function addTextConstraints(Builder $builder, EloquentBuilder $query, string $term): void
    {
        $table = $builder->model->getTable();
        $vector = "{$table}.search_vector";
        $likeTerm = '%'.$this->escapeLike($term).'%';

        $textColumns = method_exists($builder->model, 'searchTextColumns')
            ? $builder->model->searchTextColumns()
            : [];
        $trigramColumns = method_exists($builder->model, 'searchTrigramColumns')
            ? $builder->model->searchTrigramColumns()
            : $textColumns;

        $query->where(function (EloquentBuilder $q) use ($builder, $term, $likeTerm, $vector, $textColumns, $trigramColumns): void {
            $q->whereRaw("{$vector} @@ plainto_tsquery('simple', ?)", [$term]);

            foreach ($textColumns as $column) {
                $q->orWhere($builder->model->qualifyColumn($column), 'ilike', $likeTerm);
            }

            foreach ($trigramColumns as $column) {
                $q->orWhereRaw("similarity({$builder->model->qualifyColumn($column)}, ?) > 0.3", [$term]);
            }

            if (method_exists($builder->model, 'applyScoutRelationSearch')) {
                $builder->model->applyScoutRelationSearch($q, $term);
            }
        });
    }

    /**
     * Apply Scout where clauses, mapping virtual index fields to relations.
     *
     * `categories` exists only in the Typesense schema — translate it to a
     * `whereHas` so both drivers share filter semantics.
     *
     * @param  EloquentBuilder<Model>  $query
     * @return EloquentBuilder<Model>
     */
    private function addScoutWheres(Builder $builder, EloquentBuilder $query): EloquentBuilder
    {
        return $query
            ->when(! is_null($builder->callback), function ($query) use ($builder): void {
                call_user_func($builder->callback, $query, $builder, $builder->query);
            })
            ->when(! $builder->callback && count($builder->wheres) > 0, function ($query) use ($builder): void {
                foreach ($builder->wheres as $where) {
                    if ($where['field'] === '__soft_deleted') {
                        continue;
                    }

                    if ($where['field'] === 'categories') {
                        $query->whereHas('categories', fn (EloquentBuilder $categoryQuery) => $categoryQuery
                            ->where('name', $where['operator'] ?? '=', $where['value']));

                        continue;
                    }

                    $query->where($where['field'], $where['operator'], $where['value']);
                }
            })
            ->when(! $builder->callback && count($builder->whereIns) > 0, function ($query) use ($builder): void {
                foreach ($builder->whereIns as $key => $values) {
                    $query->whereIn($key, $values);
                }
            })
            ->when(! $builder->callback && count($builder->whereNotIns) > 0, function ($query) use ($builder): void {
                foreach ($builder->whereNotIns as $key => $values) {
                    $query->whereNotIn($key, $values);
                }
            })
            ->when(! is_null($builder->queryCallback), function ($query) use ($builder): void {
                call_user_func($builder->queryCallback, $query);
            });
    }

    /**
     * Order by full-text relevance, then newest.
     *
     * @param  EloquentBuilder<Model>  $query
     */
    private function orderByPostgresRelevance(Builder $builder, EloquentBuilder $query, string $term): void
    {
        $table = $builder->model->getTable();

        $query
            ->orderByRaw("ts_rank_cd({$table}.search_vector, plainto_tsquery('simple', ?)) desc", [$term])
            ->orderBy($table.'.'.$builder->model->getScoutKeyName(), 'desc');
    }

    /**
     * Escape LIKE wildcards in the raw search term.
     */
    private function escapeLike(string $term): string
    {
        return addcslashes($term, '%_\\');
    }
}
