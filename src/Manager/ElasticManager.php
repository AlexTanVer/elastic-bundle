<?php

namespace AlexTanVer\ElasticBundle\Manager;

use AlexTanVer\ElasticBundle\ClientBuilder;
use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Repository\AbstractRepository;
use AlexTanVer\ElasticBundle\SearchResponseDataExtractor;
use App\Elasticsearch\Repository\TestRepository;
use Elasticsearch\Client;

class ElasticManager
{
    /** @var ElasticIndexFactory */
    private $elasticIndexFactory;
    /** @var ClientBuilder */
    private $clientBuilder;
    /** @var SearchResponseDataExtractor */
    private $searchResponseDataExtractor;

    /**
     * ElasticManager constructor.
     * @param ElasticIndexFactory $elasticIndexFactory
     * @param \AlexTanVer\ElasticBundle\ClientBuilder $clientBuilder
     * @param \AlexTanVer\ElasticBundle\SearchResponseDataExtractor $searchResponseDataExtractor
     */
    public function __construct(
        ElasticIndexFactory         $elasticIndexFactory,
        ClientBuilder               $clientBuilder,
        SearchResponseDataExtractor $searchResponseDataExtractor
    )
    {
        $this->elasticIndexFactory         = $elasticIndexFactory;
        $this->clientBuilder               = $clientBuilder;
        $this->searchResponseDataExtractor = $searchResponseDataExtractor;
    }

    /**
     * @param string $indexClassName
     * @return AbstractRepository
     * @throws \Exception
     */
    public function getRepository(string $indexClassName): AbstractRepository
    {
        $index = $this->elasticIndexFactory->getIndexByClassName($indexClassName);
        if ($index) {
            $repositoryClass = $index->getRepository();
            return new $repositoryClass($this->getClient(), $this->getDataExtractor());
        }

        throw new \Exception("Index with class name '{$indexClassName}' not found");
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->clientBuilder->createClient();
    }

    /**
     * @return SearchResponseDataExtractor
     */
    public function getDataExtractor(): SearchResponseDataExtractor
    {
        return $this->searchResponseDataExtractor;
    }
}
