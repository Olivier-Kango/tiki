<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Declarative filters are to be defined prior to filtering. Various rules can
 * be defined in sequence. The first filter that applies will be used.
 */
class DeclFilter implements \Laminas\Filter\FilterInterface
{
    private $rules = [];

    /**
     * Builds a declarative filter object from a configuration array.
     *
     * @var array The configuration array
     * @var array The list of filtering rules that are disallowed
     * @return \DeclFilter
     * @see DeclFilter_ConfigureTest Unit tests contain samples of expected input
     */
    public static function fromConfiguration(array $configuration, array $reject = [])
    {
        $filter = new self();

        foreach ($configuration as $key => $list) {
            if (is_array($list) && is_numeric($key)) {
                foreach ($list as $method => $argument) {
                    $real = 'add' . ucfirst($method);

                    // Accept all methods beginning with 'add' except those that are disallowed
                    if (
                        method_exists($filter, $real)
                        && ! in_array($method, $reject)
                    ) {
                        $filter->$real($argument);
                    } else {
                        throw new Exception('Disallowed filtering rule: ' . $method);
                    }
                }
            } else {
                throw new Exception('Invalid input configuration structure', E_USER_WARNING);
            }
        }

        return $filter;
    }

    /**
     * Applies the registered filters on the provided data.
     */
    public function filter($data)
    {
        $keys = array_keys($data);

        foreach ($keys as $key) {
            // Loop until a matching filter is found
            foreach ($this->rules as $rule) {
                if ($rule->match($key)) {
                    $rule->apply($data, $key);
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Adds a series of filters to apply based on the key name.
     *
     * @var array Key-value pairs in which the key is the key to apply on and
     *            the value is the filter or filter name.
     */
    public function addStaticKeyFilters(array $filters)
    {
        require_once 'DeclFilter/StaticKeyFilterRule.php';
        $rule = new DeclFilter_StaticKeyFilterRule($filters);

        $this->rules[] = $rule;
    }

    /**
     * Adds a series of filters to apply based on the key name. Unlike
     * addStaticKeyFilters, filter will only be applied on array elements.
     * The filter will be applied on all array elements instead of the array
     * itself.
     *
     * @var array Key-value pairs in which the key is the key to apply on and
     *            the value is the filter or filter name.
     */
    public function addStaticKeyFiltersForArrays($filters)
    {
        require_once 'DeclFilter/StaticKeyFilterRule.php';
        $rule = new DeclFilter_StaticKeyFilterRule($filters);
        $rule->applyOnElements();

        $this->rules[] = $rule;
    }

    /**
     * Unset the specifies keys.
     */
    public function addStaticKeyUnset(array $keys)
    {
        require_once 'DeclFilter/StaticKeyUnsetRule.php';
        $rule = new DeclFilter_StaticKeyUnsetRule($keys);

        $this->rules[] = $rule;
    }

    /**
     * Adds a catch-all rule with a default filter. Will apply on all values
     * not covered by previous rules. This must be the last rule applied. The
     * filter will only be applied on array elements.
     *
     * @var mixed Filter object or filter name
     */
    public function addCatchAllFilter($filter)
    {
        require_once 'DeclFilter/CatchAllFilterRule.php';
        $rule = new DeclFilter_CatchAllFilterRule($filter);
        $rule->applyOnElements();

        $this->rules[] = $rule;
    }

    /**
     * Unset all remaining keys.
     */
    public function addCatchAllUnset($param = null)
    {
        require_once 'DeclFilter/CatchAllUnsetRule.php';
        $rule = new DeclFilter_CatchAllUnsetRule();

        $this->rules[] = $rule;
    }

    public function addKeyPatternFilters($filters)
    {
        require_once 'DeclFilter/KeyPatternFilterRule.php';
        $rule = new DeclFilter_KeyPatternFilterRule($filters);

        $this->rules[] = $rule;
    }

    public function addKeyPatternFiltersForArrays($filters)
    {
        require_once 'DeclFilter/KeyPatternFilterRule.php';
        $rule = new DeclFilter_KeyPatternFilterRule($filters);
        $rule->applyOnElements();

        $this->rules[] = $rule;
    }

    public function addKeyPatternUnset($keys)
    {
        require_once 'DeclFilter/KeyPatternUnsetRule.php';
        $rule = new DeclFilter_KeyPatternUnsetRule($keys);

        $this->rules[] = $rule;
    }
}
