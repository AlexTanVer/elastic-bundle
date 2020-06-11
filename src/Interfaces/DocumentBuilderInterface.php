<?php

namespace AlexTanVer\ElasticBundle\Interfaces;

interface DocumentBuilderInterface
{
    /**
     * @param $object
     * @return mixed
     */
    public function create($object);
}
