<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ResultSet extends ArrayObject implements JsonSerializable
{
    private $count;
    private $estimate;
    private $offset;
    private $maxRecords;

    protected $highlightHelper;
    private $filters = [];
    private $id;
    private $tsOn;
    private $tsettings;
    public $errorInQuery = '';
    public $didYouMean = '';

    public static function create($list): self
    {
        if ($list instanceof self) {
            return $list;
        } else {
            return new self($list, count($list), 0, count($list));
        }
    }

    public function __construct($result, $count, $offset, $maxRecords)
    {
        parent::__construct($result);

        $this->count = $count;
        $this->estimate = $count;
        $this->offset = $offset;
        $this->maxRecords = $maxRecords;
        $this->checkNestedObjectPerms();
        $this->assignRowIndex();
    }

    public function replaceEntries($list)
    {
        $return = new self($list, $this->count, $this->offset, $this->maxRecords);
        $return->estimate = $this->estimate;
        $return->filters = $this->filters;
        $return->highlightHelper = $this->highlightHelper;
        $return->id = $this->id;
        $return->tsOn = $this->tsOn;
        $return->count = $this->count;
        $return->tsettings = $this->tsettings;
        $return->didYouMean = $this->didYouMean;

        return $return;
    }

    public function append($list): void
    {
        foreach ($list as $item) {
            parent::append($item);
        }
        $this->count += count($list);
        $this->maxRecords += count($list);
        $this->checkNestedObjectPerms();
        $this->assignRowIndex();
    }

    public function setHighlightHelper(Laminas\Filter\FilterInterface $helper)
    {
        $this->highlightHelper = $helper;
    }

    public function setEstimate($estimate)
    {
        $this->estimate = (int) $estimate;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTsOn($tsOn)
    {
        $this->tsOn = $tsOn;
    }

    public function setTsSettings($tsettings)
    {
        $this->tsettings = $tsettings;
    }

    public function getTsOn()
    {
        return $this->tsOn;
    }

    public function getTsSettings()
    {
        return $this->tsettings;
    }

    public function getDidYouMean()
    {
        return $this->didYouMean;
    }
    public function getEstimate()
    {
        return $this->estimate;
    }

    public function getMaxRecords()
    {
        return $this->maxRecords;
    }

    public function setDidYouMean($didYouMean)
    {
        $this->didYouMean = $didYouMean;
    }

    public function setMaxResults($max)
    {
        $current = $this->exchangeArray([]);
        $this->maxRecords = $max;
        $this->exchangeArray(array_slice($current, 0, $max));
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function highlight($content)
    {
        /* These heuristics seem to date from a long time ago, and did not work properly with most trackers at all - benoitg - 2024-03-11

        if ($this->highlightHelper) {
        // Build the content string based on heuristics
        $text = '';
        foreach ($content as $key => $value) {
            if (
                $key != 'object_type' // Skip internal values
                && $key != 'object_id'
                && $key != 'parent_object_type'
                && $key != 'parent_object_id'
                && $key != 'relevance'
                && $key != 'score'
                && $key != 'url'
                && $key != 'title'
                && $key != 'title_initial'
                && $key != 'title_firstword'
                && $key != 'description'
                && ! empty($value) // Skip empty
                && ! is_array($value) // Skip arrays, multivalues fields are not human readable
                && ! preg_match('/token[a-z]{8,}/', $value) // tokens
                && ! preg_match('/^\s*\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\s*$/', $value)  // dates
                && ! preg_match('/^[\w-]+$/', $value)
            ) { // Skip anything that looks like a single token
                $text .= "\n$value";
            }
        }

        if (! empty($text)) {
            var_dump($content);
            die;
            return $this->highlightHelper->filter($text);
        }
        }*/

        if (! empty($content['contents'])) {
            return $this->highlightHelper->filter($content['contents']);
        }
    }

    public function hasMore()
    {
        return $this->count > $this->offset + $this->maxRecords;
    }

    public function getFacet(Search_Query_Facet_Interface $facet)
    {
        foreach ($this->filters as $filter) {
            if ($filter->isFacet($facet)) {
                return $filter;
            }
        }
    }

    public function getFacets()
    {
        return $this->filters;
    }

    public function addFacetFilter(Search_ResultSet_FacetFilter $facet)
    {
        $this->filters[$facet->getName()] = $facet;
    }

    public function groupBy($field, array $collect = [])
    {
        $out = [];
        foreach ($this as $entry) {
            if (! isset($entry[$field])) {
                $out[] = $entry;
            } else {
                $value = $entry[$field];
                if (! isset($out[$value])) {
                    $newentry = $entry;
                    $newentry[$field] = array_fill_keys($collect, []);
                    $out[$value] = $newentry;
                }

                foreach ($collect as $key) {
                    if (isset($entry[$key])) {
                        $out[$value][$field][$key][] = $entry[$key];
                        $out[$value][$field][$key] = array_unique($out[$value][$field][$key]);
                    }
                }
            }
        }

        $this->exchangeArray($out);
    }

    public function aggregate(array $fields = [], array $totals = [])
    {
        $out = [];
        foreach ($this as $entry) {
            $values = array_map(function ($field) use ($entry) {
                return isset($entry[$field]) ? $entry[$field] : '';
            }, $fields);
            $key = implode('', $values);
            if (! isset($out[$key])) {
                $out[$key] = array_combine($fields, $values);
                $out[$key]['aggregate_fields'] = $out[$key];
                $out[$key]['object_type'] = 'aggregate';
                $out[$key]['object_id'] = $key;
                $out[$key]['title'] = implode(' ', $values);
                foreach ($totals as $field) {
                    $out[$key][$field] = 0;
                }
            }
            foreach ($totals as $field) {
                if (isset($entry[$field])) {
                    $out[$key][$field] += $entry[$field];
                }
            }
        }

        $this->exchangeArray($out);
    }

    public function applyTransform(callable $transform)
    {
        foreach ($this as & $entry) {
            $entry = $transform($entry);
        }
    }
    /**  When relations have indexed relation objects, remove them from the resultset if user doesn't have
     * proper permissions */
    public function checkNestedObjectPerms()
    {
        global $user;
        $user_groups = array_keys(TikiLib::lib('user')->get_user_groups_inclusion($user));
        if (empty($user_groups)) {
            $user_groups = ['Anonymous'];
        }
        foreach ($this as &$item) {//for each element in resultset
            if (isset($item['relation_objects'])) {
                if (is_string($item['relation_objects'])) {
                    $objects = json_decode($item['relation_objects'], true);
                } else {
                    $objects = $item['relation_objects'];
                }
                foreach ($objects as $key => $obj) {
                    $in_group = array_intersect($obj->allowed_groups, $user_groups);
                    $in_user = in_array($user, $obj->allowed_users);
                    if (! $in_group && ! $in_user) {
                        unset($objects[$key]);
                    }
                }
                $item['relation_objects'] = array_values($objects); //rebase keys
            }
        }
    }

    public function assignRowIndex()
    {
        $current = 0;
        foreach ($this as &$item) {
            $current++;
            if (is_iterable($item)) {
                $item['row_index'] = $this->offset + $current;
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'count' => $this->count,
            'offset' => $this->offset,
            'maxRecords' => $this->maxRecords,
            'result' => array_values($this->getArrayCopy()),
        ];
    }
}
