<?php

namespace AlexTanVer\ElasticBundle\Factory;


use AlexTanVer\ElasticBundle\Index\ElasticIndexInterface;

class ElasticIndexFactory
{
    /**
     * @var ElasticIndexInterface[]
     */
    private array $indexes;

    public function addIndex(ElasticIndexInterface $index): void
    {
        $this->indexes[$index->getName()] = $index;
    }

    /**
     * @return ElasticIndexInterface[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getIndexByName(string $name): ?ElasticIndexInterface
    {
        return $this->indexes[$name] ?? null;
    }

    public function getIndexByClassName(string $indexClassName): ?ElasticIndexInterface
    {
        $indexes = $this->getIndexes();

        if (!empty($indexes)) {
            foreach ($indexes as $elasticIndex) {
                if ($elasticIndex instanceof $indexClassName) {
                    return $elasticIndex;
                }
            }
        }

        return null;
    }

}
