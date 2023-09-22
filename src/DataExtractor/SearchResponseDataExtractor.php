<?php

namespace AlexTanVer\ElasticBundle\DataExtractor;

use AlexTanVer\ElasticBundle\DTO\TermsAggregation;

class SearchResponseDataExtractor
{
    public function getSources($searchResponse): array
    {
        return array_map(function ($item) {
            return $item['_source'];
        }, $searchResponse['hits']['hits'] ?? []);
    }

    public function getFilterTermsAggregation(array $aggs, string $aggregationName): ?array
    {
        if (!array_key_exists($aggregationName, $aggs)) {
            return null;
        }

        return [
            $aggregationName => array_map(
                fn($aggregation) => new TermsAggregation($aggregation['key'], $aggregation['doc_count']),
                $aggs[$aggregationName][$aggregationName]['buckets']
            ),
        ];
    }

    public function getTermsAggregation(array $aggs, string $aggregationName): ?array
    {
        if (!array_key_exists($aggregationName, $aggs)) {
            return null;
        }

        return [
            $aggregationName => array_map(
                fn($aggregation) => new TermsAggregation($aggregation['key'], $aggregation['doc_count']),
                $aggs[$aggregationName]['buckets']
            ),
        ];
    }


    public function getMinOrMaxAggregation(array $aggs, string $aggregationName): ?array
    {
        if (!array_key_exists($aggregationName, $aggs)) {
            return null;
        }

        return [$aggregationName => $aggs[$aggregationName]['value'] ?? null];
    }

}
