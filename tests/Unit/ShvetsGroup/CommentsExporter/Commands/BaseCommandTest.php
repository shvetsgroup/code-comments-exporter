<?php

namespace Tests\Unit\ShvetsGroup\CommentsExporter\Commands;

use Tests\TestCase;
use ShvetsGroup\CommentsExporter\Commands\BaseCommand;

class BaseCommandTest extends TestCase
{
    /**
     * @var BaseCommand
     */
    private $command;

    public function setUp()
    {
        $this->command = new BaseCommand($this->rootDir);
    }

    public function testGetAbsolutePath()
    {
        $this->assertEquals($this->rootDir,
            $this->command->getAbsolutePath('.'));
        $this->assertEquals($this->rootDir . '/source.php',
            $this->command->getAbsolutePath('source.php'));
    }

    public function testGetAbsolutePathSubdir()
    {
        $this->assertEquals($this->rootDir . '/subdir',
            $this->command->getAbsolutePath('subdir'));
        $this->assertEquals($this->rootDir . '/subdir',
            $this->command->getAbsolutePath('subdir/'));
        $this->assertEquals($this->rootDir . '/subdir/subsource.php',
            $this->command->getAbsolutePath('subdir/subsource.php'));
    }

    public function testGetAbsolutePathParentDir()
    {
        $command = new BaseCommand($this->rootDir . '/subdir');
        $this->assertEquals($this->rootDir . '/source.php',
            $command->getAbsolutePath('../source.php'));
    }

    public function testGetAbsolutePathError()
    {
        $this->expectException(\RuntimeException::class);
        $this->command->getAbsolutePath('nonexistentdir');
    }

    public function testGetAbsoluteFileError()
    {
        $this->expectException(\RuntimeException::class);
        $this->command->getAbsolutePath('nonexistent.php');
    }

    public function testGetAbsolutePathOptionalExistenceDir()
    {
        $this->expectException(\RuntimeException::class);
        $this->command->getAbsolutePath('nonexistentdir', false);
    }

    public function testGetAbsolutePathOptionalExistenceFile()
    {
        $this->assertEquals($this->rootDir . '/subdir/nonexistent.php',
            $this->command->getAbsolutePath('subdir/nonexistent.php', false));
    }

}
