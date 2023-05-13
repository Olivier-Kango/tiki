<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Writer;

class JsonWriter
{
    private \SplFileObject $file;
    private string $encoding;

    public function __construct(string $outputFile, string $encoding = '')
    {
        $this->file = new \SplFileObject($outputFile, 'w');
        $this->encoding = $encoding;
    }

    public function sendHeaders(string $filename = 'tiki-tracker-tabular-export.json'): void
    {
        $encoding = $this->encoding;
        if (empty($encoding)) {
            $encoding = 'utf-8';
        }
        header("Content-Type: application/json; charset=$encoding");
        header("Content-Disposition:attachment;filename=$filename");
    }

    public function write(\Tracker\Tabular\Source\SourceInterface $source)
    {
        $schema = $source->getSchema();
        $schema = $schema->getPlainOutputSchema();
        $schema->validate();

        $columns = $schema->getColumns();

        $data = [];
        foreach ($source->getEntries() as $entry) {
            $row = [];

            foreach ($columns as $column) {
                $row[$column->getField()] = $this->encode($entry->render($column, false));
            }

            $data[] = $row;
        }

        $this->file->fwrite(json_encode($data));
    }

    private function encode(string $str): string
    {
        if ($this->encoding) {
            return mb_convert_encoding($str, $this->encoding, 'UTF-8');
        } else {
            return $str;
        }
    }
}
