<?php

namespace AlexTanVer\ElasticBundle\QueryBuilder;

class AggsBuilder
{
    public function buildAggs(...$aggs)
    {
        return array_reduce($aggs, function ($prev, $current) {
            $prev[key($current)] = current($current);

            return $prev;
        }, []);
    }

    public function filterTerms(array $filter, string $aggsName, ?string $field = null): array
    {
        return [
            $aggsName => [
                'filter' => $filter,
                'aggs'   => [
                    $aggsName => [
                        'terms' => [
                            'field' => $field ?: $aggsName,
                            'size'  => 500,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function simpleTerms(string $aggsName, ?string $field = null)
    {
        return [
            $aggsName => [
                'terms' => [
                    'field' => $field ?: $aggsName,
                    'size'  => 500,
                ],
            ],
        ];
    }

    public function min(string $aggsName, ?string $field)
    {
        return [
            $aggsName => [
                'min' => [
                    'field' => $field ?: $aggsName,
                ],
            ],
        ];
    }

    public function max(string $aggsName, ?string $field)
    {
        return [
            $aggsName => [
                'max' => [
                    'field' => $field ?: $aggsName,
                ],
            ],
        ];
    }

}
