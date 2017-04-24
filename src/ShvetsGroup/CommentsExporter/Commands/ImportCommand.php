<?php namespace ShvetsGroup\CommentsExporter\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ImportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('imports')
            ->setDescription('Imports comments from csv file back to source files.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path to the csv file.')
            ->addArgument('destination', InputArgument::REQUIRED, 'Path to source files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
    }


//    private $locales = ['en', 'ru'/*, 'uk'*/];
//    private $languages = ['pseudocode', 'java'];
//    private $base_path = 'examples/patterns';
//
//    /**
//     * Execute console command.
//     */
//    public function handle()
//    {
//        $filesystem = app('Illuminate\Filesystem\Filesystem');
//        $filename = $this->argument('filename');
//        $table = [];
//
//        if (($handle = fopen(storage_path($filename), "r")) !== FALSE) {
//            while (($data = fgetcsv($handle, 10000, "\t")) !== FALSE) {
//                $table[] = $data;
//            }
//            fclose($handle);
//        }
//
//        foreach ($this->locales as $locale) {
//            foreach ($this->languages as $lang) {
//                $srcDir = resource_path('examples/patterns/' . $lang . '/src');
//                $generatedDir = resource_path('examples/patterns/' . $lang . '/generated/' . $locale . '/src');
//                $filesystem->deleteDirectory($generatedDir);
//                $filesystem->copyDirectory($srcDir, $generatedDir);
//            }
//            $filesystem->copyDirectory(resource_path('examples/patterns/java/.idea'),
//                resource_path('examples/patterns/java/generated/' . $locale . '/.idea'));
//        }
//
//        $map = [];
//        foreach ($table as $cells) {
//            $filename = '';
//            $type = 'simple';
//            $comments = [];
//            foreach ($cells as $num => &$cell) {
//                if ($num == 0) {
//                    $filename = $cell;
//                    if (!isset($map[$filename])) {
//                        $map[$filename] = [];
//                    }
//                }
//                else if ($num == 1) {
//                    $type = $cell;
//                } else {
//                    $cell = preg_replace("#^\"([\s\S]*?)\"$#", "$1", $cell);
//                    $cell = preg_replace("#\"\"#", "\"", $cell);
//
//                    // Prevent last word on new lines (that is non-breaking space).
//                    $cell = preg_replace("# ([^ ]+$)#", "\xc2\xa0$1", $cell);
//
//                    $comments[$this->locales[$num - 2]] = $cell;
//                }
//            }
//            $map[$filename][] = ["type" => $type, "comments" => $comments];
//        }
//
//        foreach ($map as $src_filename => $data) {
//            $content = $filesystem->get(resource_path($this->base_path . '/' . $src_filename));
//            $content = $this->tokenizeComments($content);
//
//            $result = $this->replaceTokens($src_filename, $content, $data);
//            $filesystem->put(resource_path($this->base_path . '/' . $src_filename), $result);
//
//            foreach ($this->locales as $locale) {
//                $result = $this->replaceTokens($src_filename, $content, $data, $locale);
//                $generatedFilename = preg_replace('#^(.*?)/(.*?)$#', '$1/generated/' . $locale . '/$2', $src_filename);
//                $filesystem->makeDirectory(dirname(resource_path($this->base_path . '/' . $generatedFilename)), 0755, true, true);
//                $filesystem->put(resource_path($this->base_path . '/' . $generatedFilename), $result);
//            }
//        }
//    }
//
//    public function tokenizeComments($content)
//    {
//        return preg_replace_callback('#(^|\n *)?(?:(?<!:)(//).*(?:\n *//.*)*|(/\*\*)(?:\n *\*.*)*\n *\*/)#', function ($matches) use (&$count) {
//            $count++;
//            return ($matches[1] ?? '') . '// ###' . ($count - 1) . '###';
//        }, $content);
//    }
//
//    public function replaceTokens($src_filename, $content, $data, $locale = null)
//    {
//        $result = $content;
//
//        foreach ($data as $index => $ddata) {
//            $type = $ddata['type'];
//            $comment_set = $ddata['comments'];
//
//            $onlyEnglish = true;
//            foreach ($this->locales as $l) {
//                if ($comment_set[$l] && $l != 'en') {
//                    $onlyEnglish = false;
//                }
//            }
//            if ($onlyEnglish) {
//                $comment = $comment_set['en'];
//            }
//            else {
//                if (!$locale) {
//                    $comment = '';
//                    foreach ($comment_set as $l => $localized_comment) {
//                        if (!$localized_comment) {
//                            continue;
//                        }
//                        $comment .= strtoupper($l) . ': ' . $localized_comment . "\n\n";
//                    }
//                    $comment = rtrim($comment);
//                } else {
//                    if (!isset($comment_set[$locale])) {
//                        throw new \Exception("File $src_filename has no $index-th comments for locale $locale.");
//                    }
//                    $comment = $comment_set[$locale];
//                }
//            }
//
//            $result = preg_replace_callback('|( *?)// ###' . $index . '###|', function ($matches) use ($type, $comment) {
//                $indent = $matches[1];
//                $indent_size = strlen($indent);
//                if ($type == 'simple') {
//                    $comment_size = strlen('// ');
//                    $width = self::LINE_LENGTH - $indent_size - $comment_size;
//                    $comment = $this->mb_wordwrap($comment, $width);
//                    $comment = $indent . '// ' . preg_replace("|\n|", "\n" . $indent . "// ", $comment);
//                } else if ($type == 'javadoc') {
//                    $comment_size = strlen('// ');
//                    $width = self::LINE_LENGTH - $indent_size - $comment_size;
//                    $comment = $this->mb_wordwrap($comment, $width);
//                    $comment = $indent . ' * ' . preg_replace("|\n|", "\n" . $indent . " * ", $comment);
//                    $comment = $indent . "/**\n" . $comment . "\n" . $indent . " */";
//                }
//                return $comment;
//            }, $result);
//        }
//        $result = preg_replace("#\xc2\xa0#", " ", $result);
//
//        if (preg_match('|// ###[0-9]+###|', $result)) {
//            throw new \Exception("File $src_filename has unmatched comments.");
//        }
//
//        return $result;
//    }
//
//    function mb_wordwrap($str, $width = 75, $break = "\n", $cut = false)
//    {
//        $lines = explode($break, $str);
//        foreach ($lines as &$line) {
//            $line = rtrim($line);
//            if (mb_strlen($line) <= $width)
//                continue;
//            $words = explode(' ', $line);
//            $line = '';
//            $actual = '';
//            foreach ($words as $word) {
//                if (mb_strlen($actual . $word) <= $width)
//                    $actual .= $word . ' ';
//                else {
//                    if ($actual != '')
//                        $line .= rtrim($actual) . $break;
//                    $actual = $word;
//                    if ($cut) {
//                        while (mb_strlen($actual) > $width) {
//                            $line .= mb_substr($actual, 0, $width) . $break;
//                            $actual = mb_substr($actual, $width);
//                        }
//                    }
//                    $actual .= ' ';
//                }
//            }
//            $line .= trim($actual);
//        }
//        return implode($break, $lines);
//    }
}