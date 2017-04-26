<?php namespace ShvetsGroup\CommentsExporter;

class Comment
{
    private $id;
    private $type;
    private $comment;

    /**
     * Comment constructor.
     * @param $id
     * @param $type
     * @param $comment
     */
    public function __construct($id, $type, $comment)
    {
        $this->id = $id;
        $this->type = $type;
        $this->comment = $comment;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }
}