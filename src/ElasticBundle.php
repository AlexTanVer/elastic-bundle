<?php

namespace AlexTanVer\ElasticBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use AlexTanVer\ElasticBundle\Command\IndexCreateCommand;
use AlexTanVer\ElasticBundle\DependencyInjection\Compiler\ElasticIndexPass;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;

/**
 * Class ElasticBundle
 * @package AlexTanVer\ElasticBundle
 */
class ElasticBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->registerForAutoconfiguration(ElasticIndexInterface::class)->addTag('elastic.index');
        $container->addCompilerPass(new ElasticIndexPass());
    }
}
