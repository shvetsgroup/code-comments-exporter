<?php namespace ShvetsGroup\CommentsExporter;

use ShvetsGroup\CommentsExporter\Parsers\BaseParser;
use Symfony\Component\Finder\SplFileInfo;

class SourceFactory
{
    public function make(SplFileInfo $file): SourceFile
    {
        return new SourceFile($file, new BaseParser());
    }
}