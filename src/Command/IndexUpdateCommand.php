<?php

namespace AlexTanVer\ElasticBundle\Command;

use AlexTanVer\ElasticBundle\Interfaces\ElasticIndexInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use AlexTanVer\ElasticBundle\ClientBuilder;
use AlexTanVer\ElasticBundle\Command\Base\BaseIndexCommand;
use AlexTanVer\ElasticBundle\Factory\ElasticIndexFactory;

class IndexUpdateCommand extends BaseIndexCommand
{
    protected static $defaultName = 'elastic:index:update';

    private $processes = [];
    private $indexesWithError = [];

    public function __construct(
        ElasticIndexFactory $elasticIndexFactory,
        ClientBuilder $clientBuilder
    )
    {
        parent::__construct();

        $this->elasticIndexFactory = $elasticIndexFactory;
        $this->client              = $clientBuilder->createClient();
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('Добавление объектов в индексы (при указании нескольких, обновление происходит асинхронно)')
            ->addOption('simple_output', 's', InputOption::VALUE_NONE, 'Простой строковый вывод. Для лог-файла')
            ->addOption('id', '', InputOption::VALUE_OPTIONAL, 'ID обновляемого объекта в базе (если указано, то обновится только один объект)');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        if ($input->getOption('all') && $input->getOption('id')) {
            throw new \Exception("Нельзя указывать ID, если указан '--all'");
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updatedIndexes = $input->getOption('all')
            ? $this->getAvailableIndexes()
            : $input->getArgument('index');

        $isSimpleOut = $input->getOption('simple_output');

        $startTime = microtime(true);

        ProgressBar::setFormatDefinition('custom', "<info>%index%</info> - <fg=white;bg=blue>%progress%</>");
        $progressBars = [];
        foreach ($updatedIndexes as $updatedIndex) {
            $updatedIndex = explode(' ', $updatedIndex);
            $updatedIndex = $updatedIndex[0] ?? null;

            $process                        = new Process([
                'bin/console', 'elastic:update:single:index',
                $updatedIndex, ($isSimpleOut ? '--simple_output' : '-v')
            ]);
            $this->processes[$updatedIndex] = $process;

            $process->start();

            if (!$isSimpleOut) {
                $section     = $output->section();
                $progressBar = new ProgressBar($section);
                $progressBar->setFormat('custom');
                $progressBar->setMessage($updatedIndex, 'index');
                $progressBar->setMessage('Инициализация', 'progress');
                $progressBars[$updatedIndex] = $progressBar;
                $progressBar->start();
            }
        }

        if ($isSimpleOut) {
            while (count($this->processes)) {
                /** @var Process $process */
                foreach ($this->processes as $key => $process) {
                    if ($process->isRunning()) {
                        sleep(2);
                    } else {
                        $this->io->writeln($process->getOutput());
                        unset($this->processes[$key]);
                    }
                }
            }
        } else {
            while (count($this->processes)) {
                /** @var Process $process */
                foreach ($this->processes as $key => $process) {
                    $processOut = $process->getIncrementalOutput();
                    $processOut = explode("\n", $processOut);
                    $processOut = array_diff($processOut, ['']);
                    $processOut = end($processOut);


                    if ($process->isRunning()) {
                        if ($processOut) {
                            $processOut = explode("\n", $processOut);
                            $processOut = array_diff($processOut, ['']);
                            $processOut = end($processOut);

                            $progressBars[$key]->setMessage($processOut ?: '', 'progress');
                            $progressBars[$key]->advance();
                        }
                    } else {
                        $lastMessage = explode("\n", $process->getOutput());
                        $lastMessage = array_diff($lastMessage, ['']);
                        $lastMessage = end($lastMessage);

                        if ($process->getExitCode() !== 0) {
                            $this->indexesWithError[$updatedIndex] = $process->getErrorOutput();
                        }

                        $progressBars[$key]->setMessage($lastMessage ?: '', 'progress');
                        $progressBars[$key]->advance();

                        if ($process->isTerminated()) {
                            unset($this->processes[$key]);
                        }
                    }
                }
            }
        }


        $endTime  = microtime(true) - $startTime;
        $taskTime = date_create_from_format('U.u', number_format($endTime, 6, '.', ''));

        if (empty($this->indexesWithError)) {
            $this->io->success("Индексы обновлены! Время выполнения - " . $taskTime->format('Hч. iм. sс.'));
        } else {
            $message = "Некоторые индексы обновлены с ошибками: " . implode(', ', array_keys($this->indexesWithError));
            $message .= "\n\n";
            foreach ($this->indexesWithError as $index => $error) {
                $message .= "Ошибка индекса '{$index}':\n\n";
                $message .= "{$error}\n";
                $message .= "-------------------------------------------\n";
            }
            $message .= 'Время выполнения - ' . $taskTime->format('Hч. iм. sс.');

            $this->io->warning($message);
        }

        return 1;
    }

    /**
     * @param array $indexes
     * @return ElasticIndexInterface[]|void
     */
    public function filterIndexes(array &$indexes)
    {
        $indexes = array_filter($indexes, function (ElasticIndexInterface $index) {
            return $index->isCreated();
        });
    }
}
