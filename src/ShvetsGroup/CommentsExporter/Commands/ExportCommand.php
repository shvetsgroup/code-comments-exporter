<?php namespace ShvetsGroup\CommentsExporter\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * ./comment export . test.csv
 * ./comment export ./../ ../test.csv
 * ./comment export ./../test.php ../test.csv
 */
class ExportCommand extends BaseCommand
{
    const LINE_LENGTH = 80;

    private $source, $destination;

    private $locales = ['en', 'ru'/*, 'uk'*/];

    private $ignore = [];

    private $extensions = ['js', 'java', 'cs', 'php'];

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
            ->addOption('ignore', 'i', InputOption::VALUE_IS_ARRAY, "List of regexp path patterns that will be ignored during search (only when `source` argument is directory). Example: --ignore=/^test$/i --ignore=|.*\\.html$|i", [])
            ->addOption('extensions', null, InputOption::VALUE_REQUIRED, "Files that don't have these extensions will be ignored during search (only when `source` argument is directory).");

    }

    /**
     * Execute command.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parseArguments($input);

        print_r($this);

//        $sources = $this->expandSources($this->source);
//
//        $data = [];
//        foreach ($sources as $source) {
//            $code = $source->getContents();
//            $comments = $this->parseComments($code);
//            $relativePath = $source->getRelativePathname();
//            $data[$relativePath] = $comments;
//        }
//
//        $this->writeDataToCsv($data, $this->destination);
    }

    public function parseArguments(InputInterface $input)
    {
        $this->parseSource($input);
        $this->parseDestination($input);
        $this->parseExclude($input);
        $this->parseExtensions($input);
    }

    public function parseSource(InputInterface $input)
    {
        $this->source = $this->getAbsolutePath($input->getArgument('source'));
        return $this->source;
    }

    public function parseDestination(InputInterface $input)
    {
        $this->destination = $this->getAbsolutePath($input->getArgument('destination'), true);
        return $this->destination;
    }

    protected function parseIgnore(InputInterface $input) {
        $this->ignore = $input->getArgument('ignore') ?: [];
    }

    protected function parseExtensions(InputInterface $input) {
        $this->ignore = explode(',', $input->getArgument('ignore')) ?: [];
    }

    protected function expandSources($sourcePath)
    {
        if (!is_dir($sourcePath)) {
            return [new \SplFileInfo($sourcePath)];
        }

        $files = Finder::create()->files();

        foreach ($this->ignore as $ignore) {
            $files->notContains($ignore);
        }

        if ($this->extensions) {
            $files->path('/\.(' . implode('|', $this->extensions) . ')$/');
        }

        $files->ignoreDotFiles(true)->in($sourcePath);

        return iterator_to_array($files);
    }

    protected function parseComments($content)
    {
        $result = [
            'original' => $content,
            'comments' => []
        ];

        $content = preg_replace_callback('#(?:^|\n *)?(?:(?<!:)(//).*(?:\n *//.*)*|(/\*\*)(?:\n *\*.*)*\n *\*/)#', function ($matches) use (&$file, &$result) {
            $comment = $matches[0];
            // Remove extra spacing from start
            $comment = preg_replace('#^\n? *#', "", $comment);

            // Remove javadoc headers
            $comment = preg_replace('#/\*\*\n#', "", $comment);
            $comment = preg_replace('#\n *\*/#', "", $comment);

            // Remove // headers
            $comment = preg_replace('#^ *// *#m', "", $comment);

            // Remove * headers
            $comment = preg_replace('#^ *\* *#m', "", $comment);

            // Cleanup comment
            $comment = preg_replace('#\n\n+#', '!@', $comment);
            $comment = preg_replace('#\n(?! *\.\.\.)#', ' ', $comment);
            $comment = preg_replace('#!@#', "\n\n", $comment);
            $comment = trim($comment);

            $locale_tag_regexp = "(" . strtoupper(implode('|', $this->locales)) . "):";
            if (preg_match("/(?=$locale_tag_regexp)/", $comment)) {
                $raw_multilang_comments = preg_split("/(?=$locale_tag_regexp)/", $comment, -1, PREG_SPLIT_NO_EMPTY);
                $raw_comments2 = [];
                foreach ($raw_multilang_comments as $comment) {
                    $locale = strtolower(substr($comment, 0, 2));
                    $comment = trim(preg_replace("/^$locale_tag_regexp\s*/", '', $comment));
                    $raw_comments2[$locale] = $comment;
                }
            } else {
                $raw_comments2['en'] = $comment;
            }

            $multi_locale_comments = [];
            if ($matches[1]) {
                $multi_locale_comments['type'] = 'simple';
            } else if ($matches[2]) {
                $multi_locale_comments['type'] = 'javadoc';
            }

            foreach ($this->locales as $locale) {
                $multi_locale_comments[$locale] = $raw_comments2[$locale] ?? '';
            }

            $result['comments'][] = $multi_locale_comments;
            return ($matches[1] ?? '') . '// ###' . (count($result['comments']) - 1) . '###';
        }, $content);

        $result['tokenized'] = $content;

        return $result;
    }

    protected function writeDataToCsv($data, $destination)
    {
        $table = [];
        foreach ($data as $example_name => $results) {
            foreach ($results['comments'] as $comments) {
                foreach ($comments as &$comment) {
                    $comment = preg_replace("#\"#", "\"\"", $comment);
                    if (preg_match("#\n#", $comment)) {
                        $comment = '"' . $comment . '"';
                    }
                }
                $table[] = $example_name . "\t" . implode("\t", $comments);
            }
        }
        $table = implode("\n", $table);
        file_put_contents($destination, $table);
    }

}