<?php

namespace AlexTanVer\ElasticBundle\Command;

use AlexTanVer\ElasticBundle\Service\IndexService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AlexTanVer\ElasticBundle\Command\Base\BaseIndexCommand;
use AlexTanVer\ElasticBundle\ClientBuilder;
use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;

class IndexCreateCommand extends BaseIndexCommand
{
    protected static $defaultName = 'elastic:index:create';

    public function __construct(
        ElasticIndexFactory $elasticIndexFactory,
        ClientBuilder $clientBuilder,
        IndexService $indexService
    )
    {
        parent::__construct();

        $this->elasticIndexFactory = $elasticIndexFactory;
        $this->client              = $clientBuilder->createClient();
        $this->indexService        = $indexService;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('Создание индексов');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $creatingIndexes = $input->getOption('all')
            ? $this->getAvailableIndexes()
            : $input->getArgument('index');

        foreach ($creatingIndexes as $index) {
            $this->io->title("Создание {$index}");
            $elasticIndex = $this->getIndex($index);

            if ($elasticIndex instanceof ElasticIndexInterface) {
                try {
                    $created = $elasticIndex->create();

                    if (!$created) {
                        throw new \Exception("Index {$elasticIndex->getIndex()} not created");
                    }

                    $this->io->success("Индекс '{$elasticIndex->getIndex()}' успешно создан!");
                } catch (\Throwable $exception) {
                    $this->io->newLine(2);
                    $this->io->error("При создании индекса " . ucfirst($elasticIndex->getIndex()) . " произошла ошибка \n\n {$exception->getMessage()}");
                }
            } else {
                $this->io->error("Попытка создания несуществующего индекса '{$index}'");
            }
        }

        return 0;
    }

    protected function filterIndexes(array &$indexes)
    {
        $indexes = array_filter($indexes, function (ElasticIndexInterface $index) {
            return !$index->isCreated();
        });
    }
}
