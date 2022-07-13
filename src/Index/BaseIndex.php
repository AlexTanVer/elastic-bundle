<?php

namespace AlexTanVer\ElasticBundle\Index;


use AlexTanVer\ElasticBundle\Service\IndexService;
use RuntimeException;

abstract class BaseIndex implements ElasticIndexInterface
{
    public static $name;

    private IndexService $indexService;

    public function __construct(IndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    /**
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->indexService->getIndexNameByAlias($this->getIndex());
    }

    public function isCreated(): bool
    {
        return $this->indexService->indexExist($this->getOriginalName());
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        $versionIndexName = $this->createNewVersionIndex();

        return $this->indexService->putAlias($versionIndexName, $this->getIndex());
    }

    public function recreate(): bool
    {
        $versionIndexName = $this->createNewVersionIndex();
        if ($this->indexService->reindex($this->getIndex(), $versionIndexName)) {
            $oldOriginalName = $this->getOriginalName();
            $result          = $this->indexService->putAlias($versionIndexName, $this->getIndex());
            $this->indexService->deleteIndex($oldOriginalName);

            return $result;
        }

        return false;
    }

    public function getRemoteMapping(): array
    {
        return $this->indexService->getMapping($this->getIndex());
    }

    protected function createNewVersionIndex(): string
    {
        $versionIndexName = $this->generateNewVersionIndexName();
        $result           = $this->indexService->createIndex(
            $versionIndexName,
            $this->getMapping(),
        );

        if ($result) {
            return $versionIndexName;
        }

        throw new RuntimeException("Index not created");
    }

    protected function generateNewVersionIndexName(): string
    {
        $now = new \DateTime();

        return "{$this->getIndex()}_{$now->format('Y_m_d_H_i_s')}";
    }

}
