<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular;

class Schema
{
    private $definition;
    private $columns = [];
    private $primaryKey;
    private $schemas = [];
    private $filters;
    private $config;

    public function __construct(\Tracker_Definition $definition)
    {
        $this->definition = $definition;
        $this->filters = new \Tracker\Filter\Collection($definition);
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function getHtmlOutputSchema()
    {
        $out = new self($this->definition);
        $out->filters = $this->filters;
        $out->schemas = $this->schemas;

        foreach ($this->columns as $column) {
            $replacement = $column->getPlainReplacement();

            if ($column->isExportOnly()) {
                continue; // Skip column
            } elseif ($replacement || $replacement === false) {
                // Has a replacement means output is HTML
                // No replacement at all is the same
                $out->columns[] = $column;
            } else {
                $out->columns[] = $column->withWrappedRenderTransform('htmlspecialchars');
            }
        }

        return $out;
    }

    public function getPlainOutputSchema()
    {
        $out = new self($this->definition);
        $out->filters = $this->filters;
        $out->schemas = $this->schemas;
        $out->config = $this->config;
        $out->primaryKey = $this->primaryKey;

        foreach ($this->columns as $column) {
            $replacement = $column->getPlainReplacement();

            if ($replacement) {
                $new = $this->addColumn($column->getField(), $replacement);
                $new->setLabel($column->getLabel());

                // If the replacement is read-only, leave as-is
                if (! $new->isReadOnly()) {
                    $new->setReadOnly($column->isReadOnly());
                }

                // Convert the primary key field as needed
                if ($column->isPrimaryKey()) {
                    $out->primaryKey = $new;
                    $new->setPrimaryKey(true);
                }

                $out->columns[] = $new;
            } elseif ($replacement !== false) {
                $out->columns[] = $column;
            }
        }

        return $out;
    }

    public function loadConfig($config)
    {
        $config = array_merge([
            'simple_headers' => 0,
            'import_update' => 1,
            'ignore_blanks' => 0,
            'import_transaction' => 0,
            'bulk_import' => 0,
            'skip_unmodified' => 0,
            'encoding' => '',
            'format' => '',
        ], $config);

        $this->config = $config;
    }

    public function isSimpleHeaders()
    {
        return $this->config['simple_headers'];
    }

    public function canImportUpdate()
    {
        return $this->config['import_update'];
    }

    public function ignoreImportBlanks()
    {
        return $this->config['ignore_blanks'];
    }

    public function isImportTransaction()
    {
        return $this->config['import_transaction'];
    }

    public function useBulkImport()
    {
        return $this->config['bulk_import'];
    }

    public function isSkipUnmodified()
    {
        return $this->config['skip_unmodified'];
    }

    public function getEncoding()
    {
        return $this->config['encoding'];
    }

    public function getFormat()
    {
        $format = $this->config['format'] ?? 'csv';
        return $format ? $format : 'csv';
    }

    public function loadFormatDescriptor($descriptor)
    {
        foreach ($descriptor as $column) {
            try {
                $col = $this->addColumn($column['field'], $column['mode']);
            } catch (Exception\FieldNotFound $e) {
                \Feedback::error($e->getMessage()); // TODO make error message appear when exporting
                continue;
            } catch (Exception\ModeNotSupported $e) {
                \Feedback::error($e->getMessage());
                continue;
            } catch (\Exception $e) {
                // TODO make error message appear when exporting
                \Feedback::error(
                    tr(
                        'Error on field %0 mode "%1"<br>&nbsp;&nbsp;',
                        $column['field'],
                        $column['mode']
                    ) .
                    $e->getMessage()
                );
                continue;
            }
            $col->setExportOnly(! empty($column['isExportOnly']));
            $col->setUniqueKey(! empty($column['isUniqueKey']));

            if (! $col->isReadOnly() && ! empty($column['isReadOnly'])) {
                $col->setReadOnly(true);
            }

            if (! empty($column['displayAlign'])) {
                $col->setDisplayAlign($column['displayAlign']);
            }

            if ($column['label']) {
                $col->setLabel($column['label']);
            }

            if (! empty($column['isPrimary'])) {
                $this->setPrimaryKey($col);
            }

            if (! empty($column['remoteField'])) {
                $col->setRemoteField($column['remoteField']);
            }
        }
    }

    public function loadFilterDescriptor(array $descriptor)
    {
        $this->filters->loadFilterDescriptor($descriptor);
    }

    public function getFilterCollection()
    {
        return $this->filters;
    }

    /**
     * If we have default filters configured with a value, return a QuerySource with configured
     * query to filter only those records. Otherwise, we use a normal TrackerSource to fetch
     * all tracker items for that tracker.
     */
    public function getDefaultFilterSource(): Source\SourceInterface
    {
        $query = $this->getDefaultFilterQuery();
        if ($query) {
            $source = new Source\QuerySource($this, $query);
        } else {
            $source = new Source\TrackerSource($this, $this->getDefinition());
        }
        return $source;
    }

    /**
     * If we have default filters configured with a value, return a query to filter only those records.
     */
    public function getDefaultFilterQuery()
    {
        $has_default_filter = false;
        $collection = $this->getFilterCollection();
        $collection->applyInput(new \JitFilter([]), true);
        foreach ($collection->getFilters() as $filter) {
            if ($filter->getPosition() === 'default' && $filter->getControl()->hasValue()) {
                $has_default_filter = true;
                break;
            }
        }
        if ($has_default_filter) {
            $search = \TikiLib::lib('unifiedsearch');
            $query = $search->buildQuery([
                'type' => 'trackeritem',
                'tracker_id' => $this->getDefinition()->getId(),
            ]);
            $collection->applyConditions($query);
            return $query;
        } else {
            return null;
        }
    }

    public function getFormatDescriptor()
    {
        return array_map(function ($column) {
            return [
                'label' => $column->getLabel(),
                'field' => $column->getField(),
                'mode' => $column->getMode(),
                'remoteField' => $column->getRemoteField(),
                'displayAlign' => $column->getDisplayAlign(),
                'isPrimary' => $column->isPrimaryKey(),
                'isReadOnly' => $column->isReadOnly(),
                'isExportOnly' => $column->isExportOnly(),
                'isUniqueKey' => $column->isUniqueKey(),
            ];
        }, $this->columns);
    }

    public function getFilterDescriptor()
    {
        return $this->filters->getFilterDescriptor();
    }

    public function addColumn($permName, $mode)
    {
        if (isset($this->schemas[$permName])) {
            $partial = $this->schemas[$permName];
        } else {
            $partial = $this->getFieldSchema($permName, $mode);
            $this->schemas[$permName] = $partial;
        }

        $column = $partial->lookupMode($permName, $mode);
        $this->columns[] = $column;

        return $column;
    }

    public function addActualColumn(Schema\Column $column)
    {
        $this->columns[] = $column;
        return $column;
    }

    public function setPrimaryKey($field)
    {
        if ($this->primaryKey) {
            throw new \Exception(tr('Primary key already defined.'));
        }

        foreach ($this->columns as $column) {
            if ($field === $column || $column->getField() == $field) {
                $this->primaryKey = $column;
                $column->setPrimaryKey(true);
                return;
            }
        }

        throw new Exception\FieldNotFound($field);
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function isPrimaryKeyAutoIncrement()
    {
        if ($this->primaryKey) {
            $field = $this->definition->getFieldFromPermName($this->primaryKey->getField());
            return $field && $field['type'] == 'q';
        } else {
            foreach ($this->columns as $column) {
                if ($column->isPrimaryKey()) {
                    $field = $this->definition->getFieldFromPermName($column->getField());
                    return $field && $field['type'] == 'q';
                }
            }
        }
        return false;
    }

    private function lookupMode($permName, $mode)
    {
        foreach ($this->columns as $column) {
            if ($column->getField() == $permName && $column->getMode() == $mode) {
                return $column;
            }
        }

        // mode missing, try the first one
        $column = $this->columns[0];

        if ($column->getField() == $permName) {
            \Feedback::error(tr('Field mode not found: "%0" for field %1. Replaced with "%2"', $mode, $permName, $column->getMode()));
            return $column;
        }
    }

    public function addNew($permName, $mode)
    {
        $column = new Schema\Column($permName, $mode);
        $this->columns[] = $column;
        return $column;
    }

    public function addStatic($value)
    {
        $column = new Schema\Column('ignore', uniqid());
        $column->setReadOnly(true);
        $column->setRenderTransform(function () use ($value) {
            return $value;
        });

        $this->columns[] = $column;
        return $column;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getWriter(&$name, $type, $filename = 'php://output')
    {
        switch ($this->getFormat()) {
            case 'json':
                $writer = new Writer\JsonWriter($filename, $this->getEncoding() ?? '');
                $name .= '_export_' . $type . '.json';
                break;
            case 'ndjson':
                $writer = new Writer\NDJsonWriter($filename, $this->getEncoding() ?? '');
                $name .= '_export_' . $type . '.ndjson';
                break;
            case 'ical':
                $writer = new Writer\IcalWriter($filename, $this->getEncoding() ?? '');
                $name .= '_export_' . $type . '.ics';
                break;
            default:
                $writer = new Writer\CsvWriter($filename, $this->getEncoding() ?? '');
                $name .= '_export_' . $type . '.csv';
        }
        return $writer;
    }

    public function getSource($fileName)
    {
        switch ($this->getFormat()) {
            case 'json':
            case 'ndjson':
                return new Source\JsonSource($this, $fileName, $this->getEncoding());
            case 'ical':
                return new Source\IcalSource($this, $fileName, $this->getEncoding());
            default:
                return new Source\CsvSource($this, $fileName, ',', $this->getEncoding());
        }
    }

    public function validate()
    {
        foreach ($this->columns as $column) {
            $column->validateAgainst($this);
        }
    }

    public function validateAgainstHeaders(array $headers)
    {
        $headerMapping = [];

        foreach ($this->columns as $columnIndex => $column) {
            foreach ($headers as $headerIndex => $header) {
                if ($this->config['simple_headers'] && $column->getLabel() == $header) {
                    $headerMapping[$columnIndex] = $headerIndex;
                    continue 2;
                }

                if (preg_match(Schema\Column::HEADER_PATTERN, $header, $parts)) {
                    list($full, $pk, $field, $mode) = $parts;
                    if ($column->is($field, $mode)) {
                        $headerMapping[$columnIndex] = $headerIndex;
                        continue 2;
                    }
                }
            }
            throw new \Exception(tr('Expected header "%0" not found.', $column->getEncodedHeader($this)));
        }

        return $headerMapping;
    }

    public function getAvailableFields()
    {
        $fields = ['itemId' => tr('Item ID'), 'status' => tr('Status'), 'actions' => tr('Actions')];

        foreach ($this->definition->getFields() as $f) {
            $fields[$f['permName']] = $f['name'];
        }

        return $fields;
    }

    public function getFieldSchema($permName)
    {
        if ($partial = $this->getSystemSchema($permName)) {
            return $partial;
        }

        $field = $this->definition->getFieldFromPermName($permName);
        $factory = $this->definition->getFieldFactory();

        if (! $field) {
            throw new Exception\FieldNotFound($permName);
        }

        $handler = $factory->getHandler($field);

        if (! $handler instanceof \Tracker\Field\ExportableInterface) {
            throw new Exception\ModeNotSupported($permName, 'any mode');
        }

        return $handler->getTabularSchema();
    }

    private function getSystemSchema($name)
    {
        switch ($name) {
            case 'actions':
                $trackerId = $this->definition->getConfiguration('trackerId');
                $schema = new self($this->definition);
                $schema->addNew($name, 'all')
                ->setLabel(tr('Actions'))
                ->addQuerySource('itemId', 'object_id')
                ->setReadOnly(true)
                ->setPlainReplacement(false)
                ->setRenderTransform(function ($value, $extra) use ($trackerId) {
                    $smarty = \TikiLib::lib('smarty');
                    $item = \Tracker_Item::fromId($extra['itemId']);

                    $smarty->assign('tabular_actions', [
                        'trackerId' => $trackerId,
                        'itemId' => $extra['itemId'],
                        'canModify' => $item->canModify(),
                        'canRemove' => $item->canRemove(),
                    ]);

                    return $smarty->fetch('tabular/item_actions.tpl');
                })
                    ;
                return $schema;
            case 'itemId':
                $schema = new self($this->definition);
                $schema->addNew($name, 'id')
                ->setLabel(tr('Item ID'))
                ->addQuerySource('itemId', 'object_id')
                ->setRenderTransform(function ($value, $extra) {
                    return $extra['itemId'];
                })
                    ->setParseIntoTransform(function (&$info, $value) {
                        $info['itemId'] = (int) $value;
                    })
                    ;
                return $schema;
            case 'status':
                $types = \TikiLib::lib('trk')->status_types();
                $invert = array_flip(array_map(function ($s) {
                    return $s['name'];
                }, $types));

                $schema = new self($this->definition);
                $schema->addNew($name, 'system')
                    ->setLabel(tr('Status'))
                    ->addQuerySource('status', 'tracker_status')
                    ->setRenderTransform(function ($value, $extra) {
                        return $extra['status'];
                    })
                    ->setParseIntoTransform(function (&$info, $value) {
                        $info['status'] = $value;
                    })
                    ;
                $schema->addNew($name, 'name')
                    ->setLabel(tr('Status'))
                    ->addQuerySource('status', 'tracker_status')
                    ->setRenderTransform(function ($value, $extra) use ($types) {
                        return $types[$extra['status']]['name'];
                    })
                    ->setParseIntoTransform(function (&$info, $value) use ($invert) {
                        $info['status'] = $invert[$value];
                    })
                    ;
                return $schema;
        }
    }
}
