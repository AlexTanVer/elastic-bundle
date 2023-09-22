<?php

namespace AlexTanVer\ElasticBundle\ClientBuilder;

use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ClientBuilder
{
    protected Client $client;

    protected array $hosts;

    protected LoggerInterface $logger;

    public function __construct(array $hosts, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->hosts  = $hosts;
    }

    public function createClient(): Client
    {
        return \Elastic\Elasticsearch\ClientBuilder::create()
            ->setHosts($this->hosts)
            ->setLogger($this->logger)
            ->build();
    }

}
