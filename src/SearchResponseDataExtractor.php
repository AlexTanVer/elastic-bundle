<?php


namespace AlexTanVer\ElasticBundle;

/**
 * Class SearchResponseDataExtractor
 * @package AlexTanVer\ElasticBundle
 */
class SearchResponseDataExtractor
{
    /**
     * @param $searchResponse
     * @return array
     */
    public function getSources($searchResponse)
    {
        return array_map(function ($item) {
            return $item['_source'];
        }, $searchResponse['hits']['hits'] ?? []);
    }
}
