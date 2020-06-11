<?php

namespace AlexTanVer\ElasticBundle\Interfaces;


use Symfony\Component\Console\Output\OutputInterface;

interface ElasticIndexInterface
{
    public function getIndex(): string;

    public function getType(): string;

    public function getBuilder(): DocumentBuilderInterface;

    public function getObjects(int $maxResults = null, int $id = null): iterable;

    public function getMapping(): array;

    public function getTotalFieldsLimit(): int;

    public function hasCustomOutput(): bool;

    public function setOutputSection(OutputInterface $section);

    public function getRepository(): string;

    public function getOriginalName(): string;

    public function isCreated(): bool;

    public function create(): bool;

    public function recreate(): bool;
}
