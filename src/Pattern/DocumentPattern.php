<?php

namespace App\Elasticsearch\Document\SUBDIR;

class DocumentPattern
{
    /** @var string */
    public $name;

    /** @var int */
    public $age;

    /**
     * DocumentPattern constructor.
     * @param $object
     */
    public function __construct($object)
    {
        $this->name = $object['name'];
        $this->age  = $object['age'];
    }
}
