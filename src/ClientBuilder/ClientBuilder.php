<?php

namespace AlexTanVer\ElasticBundle\ClientBuilder;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ClientBuilder
{
    /** @var Client */
    protected $client;
    /** @var array */
    protected $hosts;
    /** @var LoggerInterface  */
    protected $logger;

    /**
     * ClientBuilder constructor.
     * @param array $hosts
     * @param LoggerInterface $logger
     */
    public function __construct(array $hosts, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->hosts  = $hosts;
    }

    public function createClient(): Client
    {
        return \Elasticsearch\ClientBuilder::create()
            ->setHosts($this->hosts)
            ->setLogger($this->logger)
            ->build();
    }

    /**
     * @return array
     */
    public function getHosts()
    {
        return $this->hosts;
    }

}
