<?php namespace ShvetsGroup\CommentsExporter\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ShvetsGroup\CommentsExporter\SourceFactory;
use ShvetsGroup\CommentsExporter\CSVWriter;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Exports comments from source code to a csv file.
 *
 * Usage examples:
 * - ./comment export file.php test.csv
 * - ./comment export a-whole-directory test.csv
 */
class ExportCommand extends BaseCommand
{
    private $sourceFactory;

    private $writer;

    public $source, $destination;

    public $ignore = [];

    public $extensions = [];

    public $rightMargin;

    public $fixWordWrap = false;

    public $locales = ['en', 'ru'/*, 'uk'*/];

    public function __construct($rootDir)
    {
        parent::__construct($rootDir);
        $this->sourceFactory = new SourceFactory();
        $this->writer = new CSVWriter();
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
            ->addOption('extensions', 'e', InputOption::VALUE_REQUIRED, "Files that don't have these extensions will be ignored during search (only when `source` argument is directory).")
            ->addOption('right-margin-size', 'r', InputOption::VALUE_REQUIRED, "Size of the right margin where comments will be wrapped. If non passed, comments won't be wrapped.", 0)
            ->addOption('fix-word-wrap', 'w', InputOption::VALUE_NONE, "Try to straighten-up word-wrapped comments (and wrap them again properly after editing). Beware, this may break some fancy formatting.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parseArguments($input);

        $sources = $this->expandSources($this->source);

        foreach ($sources as $path => $file) {
            $source = $this->sourceFactory->make($file);
            $source->extractComments(['fix-word-wrap' => $this->fixWordWrap]);
            $this->writer->add($source);
        }

        $this->writer->write($this->destination);
    }

    /**
     * @param InputInterface $input
     */
    public function parseArguments(InputInterface $input)
    {
        $this->source = $this->getAbsolutePath($input->getArgument('source'));
        $this->destination = $this->getAbsolutePath($input->getArgument('destination'), false);
        $this->ignore = array_filter($input->getOption('ignore')) ?: [];
        if ($extensions = $input->getOption('extensions')) {
            $this->extensions = explode(',', $extensions) ?: [];
        }
        $this->rightMargin = $input->getOption('right-margin-size');
        $this->fixWordWrap = $input->getOption('fix-word-wrap');
    }

    /**
     * @param $sourcePath
     * @return array
     */
    public function expandSources($sourcePath)
    {
        if (!is_dir($sourcePath)) {
            return [$sourcePath => new SplFileInfo($sourcePath, '', '')];
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