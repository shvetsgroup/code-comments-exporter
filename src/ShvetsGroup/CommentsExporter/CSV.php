<?php namespace ShvetsGroup\CommentsExporter;

use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Finder\SplFileInfo;

class CSV
{
    protected $header = ['file', 'id', 'type', 'comment'];

    /**
     * @var SourceFile[]
     */
    private $sources = [];

    /**
     * @param SourceFile $source
     */
    public function addSource(SourceFile $source)
    {
        $this->sources[] = $source;
    }

    /**
     * @return SourceFile[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Write sources from csv file.
     * @param string $source
     * @return SourceFile[]
     */
    public function read(string $source, SourceFactory $factory, $sourcesBasePath): array
    {
        $records = Reader::createFromPath($source)->setOffset(1)->fetchAll();

        $this->sources = [];
        foreach ($records as $record) {
            list($path, $id, $type, $comment) = $record;

            if (!isset($this->sources[$path])) {
                $baseIsFileName = !is_dir($sourcesBasePath);
                $pathEqualsBasePath = realpath($sourcesBasePath) == realpath($path);
                if ($baseIsFileName && !$pathEqualsBasePath) {
                    continue;
                }

                $relativePath = $sourcesBasePath . '/' . $path;
                $file = new SplFileInfo($relativePath, dirname($path), $path);

                $this->sources[$path] = $factory->make($file);
            }
            $source = $this->sources[$path];
            $source->addComment($id, $type, $comment);
        }

        return $this->sources;
    }

    /**
     * Write csv file.
     * @param string $destination
     */
    public function write(string $destination)
    {
        $records = [];
        foreach ($this->sources as $source) {
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
        $csv->insertOne($this->header);
        $csv->insertAll($records);
        file_put_contents($destination, $csv);
    }
}