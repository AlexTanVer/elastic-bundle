<?php

namespace AlexTanVer\ElasticBundle\DependencyInjection;

use AlexTanVer\ElasticBundle\Configuration\ElasticIndex;
use Exception;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ElasticExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            ElasticIndex::class,
            static function (
                ChildDefinition $definition,
                ElasticIndex $attribute,
                ReflectionClass $reflector
            ): void {
                $args = [];

                $reflectionAttribute = $reflector->getAttributes(ElasticIndex::class)[0];
                $args['name']        = $reflectionAttribute->getArguments()['name'];
                $definition->addTag('elastic.index', $args);
            }
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

}
