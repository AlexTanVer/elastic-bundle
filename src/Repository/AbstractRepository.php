<?php

namespace AlexTanVer\ElasticBundle\Repository;

use AlexTanVer\ElasticBundle\SearchResponseDataExtractor;
use Elasticsearch\Client;

class AbstractRepository
{
    /** @var Client */
    protected $client;
    /** @var SearchResponseDataExtractor */
    protected $dataExtractor;

    /**
     * AbstractRepository constructor.
     * @param Client $client
     */
    public function __construct(Client $client, SearchResponseDataExtractor $dataExtractor)
    {
        $this->client        = $client;
        $this->dataExtractor = $dataExtractor;
    }
}
