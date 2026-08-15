<?php

namespace App\Support;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class DatabaseDialect
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function caseInsensitiveLikeOperator(): string
    {
        return self::driver() === 'pgsql' ? 'ilike' : 'like';
    }

    public static function naturalRoomOrderExpression(string $column = 'number'): string
    {
        $wrapped = DB::connection()->getQueryGrammar()->wrap($column);

        return match (self::driver()) {
            'pgsql' => "NULLIF(REGEXP_REPLACE({$wrapped}, '[^0-9]', '', 'g'), '')::integer NULLS LAST, {$wrapped}",
            'mysql', 'mariadb' => "CAST({$wrapped} AS UNSIGNED), {$wrapped}",
            'sqlite' => "CAST({$wrapped} AS INTEGER), {$wrapped}",
            default => "{$wrapped}",
        };
    }

    /**
     * @param  array<int, string>  $orderedValues
     */
    public static function orderByListExpression(string $column, array $orderedValues): string
    {
        $wrapped = DB::connection()->getQueryGrammar()->wrap($column);

        if (in_array(self::driver(), ['mysql', 'mariadb'], true)) {
            $values = collect($orderedValues)
                ->map(fn (string $value): string => DB::connection()->getPdo()->quote($value))
                ->implode(', ');

            return "FIELD({$wrapped}, {$values})";
        }

        $cases = collect($orderedValues)
            ->values()
            ->map(fn (string $value, int $index): string => 'WHEN '.DB::connection()->getPdo()->quote($value).' THEN '.($index + 1))
            ->implode(' ');

        return "CASE {$wrapped} {$cases} ELSE ".(count($orderedValues) + 1).' END';
    }

    public static function whereAnyLike(QueryBuilder|\Illuminate\Database\Eloquent\Builder $query, array $columns, string $term): void
    {
        $operator = self::caseInsensitiveLikeOperator();
        $needle = '%'.$term.'%';

        $query->where(function ($searchQuery) use ($columns, $operator, $needle): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $searchQuery->{$method}($column, $operator, $needle);
            }
        });
    }
}
