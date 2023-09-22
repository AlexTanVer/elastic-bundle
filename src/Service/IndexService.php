<?php

namespace AlexTanVer\ElasticBundle\Service;


use AlexTanVer\ElasticBundle\ClientBuilder\ClientBuilder;
use AlexTanVer\ElasticBundle\IndexDataStore\IndexDataStoreInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

class IndexService
{
    public const ELASTIC_UPDATED_AT_MAPPING = 'elastic_updated_at';

    private Client $client;
    private IndexDataStoreInterface $indexDataStore;

    public function __construct(
        ClientBuilder $clientBuilder,
        IndexDataStoreInterface $indexDataStore
    ) {
        $this->client         = $clientBuilder->createClient();
        $this->indexDataStore = $indexDataStore;
    }

    public function indexExist(string $indexName): bool
    {
        return $this->client->indices()->exists([
            'index' => $indexName,
        ])->asBool();
    }

    public function createIndex(string $indexName, array $mapping): string
    {
        $mapping = array_merge($mapping, [
            self::ELASTIC_UPDATED_AT_MAPPING => ['type' => 'integer'],
        ]);

        $this->client->indices()->create([
            'index' => $indexName,
            'body'  => [
                'mappings' => [
                    'properties' => $mapping,
                ],
            ],
        ]);

        return true;
    }

    public function deleteExcessData(string $indexName, int $timeStamp): int
    {
        $response = $this->client->deleteByQuery([
            'index'     => $indexName,
            'conflicts' => 'proceed',
            'body'      => [
                'query' => [
                    'range' => [
                        self::ELASTIC_UPDATED_AT_MAPPING => [
                            'lte' => $timeStamp,
                        ],
                    ],
                ],
            ],
        ])->asArray();

        return $response['deleted'];
    }

    public function deleteIndex(string $indexName): bool
    {
        $result = $this->client->indices()->delete(['index' => $indexName]);

        return $result->asBool();
    }

    public function putAlias(string $indexName, string $alias)
    {
        if ($this->aliasExist($alias)) {
            $oldIndexName = $this->client->indices()->getAlias([
                'name' => $alias,
            ])->asArray();

            $this->client->indices()->deleteAlias([
                'name'  => $alias,
                'index' => key($oldIndexName),
            ]);
        }

        $result = $this->client->indices()->putAlias([
            'name'  => $alias,
            'index' => $indexName,
        ])->asArray();

        return $result['acknowledged'] ?? false;
    }

    public function reindex(string $source, string $dest): bool
    {
        $result = $this->client->reindex([
            'body' => [
                'source' => [
                    'index' => $source,
                ],
                'dest'   => [
                    'index' => $dest,
                ],
            ],
        ]);

        return empty($result['failures']);
    }

    public function aliasExist(string $alias): bool
    {
        return $this->client->indices()->existsAlias([
            'name' => $alias,
        ])->asBool();
    }

    public function getIndexNameByAlias(string $alias): string
    {
        if ($this->aliasExist($alias)) {
            $oldIndexName = $this->client->indices()->getAlias([
                'name' => $alias,
            ])->asArray();

            return key($oldIndexName);
        }

        return $alias;
    }

    public function getMapping(string $indexName): array
    {
        $mapping = $this->client->indices()->getMapping(['index' => $indexName])->asArray();

        return current(current(current($mapping)));
    }

    public function compareIndexMappings($mapping1, $mapping2): array
    {
        $diff = [];

        foreach ($mapping1 as $mapping1Key => $mapping1Value) {
            if (array_key_exists($mapping1Key, $mapping2)) {
                if (is_array($mapping1Value)) {
                    if ($mapping1Value['type'] === 'object' || $mapping1Value['type'] === 'nested') {
                        $aRecursiveDiff = $this->compareIndexMappings(
                            $mapping1Value['properties'],
                            $mapping2[$mapping1Key]['properties']
                        );
                    } else {
                        $aRecursiveDiff = $this->compareIndexMappings($mapping1Value, $mapping2[$mapping1Key]);
                    }

                    if (count($aRecursiveDiff)) {
                        $diff[$mapping1Key] = $aRecursiveDiff;
                    }
                } else {
                    if ($mapping1Value != $mapping2[$mapping1Key]) {
                        $diff[$mapping1Key] = $mapping1Value;
                    }
                }
            } else {
                $diff[$mapping1Key] = $mapping1Value;
            }
        }

        return $diff;
    }

    public function persistData(
        string $indexName,
        array &$objects
    ): void {
        $this->indexDataStore->persist(
            $indexName,
            $objects
        );
    }

    public function doc(string $indexName, string $id): Elasticsearch|Promise
    {
        return $this->client->get([
            'index' => $indexName,
            'type'  => '_doc',
            'id'    => $id,
        ]);
    }

    public function search(array $search): Elasticsearch|Promise
    {
        return $this->client->search($search);
    }

}
