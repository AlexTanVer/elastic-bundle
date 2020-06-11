<?php

namespace AlexTanVer\ElasticBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Elasticsearch\Common\Exceptions\RuntimeException;
use JMS\Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use AlexTanVer\ElasticBundle\ClientBuilder;
use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;
use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;

class UpdateSingleIndexCommand extends Command
{
    protected static $defaultName = 'elastic:update:single:index';

    /** @var ElasticIndexFactory  */
    protected $elasticIndexFactory;

    /** @var OutputInterface */
    private $section;

    /** @var SymfonyStyle */
    protected $io;

    /** @var \Elasticsearch\Client  */
    protected $client;

    /** @var Serializer */
    private $serializer;

    private $isSimpleOut = false;
    private $errorsCount = 0;

    public function __construct(
        ElasticIndexFactory $elasticIndexFactory,
        ClientBuilder $clientBuilder,
        Serializer $serializer
    )
    {
        parent::__construct();

        $this->elasticIndexFactory = $elasticIndexFactory;
        $this->serializer          = $serializer;
        $this->client              = $clientBuilder->createClient();
    }

    protected function configure()
    {
        $this
            ->setDescription('Обновляет один индекс')
            ->addOption('simple_output', 's', InputOption::VALUE_NONE, 'Простой строковый вывод. Для лог-файла')
            ->addArgument('index', InputArgument::REQUIRED)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->section = $output->section();
        $this->io = new SymfonyStyle($input, $output);
        $this->isSimpleOut = $input->getOption('simple_output');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updatingIndex = $input->getArgument('index');

        $elasticIndex = $this->getIndex($updatingIndex);
        $elasticIndex->setOutputSection($this->section);
        if (!$elasticIndex->hasCustomOutput()) {
            $this->io->writeln(date('Y-m-d H:i:s') . ' Начало индексирования ' . $elasticIndex->getIndex());
        } else {
            $this->section->overwrite('Индексирование ' . $elasticIndex->getIndex());
        }
        if ($elasticIndex instanceof ElasticIndexInterface) {
            try {
                if (!$elasticIndex->hasCustomOutput()) {
                    $this->section->overwrite('Получение массива объектов');
                }
                $this->prepareFlag($elasticIndex->getIndex());
                $objects = $elasticIndex->getObjects(null, null);
                $this->updateIndex($objects, $elasticIndex, '', false);
                $this->clearingData($elasticIndex->getIndex());
            } catch (\Throwable $exception) {
                dd($exception->getMessage());
                $mess = 'Ошибка - ' . $exception->getMessage();
                if ($elasticIndex->hasCustomOutput()) {
                    $this->io->writeln(date('Y-m-d H:i:s') . ' Индекс ' . $elasticIndex->getIndex() . '. ' . $mess);
                } else {
                    $this->section->overwrite($mess);
                }

                throw new \Exception($exception->getMessage());
            }
        }

        $this->section->overwrite("Успех!");
        return 0;
    }

    /**
     * @param $objects
     * @param ElasticIndexInterface $elasticIndex
     * @param string $messagePrefix
     * @throws \Throwable
     */
    private function updateIndex($objects, ElasticIndexInterface $elasticIndex, string $messagePrefix)
    {
        if (is_countable($objects)) {
            $this->handleArray($objects, $elasticIndex, $messagePrefix);
        } else {
            $this->handleGenerator($objects, $elasticIndex, $messagePrefix);
        }
    }

    /**
     * @param $objects
     * @throws \Throwable
     */
    private function handleArray($objects, ElasticIndexInterface $elasticIndex, string $messagePrefix)
    {
        $count = count($objects);
        $builder = $elasticIndex->getBuilder();

        $params = [
            'refresh' => true,
            'body' => []
        ];

        $generatedId = 1;
        for ($i = 0; $i < $count; $i++) {
            $percent = number_format((int) $i / $count * 100, 2, ',', '');
            if (!$elasticIndex->hasCustomOutput()) {
                $this->section->overwrite($messagePrefix . $percent);
            }

            $object           = $objects[$i];
            $document         = $builder->create($object);
            $params['body'][] = [
                'index' => [
                    '_index' => $elasticIndex->getIndex(),
                    '_id'    => $document->id ?? $generatedId
                ]
            ];

            $params['body'][] = $this->serializer->toArray($document);
            if (($i > 0) && ($i % 100 == 0)) {
                $this->sendBulk($params);
                $params = ['body' => []];
            }

            $generatedId++;
        }

        $this->sendBulk($params);
        gc_collect_cycles();
        gc_mem_caches();
    }

    /**
     * @param $objects
     * @throws \Throwable
     */
    private function handleGenerator($objects, ElasticIndexInterface $elasticIndex, string $messagePrefix)
    {
        foreach ($objects as $object) {
            $this->handleArray($object, $elasticIndex, $messagePrefix);
        }
    }

    /**
     * @param $params
     * @throws \Throwable
     */
    private function sendBulk($params)
    {
        if (!empty($params)) {
            try {
                $result = $this->client->bulk($params);
                if (isset($result['errors']) && $result['errors']) {
                    foreach ($result['items'] as $item) {
                        if ($item['index']['status'] !== 200 && $item['index']['status'] !== 201) {
//                        dd($item);
                            $this->errorsCount++;
                        }
                    }
                }
            } catch (RuntimeException $e) {
                foreach ($params['body'] as $param) {
                    $testEncode = json_encode($param);
                    if (!$testEncode) {
                        dump($param);
                    }
                }
                throw $e;
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
     * @param string $indexName
     */
    private function prepareFlag(string $indexName) {
        $params = [
            'index' => $indexName,
            'refresh' => true,
            'conflicts' => 'proceed',
            'body'  => [
                'script' => [
                    'lang' => 'painless',
                    'source' => 'ctx._source.absentee=true',
                ],
            ]
        ];
        $this->client->updateByQuery($params);
    }

    private function clearingData(string $indexName) {
        $params = [
            'index' => $indexName,
            'refresh' => true,
            // 'wait_for_completion' => true,
            'conflicts' => 'proceed',
            'body'  => [
                'query' => [
                    'term' => [
                        'absentee' => true
                    ]
                ]
            ]
        ];
        $result = $this->client->deleteByQuery($params);
        // Выборка $result['total']
        // Удалено $result['deleted']
        // Не смогли удалить $result['version_conflicts']
    }

}
