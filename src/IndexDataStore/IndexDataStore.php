<?php

namespace AlexTanVer\ElasticBundle\IndexDataStore;

use AlexTanVer\ElasticBundle\ClientBuilder\ClientBuilder;
use AlexTanVer\ElasticBundle\Service\IndexService;
use DateTimeImmutable;
use Elastic\Elasticsearch\Client;
use Throwable;

class IndexDataStore implements IndexDataStoreInterface
{
    const SENDING_DATA_COUNT = 100;
    protected Client $client;

    public function __construct(ClientBuilder $clientBuilder)
    {
        $this->client = $clientBuilder->createClient();
    }

    public function persist(
        string $indexName,
        array &$objects
    ): void {
        $count = count($objects);

        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $document = $objects[$i];

            $data[] = [
                'index' => [
                    '_index' => $indexName,
                    '_id'    => $document['id'],
                ],
            ];

            $document[IndexService::ELASTIC_UPDATED_AT_MAPPING] = (new DateTimeImmutable())->getTimestamp();

            $data[] = $document;
            unset($document);

            if (($i > 0) && ($i % static::SENDING_DATA_COUNT == 0)) {
                $this->sendBulk(['body' => $data]);
                unset($data);
                $data = [];
            }
        }

        if (!empty($data)) {
            $this->sendBulk(['body' => $data]);
        }

        unset($objects);
        unset($data);
        gc_collect_cycles();
        gc_mem_caches();
    }

    private function sendBulk(array $params): void
    {
        try {
            $this->client->bulk($params);
            unset($params);
        } catch (Throwable $exception) {
            sleep(1);
            $this->client->bulk($params);
            unset($params);
        }
    }

}
