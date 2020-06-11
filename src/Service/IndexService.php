<?php

namespace AlexTanVer\ElasticBundle\Service;

use AlexTanVer\ElasticBundle\ClientBuilder;

class IndexService
{
    /** @var \Elasticsearch\Client */
    private $client;

    /**
     * IndexService constructor.
     * @param ClientBuilder $clientBuilder
     */
    public function __construct(ClientBuilder $clientBuilder)
    {
        $this->client = $clientBuilder->createClient();
    }

    /**
     * @param string $indexName
     * @return bool
     */
    public function indexExist(string $indexName): bool
    {
        return $this->client->indices()->exists([
            'index' => $indexName
        ]);
    }

    /**
     * @param string $indexName
     * @param array $mapping
     * @param int $totalLimit
     * @return string
     */
    public function createIndex(string $indexName, array $mapping, int $totalLimit): string
    {
        $this->client->indices()->create([
            'index' => $indexName,
            'body'  => [
                'mappings' => [
                    'properties' => $mapping
                ]
            ]
        ]);
        $this->client->indices()->putSettings([
            'index' => $indexName,
            'body'  => [
                'index' => [
                    'mapping' => [
                        'total_fields' => [
                            'limit' => $totalLimit
                        ]
                    ]
                ]
            ]
        ]);

        return true;
    }

    /**
     * @param string $indexName
     * @param string $alias
     * @return bool|mixed
     */
    public function putAlias(string $indexName, string $alias)
    {
        if ($this->aliasExist($alias)) {
            $oldIndexName = $this->client->indices()->getAlias([
                'name' => $alias
            ]);

            $this->client->indices()->deleteAlias([
                'name'  => $alias,
                'index' => key($oldIndexName)
            ]);
        }

        $result = $this->client->indices()->putAlias([
            'name'  => $alias,
            'index' => $indexName
        ]);

        return $result['acknowledged'] ?? false;
    }

    /**
     * @param string $source
     * @param string $dest
     * @return bool
     */
    public function reindex(string $source, string $dest): bool
    {
        $result = $this->client->reindex([
            'body' => [
                'source' => [
                    'index' => $source
                ],
                'dest'   => [
                    'index' => $dest
                ]
            ]
        ]);

        return empty($result['failures']);
    }

    /**
     * @return bool
     */
    public function aliasExist(string $alias): bool
    {
        return $this->client->indices()->existsAlias([
            'name' => $alias
        ]);
    }

    /**
     * @param string $alias
     * @return string
     */
    public function getIndexNameByAlias(string $alias): string
    {
        if ($this->aliasExist($alias)) {
            $oldIndexName = $this->client->indices()->getAlias([
                'name' => $alias
            ]);

            return key($oldIndexName);
        }

        return $alias;
    }
}
