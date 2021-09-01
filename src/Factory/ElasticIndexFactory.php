<?php

namespace AlexTanVer\ElasticBundle\Factory;


use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;

class ElasticIndexFactory
{
    /**
     * @var ElasticIndexInterface[]
     */
    private $indexes;

    public function addIndex(string $alias, ElasticIndexInterface $index)
    {
        $this->indexes[$alias] = $index;
    }

    /**
     * @return ElasticIndexInterface[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param string $alias
     * @return ElasticIndexInterface|null
     */
    public function getIndexByAlias(string $alias): ?ElasticIndexInterface
    {
        return $this->indexes[$alias] ?? null;
    }

    /**
     * @param string $indexClassName
     * @return ElasticIndexInterface|null
     */
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
