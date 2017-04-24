<?php namespace ShvetsGroup\CommentsExporter\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class BaseCommand extends Command
{
    private $rootDir;

    public function __construct($rootDir)
    {
        parent::__construct('comments-command');
        $this->rootDir = $rootDir;
    }

    public function getAbsolutePath($path, $shouldExist = true) {
        if (!$path || !is_string($path)) {
            throw new \InvalidArgumentException("Path should be a string.");
        }

        if (mb_substr($path, 0, 1, 'utf-8') == '/') {
            return $path;
        }

        $path = rtrim(trim($path), '/');
        $pathinfo = pathinfo($path);
        if (!isset($pathinfo['extension']) || !$pathinfo['extension']) {
            $dir = $path;
            $file = '';
        }
        else {
            $dir = $pathinfo['dirname'];
            $file = $pathinfo['basename'];
        }

        $absDir = realpath($this->rootDir . "/" . $dir);
        if (!$absDir) {
            throw new \RuntimeException("Can not resolve directory $dir");
        }

        $absFilePath = $absDir . ($file ? '/' . $file : '');
        if ($shouldExist && !file_exists($absFilePath)) {
            throw new \RuntimeException("File $absFilePath does not exist.");
        }

        return $absFilePath;
    }
}