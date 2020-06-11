<?php

namespace AlexTanVer\ElasticBundle\Index;

use AlexTanVer\ElasticBundle\ClientBuilder;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;
use AlexTanVer\ElasticBundle\Service\IndexService;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractIndex implements ElasticIndexInterface
{
    /**
     * Имя индекса в elasticsearch
     *
     * Если не указать, то имя сформируется из имени класса
     * Т.е класс SomeTestIndex будет преобразовано в some_test
     * @var string
     */
    public static $name;

    /** @var OutputInterface */
    protected $section;

    /** @var IndexService */
    protected $indexService;

    /**
     * AbstractIndex constructor.
     * @param IndexService $indexService
     */
    public function __construct(IndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    /**
     * @return bool
     */
    public function hasCustomOutput(): bool
    {
        return false;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutputSection(OutputInterface $output)
    {
        $this->section = $output;
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

    /**
     * @return bool
     * @throws \Exception
     */
    public function recreate(): bool
    {
        $versionIndexName = $this->createNewVersionIndex();
        if ($this->indexService->reindex($this->getIndex(), $versionIndexName)) {
            return $this->indexService->putAlias($versionIndexName, $this->getIndex());
        }

        return false;
    }

    /**
     * @return string
     */
    protected function createNewVersionIndex(): string
    {
        $versionIndexName = $this->generateNewVersionIndexName();
        $result           = $this->indexService->createIndex(
            $versionIndexName,
            $this->getMapping(),
            $this->getTotalFieldsLimit()
        );

        if ($result) {
            return $versionIndexName;
        }

        throw new \Exception("Index not created");
    }

    /**
     * @return string
     */
    protected function generateNewVersionIndexName(): string
    {
        $now = new \DateTime();
        return "{$this->getIndex()}_{$now->format('Y_m_d_H_i_s')}";
    }
}
