<?php

namespace App\Elasticsearch\DocumentBuilder\SUBDIR;

use AlexTanVer\ElasticBundle\Interfaces\DocumentBuilderInterface;
#use App\Elasticsearch\Document\SUBDIR\DocumentPattern;

class DocumentBuilderPattern implements DocumentBuilderInterface
{
    public function create($object)
    {
        return new DocumentPattern($object);
    }
}
