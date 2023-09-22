<?php

namespace AlexTanVer\ElasticBundle\DependencyInjection\Compiler;

use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Service\IndexService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ElasticIndexPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(ElasticIndexFactory::class);
        $indexes = $container->findTaggedServiceIds('elastic.index');

        foreach ($indexes as $index => $tags) {
            $definition->addMethodCall('addIndex', [
                new Definition(
                    class: $index,
                    arguments: [
                        $tags[0]['name'],
                        new Reference(IndexService::class)
                    ]
                ),
            ]);
        }
    }

}
