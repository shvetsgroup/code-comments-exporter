<?php namespace ShvetsGroup\CommentsExporter;

use ShvetsGroup\CommentsExporter\Parsers\Parser;
use Symfony\Component\Finder\SplFileInfo;

class SourceFile
{
    /**
     * @var SplFileInfo
     */
    private $file;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Comment[]
     */
    private $comments = [];

    private $tokenizedContent;

    public function __construct(SplFileInfo $file, Parser $parser)
    {
        $this->file = $file;
        $this->parser = $parser;
    }

    public function addComment($type, $comment): Comment
    {
        $comment = new Comment(count($this->comments), $type, $comment);
        $this->comments[] = $comment;
        return $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getContent()
    {
        return $this->file->getContents();
    }

    public function getTokenizedContent()
    {
        return $this->tokenizedContent;
    }

    public function setTokenizedContent(string $tokenizedContent)
    {
        $this->tokenizedContent = $tokenizedContent;
    }

    public function saveTokenized()
    {
        file_get_contents($this->file->getPathname(), $this->tokenizedContent);
    }

    public function extractComments()
    {
        $result = $this->parser->parse($this->getContent());
        $this->setTokenizedContent($result['tokenized']);
        $this->comments = $result['comments'];
    }
}