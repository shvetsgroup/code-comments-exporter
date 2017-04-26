<?php namespace ShvetsGroup\CommentsExporter\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ShvetsGroup\CommentsExporter\CSV;
use ShvetsGroup\CommentsExporter\SourceFactory;

/**
 * Imports comments from csv file and into source code.
 *
 * Usage examples:
 * - ./comments-exporter import test.csv file.php
 * - ./comments-exporter import test.csv a-whole-directory
 * - ./comments-exporter import test.csv a-whole-directory -w 120
 */
class ImportCommand extends Command
{
    protected $rootDir;

    private $sourceFactory;

    private $csv;

    public $source, $destination;

    public $wordWrapSize;

    public function __construct($rootDir)
    {
        parent::__construct('import');
        $this->rootDir = $rootDir;
        $this->sourceFactory = new SourceFactory();
        $this->csv = new CSV();
    }

    protected function configure()
    {
        $this
            ->setName('import')
            ->setDescription('Imports comments from csv file back to source files.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path to the csv file.')
            ->addArgument('destination', InputArgument::REQUIRED, 'Path to source files.')
            ->addOption('word-wrap-size', 'w', InputOption::VALUE_REQUIRED, "Size of the right margin where comments will be wrapped. If non passed, comments won't be wrapped.", 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parseArguments($input);

        $sources = $this->csv->read($input->getArgument('source'), $this->sourceFactory, $this->destination);

        foreach ($sources as $source) {
            $source->updateComments(['word-wrap-size' => $this->wordWrapSize]);
        }
    }

    /**
     * @param InputInterface $input
     */
    public function parseArguments(InputInterface $input)
    {
        $this->source = $input->getArgument('source');
        $this->destination = rtrim($input->getArgument('destination'), '\\/');
        $this->wordWrapSize = $input->getOption('word-wrap-size');
    }
}