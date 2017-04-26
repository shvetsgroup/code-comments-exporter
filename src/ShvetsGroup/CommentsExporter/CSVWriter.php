<?php namespace ShvetsGroup\CommentsExporter;

class CSVWriter
{
    private $sources = [];

    public function add(SourceFile $source)
    {
        $this->sources[] = $source;
    }

    public function write(string $destination) {
        $table = [];
        foreach ($this->sources as $source) {
            $source->saveTokenized();
            foreach ($source->getComments() as $example_name => $results) {
                foreach ($results['comments'] as $comments) {
                    foreach ($comments as &$comment) {
                        $comment = preg_replace("#\"#", "\"\"", $comment);
                        if (preg_match("#\n#", $comment)) {
                            $comment = '"' . $comment . '"';
                        }
                    }
                    $table[] = $example_name . "\t" . implode("\t", $comments);
                }
            }
        }
        $table = implode("\n", $table);
        file_put_contents($destination, $table);

    }
}