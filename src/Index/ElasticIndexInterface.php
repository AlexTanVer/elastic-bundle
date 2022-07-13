<?php

namespace AlexTanVer\ElasticBundle\Index;



interface ElasticIndexInterface
{
    public function getIndex(): string;

    public function getObjects(): iterable;

    public function getMapping(): array;

    public function getRemoteMapping(): array;

    public function getOriginalName(): string;

    public function isCreated(): bool;

    public function create(): bool;

    public function recreate(): bool;

    public function needToRemoveExcessData(): bool;
}
