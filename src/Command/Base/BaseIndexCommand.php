<?php

namespace AlexTanVer\ElasticBundle\Command\Base;

use AlexTanVer\ElasticBundle\Service\IndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;

abstract class BaseIndexCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;

    /** @var ElasticIndexFactory */
    protected $elasticIndexFactory;

    /** @var \Elasticsearch\Client */
    protected $client;

    /** @var IndexService */
    protected $indexService;

    protected function configure()
    {
        $this
            ->addArgument('index', InputArgument::IS_ARRAY, 'Какой индекс нужно обновить')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Для всех');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $index = $input->getArgument('index');

        if (!count($index) && !$input->getOption('all')) {
            $availableIndexes = $this->getAvailableIndexes();
            if (!empty($availableIndexes)) {
                $q = new ChoiceQuestion("Выберите индекс\n Можно перечислить через запятую несколько вариантов", $availableIndexes);
                $q->setMultiselect(true);
                $q->setErrorMessage("Значение \"%s\" некорректно. Выберите значение из списка");

                $index = $this->io->askQuestion($q);

                if ($index) {
                    $input->setArgument('index', $index);
                } else {
                    throw new \Exception('Необходимо указать индекс');
                }
            } else {
                $this->io->success("Все индексы уже созданы");
            }
        }
    }

    /**
     * @param string $alias
     * @return ElasticIndexInterface|null
     */
    protected function getIndex(string $alias): ?ElasticIndexInterface
    {
        return $this->elasticIndexFactory->getIndexByAlias($alias);
    }

    /**
     * @return array
     */
    protected function getAvailableIndexes()
    {
        $indexes          = $this->elasticIndexFactory->getIndexes();
        $availableIndexes = [];

        $this->filterIndexes($indexes);
        if ($indexes) {
            foreach ($indexes as $index) {
                $indexName          = $index->isCreated() ? "{$index->getIndex()} ({$index->getOriginalName()})" : $index->getIndex();
                $availableIndexes[] = $indexName;
            }
        }

        return $availableIndexes;
    }

    /**
     * @param ElasticIndexInterface[] $indexes
     */
    protected function filterIndexes(array &$indexes)
    {
        return $indexes;
    }
}
