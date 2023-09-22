<?php

namespace AlexTanVer\ElasticBundle\Configuration;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ElasticIndex
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
