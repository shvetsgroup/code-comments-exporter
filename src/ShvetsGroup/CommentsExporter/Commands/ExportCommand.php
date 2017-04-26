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
 * ./comment export . test.csv
 * ./comment export ./../ ../test.csv
 * ./comment export ./../test.php ../test.csv
 */
class ExportCommand extends BaseCommand
{
    private $sourceFactory;

    private $writer;

    public $source, $destination;

    public $ignore = [];

    public $extensions = [];

    public $rightMargin;

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
            ->addOption('right-margin-size', 'r', InputOption::VALUE_REQUIRED, "Size of the right margin where comments will be wrapped. If non passed, comments won't be wrapped.", 0);
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
            $source->extractComments();
            $this->writer->add($source);
        }

        $this->writer->write($this->destination);
    }

    /**
     * @param InputInterface $input
     */
    public function parseArguments(InputInterface $input)
    {
        $this->getSourceArgument($input);
        $this->getDestinationArgument($input);
        $this->getIgnoreOption($input);
        $this->getExtensionsOption($input);
        $this->getRightMarginOption($input);
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    public function getSourceArgument(InputInterface $input)
    {
        $this->source = $this->getAbsolutePath($input->getArgument('source'));
        return $this->source;
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    public function getDestinationArgument(InputInterface $input)
    {
        $this->destination = $this->getAbsolutePath($input->getArgument('destination'), false);
        return $this->destination;
    }

    /**
     * @param InputInterface $input
     */
    protected function getIgnoreOption(InputInterface $input)
    {
        $this->ignore = array_filter($input->getOption('ignore')) ?: [];
    }

    /**
     * @param InputInterface $input
     */
    protected function getExtensionsOption(InputInterface $input)
    {
        if ($extensions = $input->getOption('extensions')) {
            $this->extensions = explode(',', $extensions) ?: [];
        }
    }

    /**
     * @param InputInterface $input
     */
    protected function getRightMarginOption(InputInterface $input)
    {
        $this->rightMargin = $input->getOption('right-margin-size');
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