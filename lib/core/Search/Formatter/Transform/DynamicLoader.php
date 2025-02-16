<?php

/** If a field isn't available in the index, this tries to load it from the original data, which can have a huge performance impcat.
 * Typically it does it for multivalue fields which are tokenized to
 * hashes for indexing that are meaningless for the user.
 * To restore them to meaningful values, it needs to retrieve the initial data.
 * An example is the UserSelect field in trackers. */
class Search_Formatter_Transform_DynamicLoader
{
    private $source;

    public function __construct(Search_Formatter_DataSource_Interface $datasource)
    {
        $this->source = $datasource;
    }

    public function __invoke($entry)
    {
        return new Search_Formatter_Transform_DynamicLoaderWrapper($entry, $this->source);
    }
}

class Search_Formatter_Transform_DynamicLoaderWrapper extends ArrayObject
{
    private $source;
    private $loaded = [];

    public function __construct($entry, $source)
    {
        parent::__construct($entry);
        $this->source = $source;
        $this->loaded['ignored_fields'] = true;
        if (! empty($entry['ignored_fields'])) {
            foreach ($entry['ignored_fields'] as $field) {
                $this->loaded[$field] = true;
            }
        }
    }

    public function offsetGet($name): mixed
    {
        $this->load($name);
        if (isset($this[$name])) {
            return parent::offsetGet($name);
        }
        return null;
    }

    public function offsetExists($name): bool
    {
        return parent::offsetExists($name);
    }

    private function load($name)
    {
        if (isset($this->loaded[$name])) {
            return;
        }

        $this->loaded[$name] = true;
        $data = $this->source->getData($this->getArrayCopy(), $name);

        foreach ($data as $key => $name) {
            $this[$key] = $name;
        }
    }
}
