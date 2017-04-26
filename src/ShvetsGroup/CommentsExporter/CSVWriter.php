<?php namespace ShvetsGroup\CommentsExporter;

use League\Csv\Writer;

class CSVWriter
{
    /**
     * @var SourceFile[]
     */
    private $sources = [];

    public function add(SourceFile $source)
    {
        $this->sources[] = $source;
    }

    public function write(string $destination)
    {
        $header = ['file', 'id', 'type', 'comment'];

        $records = [];
        foreach ($this->sources as $source) {
            $source->saveTokenized();
            foreach ($source->getComments() as $comment) {
                $records[] = [
                    $source->getPath(),
                    $comment->getId(),
                    $comment->getType(),
                    $comment->getComment()
                ];
            }
        }

        $csv = Writer::createFromString('');
        $csv->insertOne($header);
        $csv->insertAll($records);
        file_put_contents($destination, $csv);
    }
}