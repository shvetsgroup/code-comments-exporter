<?php namespace ShvetsGroup\CommentsExporter\Parsers;

interface Parser
{
    public function parse($content, array $options): array;
}