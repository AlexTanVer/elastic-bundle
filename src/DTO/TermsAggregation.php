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

    public function getKey(): mixed
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }

}
