<?php

namespace AlexTanVer\ElasticBundle\DependencyInjection\Compiler;

use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ElasticIndexPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition     = $container->findDefinition(ElasticIndexFactory::class);
        $taggedServices = $container->findTaggedServiceIds('elastic.index');

        foreach ($taggedServices as $id => $tags) {
            $indexName = $id::$name;
            if (!$indexName) {
                $indexName = explode('\\', $id);
                $indexName = $indexName[count($indexName) - 1];
                $indexName = str_replace('Index', '', $indexName);
                $indexName = $this->fromCamelCase($indexName);
            }

            $definition->addMethodCall('addIndex', [$indexName, new Reference($id)]);
        }
    }

    /**
     * @param string $input
     * @return string
     */
    private function fromCamelCase(string $input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}
