<?php

namespace AlexTanVer\ElasticBundle\IndexDataStore;

use AlexTanVer\ElasticBundle\ClientBuilder\ClientBuilder;
use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Index\ElasticIndexInterface;
use AlexTanVer\ElasticBundle\Service\IndexService;
use DateTimeImmutable;
use Elastic\Elasticsearch\Client;
use Elastic\Transport\Serializer\SerializerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class IndexDataStore
{
    const SENDING_DATA_COUNT = 100;

    protected SerializerInterface $serializer;

    protected ElasticIndexFactory $elasticIndexFactory;

    protected Client $client;

    public function __construct(
        ElasticIndexFactory $elasticIndexFactory,
        ClientBuilder $clientBuilder
    ) {
        $this->elasticIndexFactory = $elasticIndexFactory;
        $this->client              = $clientBuilder->createClient();
    }

    public function updateData(
        $objects,
        ElasticIndexInterface $elasticIndex,
        OutputInterface $output = null,
        string $messagePrefix = '',
        bool $quiet = false
    ) {
        $count = count($objects);

        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $percent = number_format($i / $count * 100, 2, ',', '');
            if (!$quiet) {
                $output->writeln("{$messagePrefix} | Percent - {$percent}");
            }

            $document = $objects[$i];

            $data[] = [
                'index' => [
                    '_index' => $elasticIndex->getIndex(),
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
        gc_collect_cycles();
        gc_mem_caches();
    }

    public function sendBulk($params): void
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
