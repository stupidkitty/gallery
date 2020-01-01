<?php
namespace SK\GalleryModule\Import;

use SplFileObject;
use LimitIterator;

class CsvParser
{
    private $file;
    private $fields;
    private $iterator;

    private $skipFirstLine;

    private $currentLineData;
    private $totalFields;

    public function __construct(SplFileObject $file, array $fields, array $options = [])
    {
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $this->file = $file;
        $this->fields = array_filter($fields);
        $this->skipFirstLine = (bool) $options['skipFirstLine'] ?? true;

        $this->totalFields = count($this->fields);

        $this->buildIterator();
    }

    public function each(callable $success)
    {
        foreach ($this->iterator as $lineNumber => $line) {
			if ($this->isValidLine($line)) {
				$success($this->prepareLine($line));
			}
        }
    }

    private function isValidLine($line)
    {
        $totalLineItems = count($line);

        return ($this->totalFields === $totalLineItems);
    }

    private function prepareLine($line)
    {
        $newItem = [];

        foreach ($this->fields as $key => $field) {
            if (isset($line[$key]) && $field !== 'skip') {
                $newItem[$field] = trim($line[$key]);
            }
        }

        return $newItem;
    }

    public function getIterator()
    {
        return $this->iterator;
    }

    private function buildIterator()
    {
        $startLine = 0;
        if (true === $this->skipFirstLine) {
            $startLine = 1;
        }

        $this->iterator = new LimitIterator($this->file, $startLine);
    }
}
