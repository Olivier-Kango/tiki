<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

use Sabre\VObject;
use Tracker\Tabular\Schema;

class IcalSource implements SourceInterface
{
    private Schema $schema;
    private \SplFileObject $file;
    private string $encoding;

    public function __construct(Schema $schema, string $fileName, string $encoding)
    {
        $this->schema = $schema->getPlainOutputSchema();
        $this->file = new \SplFileObject($fileName, 'r');
        $this->encoding = $encoding;
    }

    public function getEntries()
    {
        $this->file->fseek(0);
        $size = $this->file->getSize();
        $contents = $this->file->fread($size);

        if ($this->encoding) {
            $vcalendar = VObject\Reader::read($contents, VObject\Reader::OPTION_FORGIVING, $this->encoding);
        } else {
            $vcalendar = VObject\Reader::read($contents, VObject\Reader::OPTION_FORGIVING);
        }

        foreach ($vcalendar->VEVENT as $vevent) {
            $data = [];
            foreach ($this->schema->getColumns() as $column) {
                $key = $column->getRemoteField();
                $value = (string)$vevent->$key;
                $data[spl_object_hash($column)] = $value;
            }
            yield new JsonSourceEntry($data);
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
