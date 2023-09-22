<?php

namespace AlexTanVer\ElasticBundle\Index;

use AlexTanVer\ElasticBundle\Service\IndexService;
use DateTime;
use RuntimeException;

abstract class BaseIndex implements ElasticIndexInterface
{
    protected string $name;
    private IndexService $indexService;

    public function __construct(
        string $name,
        IndexService $indexService
    ) {
        $this->name         = $name;
        $this->indexService = $indexService;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOriginalName(): string
    {
        return $this->indexService->getIndexNameByAlias($this->getName());
    }

    public function isCreated(): bool
    {
        return $this->indexService->indexExist($this->getOriginalName());
    }

    public function create(): bool
    {
        $versionIndexName = $this->createNewVersionIndex();

        return $this->indexService->putAlias($versionIndexName, $this->getName());
    }

    public function recreate(): bool
    {
        $versionIndexName = $this->createNewVersionIndex();
        if ($this->indexService->reindex($this->getName(), $versionIndexName)) {
            $oldOriginalName = $this->getOriginalName();
            $result          = $this->indexService->putAlias($versionIndexName, $this->getName());
            $this->indexService->deleteIndex($oldOriginalName);

            return $result;
        }

        return false;
    }

    public function actualMapping(): void
    {
        if (!empty($this->indexService->compareIndexMappings($this->getMapping(), $this->getRemoteMapping()))) {
            $this->recreate();
        }
    }

    public function getRemoteMapping(): array
    {
        return $this->indexService->getMapping($this->getName());
    }

    public function doc(string $id): array
    {
        if (!$this->isCreated()) {
            $this->create();
        }

        return $this->indexService->doc($this->getName(), $id)->asArray();
    }

    public function search(array $search): array
    {
        if (!$this->isCreated()) {
            $this->create();
        }

        return $this->indexService->search(
            array_merge(
                $search,
                ['index' => $this->getName()],
            )
        )->asArray();
    }

    public function persist(array &$objects): void
    {
        if (!$this->isCreated()) {
            $this->create();
        }
        $this->actualMapping();

        $this->indexService->persistData(
            $this->getName(),
            $objects
        );
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
        $now = new DateTime();

        return "{$this->getName()}_{$now->format('Y_m_d_H_i_s')}";
    }

}
