<?php

namespace AlexTanVer\ElasticBundle\QueryBuilder;

use stdClass;

class QueryBuilder
{
    public function buildQuery(...$predicates): array
    {
        $predicates = array_values(array_filter($predicates, fn($predicate) => !is_null($predicate)));
        if (empty($predicates)) {
            return ['match_all' => new stdClass()];
        }

        return array_reduce($predicates, function ($prev, $current) {
            $prev[key($current)] = current($current);

            return $prev;
        }, []);
    }

    public function bool(...$predicates): ?array
    {
        $predicates = array_values(array_filter($predicates, fn($predicate) => !is_null($predicate)));
        if (empty($predicates)) {
            return null;
        }

        $predicates = array_reduce($predicates, function ($prev, $current) {
            $prev[key($current)] = current($current);

            return $prev;
        }, []);

        return ['bool' => $predicates];
    }

    public function must(...$predicates): ?array
    {
        $predicates = array_values(array_filter($predicates, fn($predicate) => !is_null($predicate)));
        if (empty($predicates)) {
            return null;
        }

        return ['must' => $predicates];
    }

    public function mustNot(...$predicates): ?array
    {
        $predicates = array_values(array_filter($predicates, fn($predicate) => !is_null($predicate)));
        if (empty($predicates)) {
            return null;
        }

        return ['must_not' => $predicates];
    }

    public function term(string $field, null|string|int|bool $value): ?array
    {
        return !is_null($value) ? ['term' => [$field => $value]] : null;
    }

    public function terms(string $field, null|array $value): ?array
    {
        return !is_null($value) ? ['terms' => [$field => $value]] : null;
    }

    public function range(string $field, null|int|float|string $lte, null|int|float|string $gte): ?array
    {
        $rangeQuery = [];
        if (!is_null($lte)) {
            $rangeQuery['lte'] = $lte;
        }

        if (!is_null($gte)) {
            $rangeQuery['gte'] = $gte;
        }

        if (empty($rangeQuery)) {
            return null;
        }

        return [
            'range' => [
                $field => $rangeQuery,
            ],
        ];
    }

}
