<?php


namespace AlexTanVer\ElasticBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

class GenerateDocumentCommand extends Command
{
    /** @var Kernel */
    private $kernel;

    public function __construct(string $name = null, Kernel $kernel)
    {
        parent::__construct($name);
        $this->kernel = $kernel;
    }

    protected function configure()
    {
        parent::configure();
        $this->addArgument('name', InputArgument::REQUIRED)
            ->setDescription('Генерирование классов для работы с elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = ucfirst($input->getArgument('name'));

        $this->createIndex($name);
        $this->createDocument($name);
        $this->createDocumentBuilder($name);
        $this->createRepository($name);

        return 1;
    }

    /**
     * @param string $name
     */
    private function createIndex(string $name)
    {
        $indexName = str_replace('/', '_', $name);
        $indexName = $this->fromCamelCase($indexName);

        list($name, $subDir, $dir) = $this->parseName($name, 'Index');

        $indexPattern = file_get_contents(__DIR__ . '/../Pattern/IndexPattern.php');
        $indexPattern = str_replace('INDEX_NAME', $indexName, $indexPattern);
        $indexPattern = str_replace('IndexPattern', "{$name}Index", $indexPattern);
        $indexPattern = str_replace('\SUBDIR', $subDir ? "\\{$subDir}" : '', $indexPattern);
        $indexPattern = str_replace('CUSTOMDOCUMENTBUILDER', "{$name}DocumentBuilder", $indexPattern);
        $indexPattern = str_replace('CUSTOMREPOSITORY', "{$name}Repository", $indexPattern);
        $indexPattern = str_replace('#', "", $indexPattern);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents("{$dir}/{$name}Index.php", $indexPattern);
    }

    /**
     * @param string $name
     */
    private function createDocument(string $name)
    {
        list($name, $subDir, $dir) = $this->parseName($name, 'Document');

        $documentPattern = file_get_contents(__DIR__ . '/../Pattern/DocumentPattern.php');
        $documentPattern = str_replace('DocumentPattern', "{$name}Document", $documentPattern);
        $documentPattern = str_replace('\SUBDIR', $subDir ? "\\{$subDir}" : '', $documentPattern);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents("{$dir}/{$name}Document.php", $documentPattern);
    }

    /**
     * @param string $name
     */
    private function createDocumentBuilder(string $name)
    {
        list($name, $subDir, $dir) = $this->parseName($name, 'DocumentBuilder');

        $documentBuilderPattern = file_get_contents(__DIR__ . '/../Pattern/DocumentBuilderPattern.php');
        $documentBuilderPattern = str_replace('DocumentBuilderPattern', "{$name}DocumentBuilder", $documentBuilderPattern);
        $documentBuilderPattern = str_replace('\SUBDIR', $subDir ? "\\{$subDir}" : '', $documentBuilderPattern);
        $documentBuilderPattern = str_replace('SUBDIR\\', $subDir ? $subDir : '', $documentBuilderPattern);
        $documentBuilderPattern = str_replace('DocumentPattern', "{$name}Document", $documentBuilderPattern);
        $documentBuilderPattern = str_replace('#', '', $documentBuilderPattern);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents("{$dir}/{$name}DocumentBuilder.php", $documentBuilderPattern);
    }

    /**
     * @param string $name
     */
    private function createRepository(string $name)
    {
        list($name, $subDir, $dir) = $this->parseName($name, 'Repository');

        $repositoryPattern = file_get_contents(__DIR__ . '/../Pattern/RepositoryPattern.php');
        $repositoryPattern = str_replace('RepositoryPattern', "{$name}Repository", $repositoryPattern);
        $repositoryPattern = str_replace('\SUBDIR', $subDir ? "\\{$subDir}" : '', $repositoryPattern);
        $repositoryPattern = str_replace('SUBDIR\\', $subDir ? $subDir : '', $repositoryPattern);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents("{$dir}/{$name}Repository.php", $repositoryPattern);
    }

    /**
     * @param string $name
     * @param string $elasticDir
     * @return array
     */
    private function parseName(string $name, string $elasticDir): array
    {
        $nameArray = explode('/', $name);
        $name      = array_pop($nameArray);
        $subDir    = implode('/', $nameArray);

        $dir = "{$this->kernel->getProjectDir()}/src/Elasticsearch/{$elasticDir}" . ($subDir ? "/{$subDir}" : '');

        return [$name, $subDir, $dir];
    }

    /**
     * @param string $input
     * @return string
     */
    private function fromCamelCase(string $input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}
