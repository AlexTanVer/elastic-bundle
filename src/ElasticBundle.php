<?php

namespace AlexTanVer\ElasticBundle;

use AlexTanVer\ElasticBundle\DependencyInjection\Compiler\ElasticIndexPass;
use AlexTanVer\ElasticBundle\Index\ElasticIndexInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ElasticBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(ElasticIndexInterface::class)->addTag('elastic.index');
        $container->addCompilerPass(new ElasticIndexPass());
    }

}
