<?php namespace Tests\Unit\ShvetsGroup\CommentsExporter\Commands;

use Tests\TestCase;
use ShvetsGroup\CommentsExporter\Commands\ExportCommand;

class ExportCommandTest extends TestCase
{
    private $path;

    /**
     * @var ExportCommand
     */
    private $command;

    public function setUp()
    {
        $this->path = $this->rootDir . '/data';
        $this->command = new ExportCommand($this->path);
    }

    public function assertPathArrayEquals($basePath, $expected, $actual)
    {
        $this->assertEquals(count($expected), count($actual), "File count is wrong.");
        $i = 0;
        uasort($actual, function($a, $b){ return $a->getRealPath() > $b->getRealPath(); });
        sort($expected);
        foreach ($actual as $file) {
            $this->assertEquals($basePath . $expected[$i], $file->getRealPath(), "{$i}th file has wrong path.");
            $i++;
        }
    }

    public function testExpandSourcesFile()
    {
        $path = $this->path . '/source.php';
        $result = $this->command->fildAllSourceFiles($path);
        $this->assertPathArrayEquals('', [$path], $result);
    }

    public function testExpandSourcesDir()
    {
        $result = $this->command->fildAllSourceFiles($this->path);
        $this->assertPathArrayEquals($this->path, [
            '/source.php',
            '/subdir2/subsource.js',
            '/subdir/subsource.php',
        ], $result);
    }

    public function testExpandSourcesDirExtensions()
    {
        $this->command->extensions = ['php'];

        $result = $this->command->fildAllSourceFiles($this->path);
        $this->assertPathArrayEquals($this->path, [
            '/source.php',
            '/subdir/subsource.php',
        ], $result);
    }

    public function testExpandSourcesDirIgnore()
    {
        $this->command->ignore = ['/php$/i'];

        $result = $this->command->fildAllSourceFiles($this->path);
        $this->assertPathArrayEquals($this->path, [
            '/subdir2/subsource.js',
        ], $result);
    }

    public function testExpandSourcesDirIgnorePlusExtensions()
    {
        $this->command->extensions = ['php'];
        $this->command->ignore = ['subdir'];

        $result = $this->command->fildAllSourceFiles($this->path);
        $this->assertPathArrayEquals($this->path, [
            '/source.php',
        ], $result);
    }
}
