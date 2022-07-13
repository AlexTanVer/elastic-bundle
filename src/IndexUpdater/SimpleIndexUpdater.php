<?php

namespace AlexTanVer\ElasticBundle\IndexUpdater;

use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Index\ElasticIndexInterface;
use AlexTanVer\ElasticBundle\IndexDataStore\IndexDataStore;
use AlexTanVer\ElasticBundle\Service\IndexService;
use DateTimeImmutable;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SimpleIndexUpdater
{
    private ?DateTimeImmutable $currentDateTime = null;

    public function __construct(
        private readonly ElasticIndexFactory $elasticIndexFactory,
        private readonly IndexDataStore $indexDataStore,
        private readonly IndexService $indexService
    ) {
    }

    /**
     * @param string $indexAlias
     * @param OutputInterface $output
     *
     * @return void
     * @throws Throwable
     */
    public function update(string $indexAlias, OutputInterface $output): void
    {
        try {
            $this->currentDateTime = new DateTimeImmutable();
            sleep(1);

            $elasticIndex = $this->elasticIndexFactory->getIndexByAlias($indexAlias);
            if ($elasticIndex instanceof ElasticIndexInterface) {
                if (!$elasticIndex->isCreated()) {
                    $elasticIndex->create();

                    $output->writeln("Создан индекс '{$indexAlias}'");
                }

                $output->writeln("Начало обновления индекса {$indexAlias}");


                $remoteMapping = $elasticIndex->getRemoteMapping();

                if (!empty($this->compareIndexMappings($elasticIndex->getMapping(), $remoteMapping))) {
                    $elasticIndex->recreate();
                }

                // Обновляем данные
                $this->handleData($elasticIndex->getObjects(), $elasticIndex, $output);

                if ($elasticIndex->needToRemoveExcessData()) {
                    // Удаляем данные, которые не были обновлены
                    $deletedCount = $this->indexService->deleteExcessData(
                        $elasticIndex->getIndex(),
                        $this->currentDateTime->getTimestamp()
                    );

                    if ($deletedCount > 0) {
                        $output->writeln("Удалено данных - {$deletedCount}");
                    }
                }
            }

            gc_collect_cycles();
            gc_mem_caches();
            $output->writeln("Завершено. Индекс - {$indexAlias}");
        } catch (Throwable $exception) {
            $output->writeln($exception->getMessage());
            throw $exception;
        }
    }

    private function handleData(
        $objects,
        ElasticIndexInterface $elasticIndex,
        OutputInterface $output,
    ): void {
        if (is_countable($objects)) {
            $this->indexDataStore->updateData(
                $objects,
                $elasticIndex,
                $output,
                "Индекс - {$elasticIndex->getIndex()} | Всего объектов: " . count($objects)
            );
        } else {
            $objectsCount = 0;
            foreach ($objects as $objectsArray) {
                $objectsCount += count($objectsArray);
                $this->indexDataStore->updateData(
                    $objectsArray,
                    $elasticIndex,
                    quiet: true
                );

                gc_collect_cycles();
                gc_mem_caches();
                $output->writeln("Индекс - {$elasticIndex->getIndex()} | Обновлено объектов - {$objectsCount} | Памяти используется " . (memory_get_usage(true) / 1024 / 1024) . 'MB');
            }
        }
    }


    private function compareIndexMappings($mapping1, $mapping2)
    {
        $diff = [];

        foreach ($mapping1 as $mapping1Key => $mapping1Value) {
            if (array_key_exists($mapping1Key, $mapping2)) {
                if (is_array($mapping1Value)) {
                    if ($mapping1Value['type'] === 'object') {
                        $aRecursiveDiff = $this->compareIndexMappings(
                            $mapping1Value['properties'],
                            $mapping2[$mapping1Key]['properties']
                        );
                    } else {
                        $aRecursiveDiff = $this->compareIndexMappings($mapping1Value, $mapping2[$mapping1Key]);
                    }

                    if (count($aRecursiveDiff)) {
                        $diff[$mapping1Key] = $aRecursiveDiff;
                    }
                } else {
                    if ($mapping1Value != $mapping2[$mapping1Key]) {
                        $diff[$mapping1Key] = $mapping1Value;
                    }
                }
            } else {
                $diff[$mapping1Key] = $mapping1Value;
            }
        }

        return $diff;
    }

}
