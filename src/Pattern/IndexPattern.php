<?php

namespace App\Elasticsearch\Index\SUBDIR;

use AlexTanVer\ElasticBundle\Index\AbstractIndex;
use AlexTanVer\ElasticBundle\Interfaces\DocumentBuilderInterface;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;
use AlexTanVer\ElasticBundle\Service\IndexService;

#use App\Elasticsearch\DocumentBuilder\SUBDIR\CUSTOMDOCUMENTBUILDER;
#use App\Elasticsearch\Repository\SUBDIR\CUSTOMREPOSITORY;

class IndexPattern extends AbstractIndex implements ElasticIndexInterface
{
    /**
     * Имя в elasticsearch
     * @var string
     */
    public static $name = 'INDEX_NAME';

    /** @var DocumentBuilderInterface */
    private $builder;

    public function __construct(
        CUSTOMDOCUMENTBUILDER $documentBuilder,
        IndexService $indexService
    )
    {
        parent::__construct($indexService);
        $this->builder = $documentBuilder;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return static::$name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->getIndex();
    }

    /**
     * @return DocumentBuilderInterface
     */
    public function getBuilder(): DocumentBuilderInterface
    {
        return $this->builder;
    }

    /**
     * @param int|null $maxResults
     * @param int|null $id
     * @return array
     */
    public function getObjects(int $maxResults = null, int $id = null): array
    {
        return [
            ['name' => 'alex', 'age' => 10]
        ];
    }

    /**
     * @return array
     */
    public function getMapping(): array
    {
        return [
            'name' => ['type' => 'keyword'],
            'age'  => ['type' => 'integer']
        ];
    }

    /**
     * @return int
     */
    public function getTotalFieldsLimit(): int
    {
        return 1000;
    }

    public function getRepository(): string
    {
        return CUSTOMREPOSITORY::class;
    }
}
