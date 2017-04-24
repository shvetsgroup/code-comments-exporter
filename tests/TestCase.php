<?php namespace Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $rootDir;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->rootDir = realpath(__DIR__ . '/data');
    }
}