<?php

namespace AlexTanVer\ElasticBundle\Index;


interface ElasticIndexInterface
{
    public function getName(): string;

    public function getMapping(): array;

    public function getRemoteMapping(): array;

    public function getOriginalName(): string;

    public function isCreated(): bool;

    public function create(): bool;

    public function recreate(): bool;

    public function actualMapping(): void;

    public function doc(string $id): array;

    public function search(array $search): array;

    public function persist(array &$objects): void;
}
