<?php namespace ShvetsGroup\CommentsExporter\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ShvetsGroup\CommentsExporter\SourceFactory;
use ShvetsGroup\CommentsExporter\CSV;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Exports comments from source code to a csv file.
 *
 * Usage examples:
 * - ./comment export file.php test.csv
 * - ./comment export a-whole-directory test.csv
 * - ./comment export a-whole-directory test.csv -i /ignored-dir/ -e php,html -w
 */
class ExportCommand extends Command
{
    protected $rootDir;

    private $sourceFactory;

    private $csv;

    public $source, $destination;

    public $ignore = [];

    public $extensions = [];

    public $fixWordWrap = false;

    public $locales = ['en', 'ru'/*, 'uk'*/];

    public function __construct($rootDir)
    {
        parent::__construct('export');
        $this->rootDir = $rootDir;
        $this->sourceFactory = new SourceFactory();
        $this->csv = new CSV();
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription('Exports comments from source files into csv file.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path to source code.')
            ->addArgument('destination', InputArgument::REQUIRED, 'Path to the resulting csv file.')
            ->addOption('ignore', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "List of regexp path patterns that will be ignored during search (only when `source` argument is directory). Example: --ignore=/^test$/i --ignore=|.*\\.html$|i", [])
            ->addOption('extensions', 'e', InputOption::VALUE_REQUIRED, "Comma-separated list of file extensions. Files that don't have these extensions will be ignored during search (only when `source` argument is directory).  Example: --extensions=php,html")
            ->addOption('fix-word-wrap', 'w', InputOption::VALUE_NONE, "Try to straighten-up word-wrapped comments (and wrap them again properly after editing). Beware, this may break some fancy formatting.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parseArguments($input);

        $files = $this->fildAllSourceFiles($this->source);

        foreach ($files as $path => $file) {
            $source = $this->sourceFactory->make($file);
            $source->extractComments(['fix-word-wrap' => $this->fixWordWrap]);
            $this->csv->addSource($source);
        }

        $this->csv->write($this->destination);
    }

    /**
     * @param InputInterface $input
     */
    public function parseArguments(InputInterface $input)
    {
        $this->source = rtrim($input->getArgument('source'), '\\/');
        $this->destination = $input->getArgument('destination');
        $this->ignore = array_filter($input->getOption('ignore')) ?: [];
        if ($extensions = $input->getOption('extensions')) {
            $this->extensions = explode(',', $extensions) ?: [];
        }
        $this->fixWordWrap = $input->getOption('fix-word-wrap');
    }

    /**
     * @param $sourcePath
     * @return array
     */
    public function fildAllSourceFiles($sourcePath)
    {
        if (!is_dir($sourcePath)) {
            return [$sourcePath => new SplFileInfo($sourcePath, dirname($sourcePath), $sourcePath)];
        }

        $files = Finder::create()->files();

        foreach ($this->ignore as $ignore) {
            $files->notPath($ignore);
        }

        if ($this->extensions) {
            $files->path('/\.(' . implode('|', $this->extensions) . ')$/');
        }

        $files->ignoreDotFiles(true)->in($sourcePath);

        return iterator_to_array($files);
    }
}