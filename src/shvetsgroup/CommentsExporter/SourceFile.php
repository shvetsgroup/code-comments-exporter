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

    public function __construct(SplFileInfo $file, Parser $parser)
    {
        $this->file = $file;
        $this->parser = $parser;
    }

    public function getPath() {
        return $this->file->getRelativePathname();
    }

    public function addComment($id, $type, $comment)
    {
        $this->comments[] = new Comment($id, $type, $comment);
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getContent()
    {
        return $this->file->getContents();
    }

    public function setContent(string $content)
    {
        //file_put_contents($this->file->getPathname(), $content);
    }


    public function extractComments($options)
    {
        $this->comments = $this->parser->parse($this->getContent(), $options);
    }

    public function updateComments($options)
    {
        $content = $this->parser->update($this->getContent(), $this->comments, $options);
        $this->setContent($content);
    }
}