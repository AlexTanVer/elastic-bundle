<?php

namespace AlexTanVer\ElasticBundle\Command;

use AlexTanVer\ElasticBundle\Service\IndexService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AlexTanVer\ElasticBundle\Command\Base\BaseIndexCommand;
use AlexTanVer\ElasticBundle\ClientBuilder;
use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;

class IndexRecreateCommand extends BaseIndexCommand
{
    protected static $defaultName = 'elastic:index:recreate';

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
        $recreatedIndexes = $input->getOption('all')
            ? $this->getAvailableIndexes()
            : $input->getArgument('index');


        foreach ($recreatedIndexes as $recreatedIndex) {
            $recreatedIndex = explode(' ', $recreatedIndex);
            $recreatedIndex = $recreatedIndex[0] ?? null;

            if ($recreatedIndex) {
                $elasticIndex = $this->getIndex($recreatedIndex);
                if ($elasticIndex->recreate()) {
                    $this->io->success("Индекс '{$elasticIndex->getIndex()}' успешно пересоздан!");

                }
            }
        }

        return 0;
    }
}
