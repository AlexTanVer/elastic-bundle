<?php

namespace AlexTanVer\ElasticBundle\IndexDataStore;

interface IndexDataStoreInterface
{
    public function persist(
        string $indexName,
        array &$objects
    ): void;

}
