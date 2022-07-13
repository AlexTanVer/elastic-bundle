<?php

namespace AlexTanVer\ElasticBundle\DTO;

class TermsAggregation
{
    private mixed $key;

    private int $count;

    public function __construct(mixed $key, int $count)
    {
        $this->key   = $key;
        $this->count = $count;
    }

    /**
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

}
