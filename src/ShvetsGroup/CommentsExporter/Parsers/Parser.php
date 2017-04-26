<?php namespace ShvetsGroup\CommentsExporter\Parsers;

use ShvetsGroup\CommentsExporter\Comment;

interface Parser
{
    /**
     * Parse source file for comments.
     * @param string $content
     * @param array $options
     * @return Comment[]
     */
    public function parse(string $content, array $options): array;

    /**
     * Update source file with new comments.
     * @param string $content
     * @param Comment[] $comments
     * @param array $options
     * @return string
     */
    public function update(string $content, array $comments, array $options): string;
}