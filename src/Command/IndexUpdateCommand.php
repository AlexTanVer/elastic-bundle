<?php

namespace AlexTanVer\ElasticBundle\Command;

use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Index\ElasticIndexInterface;
use AlexTanVer\ElasticBundle\IndexUpdater\SimpleIndexUpdater;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'elastic:index:update', description: 'Обновление индекса')]
class IndexUpdateCommand extends Command
{
    private ElasticIndexFactory $elasticIndexFactory;
    private SimpleIndexUpdater $indexUpdater;
    private SymfonyStyle $io;

    public function __construct(
        ElasticIndexFactory $elasticIndexFactory,
        SimpleIndexUpdater $indexUpdater
    ) {
        parent::__construct();
        $this->elasticIndexFactory = $elasticIndexFactory;
        $this->indexUpdater        = $indexUpdater;
    }

    protected function configure()
    {
        $this->addArgument('index', InputArgument::OPTIONAL, 'Какой индекс нужно обновить');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $index = $input->getArgument('index');

        if (!$index) {
            $availableIndexes = $this->getAvailableIndexes();

            if (!empty($availableIndexes)) {
                $q = new ChoiceQuestion("Выберите индекс", $availableIndexes);
                $q->setMultiselect(false);
                $q->setErrorMessage("Значение \"%s\" некорректно. Выберите значение из списка");

                $index = $this->io->askQuestion($q);

                if ($index) {
                    $input->setArgument('index', $index);
                } else {
                    throw new RuntimeException('Необходимо указать индекс');
                }
            } else {
                $this->io->success("Все индексы уже созданы");
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $index = $this->elasticIndexFactory->getIndexByAlias($input->getArgument('index'));
        if ($index instanceof ElasticIndexInterface) {
            $this->indexUpdater->update($input->getArgument('index'), $output);
        } else {
            throw new RuntimeException("Index {$input->getArgument('index')} not found");
        }

        return 0;
    }

    protected function getAvailableIndexes(): array
    {
        $indexes          = $this->elasticIndexFactory->getIndexes();

        return array_values(array_map(fn(ElasticIndexInterface $index) => $index->getIndex(), $indexes));
    }

}
