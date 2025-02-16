<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Controller to process requests from the custom search plugin using the list plugin to display results
// Refactored from customsearch_ajax.php for Tiki


class Services_Search_CustomSearchController
{
    private $textranges = [];
    private $dateranges = [];
    private $distances = [];
    private $contentFields;

    public function setUp()
    {
        Services_Exception_Disabled::check('wikiplugin_list');
        Services_Exception_Disabled::check('wikiplugin_customsearch');
        Services_Exception_Disabled::check('feature_search');

        $this->contentFields = TikiLib::lib('tiki')->get_preference('unified_default_content', ['contents'], true);
    }

    public function action_customsearch($input)
    {
        global $prefs;

        $this->textranges = [];
        $this->dateranges = [];
        $this->distances = [];

        $cachelib = TikiLib::lib('cache');
        $definition = $input->definition->word();
        if (empty($definition) || ! $definition = $cachelib->getSerialized($definition, 'customsearch')) {
            $smarty = \TikiLib::lib('smarty');
            if (isset($_SERVER['HTTP_REFERER']) && ! empty($_SERVER['HTTP_REFERER'])) {
                $smarty->assign('url', $_SERVER['HTTP_REFERER']);
                $value = $smarty->fetch('search_customsearch/cache_expired.tpl');
                return ['html' => $value];
            } else {
                Feedback::errorPage(['mes' => "unauthorized direct access to personalized search"]);
            }
        }

        /** @var Search_Query $query */
        $query = $definition['query'];
        /** @var Search_Formatter_Builder $builder */
        $builder = $definition['builder'];
        /** @var Search_Query_FacetWikiBuilder $facetsBuilder */
        $facetsBuilder = $definition['facets'];

        $tsettings = $definition['tsettings'] ?? null;
        $tsret = $definition['tsret'];

        $matches = WikiParser_PluginMatcher::match($definition['data']);
        $builder->apply($matches);

        $adddata = json_decode($input->adddata->text(), true);

        $recalllastsearch = $input->recalllastsearch->int() ? true : false;

        $id = $input->searchid->text();
        if (empty($id)) {
            $id = '0';
        }

        if ($recalllastsearch && isset($_SESSION["customsearch_$id"])) {
            unset($_SESSION["customsearch_$id"]);
        }
        if ($input->sort_mode->text()) {
            if ($recalllastsearch) {
                $_SESSION["customsearch_$id"]["sort_mode"] = $input->sort_mode->text();
            }
            $query->setOrder($input->sort_mode->text());
        }
        if ($input->maxRecords->int()) {
            if ($recalllastsearch) {
                $_SESSION["customsearch_$id"]["maxRecords"] = $input->maxRecords->int();
            }
            $maxRecords = $input->maxRecords->int();    // pass request data required by list
        } else {
            $maxRecords = $prefs['maxRecords'];
        }
        if ($input->offset->int()) {
            if ($recalllastsearch) {
                $_SESSION["customsearch_$id"]["offset"] = $input->offset->int();
            }
            $offset = $input->offset->int();
        } else {
            $offset = 0;
        }
        $query->setRange($offset, $maxRecords);

        if ($adddata) {
            foreach ($adddata as $fieldid => $d) {
                $config = $d['config'];
                $name = $d['name'];
                $value = $d['value'];
                $group = empty($config['_group']) ? null : $config['_group'];

                // save values entered as defaults while session lasts
                if (empty($value) && $value != 0) {
                    $value = '';        // remove false or null
                }
                if ($recalllastsearch) {
                    $_SESSION["customsearch_$id"][$fieldid] = $value;
                }

                if (empty($config['type'])) {
                    $config['type'] = $name;
                }

                $filter = 'content'; //default
                if (isset($config['_filter']) || $name == 'categories' || $name == 'daterange' || $name == 'distance') {
                    if ($config['_filter'] == 'language') {
                        $filter = 'language';
                    } elseif ($config['_filter'] == 'type') {
                        $filter = 'type';
                    } elseif ($config['_filter'] == 'categories' || $name == 'categories') {
                        $filter = 'categories';
                    } elseif ($name == 'daterange') {
                        $filter = 'daterange';
                    } elseif ($config['_filter'] === 'multivalue') {
                        $filter = 'multivalue';
                    } elseif ($config['_filter'] === 'exact') {
                        $filter = 'exact';
                    } elseif ($name == 'distance') {
                        $filter = 'distance';
                        if (! $input->sort_mode->text()) {
                            $config['sort'] = true;
                        }
                    }
                }

                if (is_array($value) && count($value) > 1) {
                    $value = implode(' ', $value);
                } elseif (is_array($value)) {
                    $value = current($value);
                }

                $function = "cs_dataappend_{$filter}";
                if (method_exists($this, $function)) {
                    $this->$function($query->getSubQuery($group), $config, $value);
                }
            }

            foreach ($this->textranges as $info) {
                if (count($info['values']) == 2) {
                    $from = array_shift($info['values']);
                    $to = array_shift($info['values']);
                    $info['query']->filterTextRange($from, $to, $info['config']['_field']);
                }
            }

            foreach ($this->dateranges as $info) {
                if (count($info['values']) == 2) {
                    $from = array_shift($info['values']);
                    $to = array_shift($info['values']);
                    $info['query']->filterRange($from, $to, $info['config']['_field']);
                }
            }
        }

        if ($prefs['storedsearch_enabled'] == 'y' && $queryId = $input->store_query->int()) {
            // Store prior to adding
            $storedsearchlib = TikiLib::lib('storedsearch');
            $storeResult = $storedsearchlib->storeUserQuery($queryId, $query);

            if (! $storeResult) {
                throw new Services_Exception('Failed to store the query.', 500);
            }
        }

        /** @var UnifiedSearchLib $unifiedsearchlib */
        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        $unifiedsearchlib->initQuery($query); // Done after cache because permissions vary


        if ($prefs['unified_highlight_results'] === 'y') {
            $query->applyTransform(
                new \Search\ResultSet\UrlHighlightTermsTransform(
                    $query->getTerms()
                )
            );
        }

        $facetsBuilder->build($query, $unifiedsearchlib->getFacetProvider());

        $index = $unifiedsearchlib->getIndex();
        require_once 'lib/wiki/pluginslib.php';
        PluginsLibUtil::handleDownload($query, $index, $matches, $input->asArray());
        $resultSet = $query->search($index);
        if (! empty($_SESSION['tikifeedback']) && $_SESSION['tikifeedback'][0]['type'] === 'error') {
            Feedback::sendHeaders();
        } else {
            $resultSet->setTsSettings($builder->getTsSettings());
            $resultSet->setId('wpcs-' . $id);
            $resultSet->setTsOn($tsret['tsOn']);

            $formatter = $builder->getFormatter();
            $results = $formatter->format($resultSet);

            $parserLib = TikiLib::lib('parser');
            $results = $parserLib->searchFilePreview($results, true);
            $results = $parserLib->parse_data($results, ['is_html' => true, 'skipvalidation' => true]);

            return ['html' => $results];
        }
    }

    private function cs_dataappend_language(Search_Query $query, $config, $value)
    {
        if ($config['type'] != 'text') {
            if (! empty($config['_value'])) {
                $value = $config['_value'];
                $query->filterLanguage($value);
            } elseif ($value) {
                $query->filterLanguage($value);
            }
        }
    }

    private function cs_dataappend_multivalue(Search_Query $query, $config, $value)
    {
        if (! empty($config['_value'])) {
            $value = $config['_value'];
            $query->filterMultivalue($value, $config['_field']);
        } elseif ($value) {
            $query->filterMultivalue($value, $config['_field']);
        }
    }

    private function cs_dataappend_type(Search_Query $query, $config, $value)
    {
        if ($config['type'] != 'text') {
            if (! empty($config['_value'])) {
                $value = $config['_value'];
                $query->filterType($value);
            } elseif ($value) {
                $query->filterType($value);
            }
        }
    }

    private function cs_dataappend_content(Search_Query $query, $config, $value)
    {
        if (( isset($config['_textrange']) || isset($config['_daterange']) ) && ( isset($config['_emptyfrom']) || isset($config['_emptyto']) )  && $value <= '') {
            $value = isset($config['_emptyfrom']) ? $config['_emptyfrom'] : $config['_emptyto'];
        }
        if ($value > '') {
            if (isset($config['_textrange'])) {
                $this->cs_handle_textrange($config['_textrange'], $query, $config, $value);
            } elseif (isset($config['_daterange'])) {
                $this->cs_handle_daterange($config['_daterange'], $query, $config, $value);
            } elseif ($config['type'] == 'checkbox') {
                if (empty($config['_field'])) {
                    return;
                }
                if (! empty($config['_value'])) {
                    if ($config['_value'] == 'n') {
                        $config['_value'] = 'NOT y';
                    }
                    $query->filterContent($config['_value'], $config['_field']);
                } else {
                    $query->filterContent('y', $config['_field']);
                }
            } elseif ($config['type'] == 'radio' && ! empty($config['_value'])) {
                if (empty($config['_field'])) {
                    $query->filterContent($config['_value']);
                } else {
                    $query->filterContent($config['_value'], $config['_field']);
                }
            } else {
                if ($config['type'] == 'select' && ! empty($config['multiple']) && ! empty($config['_operator'])) {
                    $value = str_replace(' ', " {$config['_operator']} ", $value);
                }
                // covers everything else including radio that have no _value set (use sent value)
                if (empty($config['_field'])) {
                    $query->filterContent($value, $this->contentFields);
                } else {
                    $query->filterContent($value, $config['_field']);
                }
            }
        }
    }

    private function cs_dataappend_exact(Search_Query $query, $config, $value)
    {
        $query->filterIdentifier($value, $config['_field']);
    }

    private function cs_handle_textrange($rangeName, Search_Query $query, $config, $value)
    {
        if (! isset($this->textranges[$rangeName])) {
            $this->textranges[$rangeName] = [
                'query' => $query,
                'config' => $config,
                'values' => [],
            ];
        }

        if (isset($config['_emptyother']) && isset($config['_emptyfrom'])) {
            // is "from" value
            if (count($this->textranges[$rangeName]['values']) == 0) {
                $this->textranges[$rangeName]['values'][] = $config['_emptyother'];
            } elseif (count($this->textranges[$rangeName]['values']) == 2) {
                array_shift($this->textranges[$rangeName]['values']);
            }
            array_unshift($this->textranges[$rangeName]['values'], $value);
        } elseif (isset($config['_emptyother']) && isset($config['_emptyto'])) {
            // is "to" value
            if (count($this->textranges[$rangeName]['values']) == 0) {
                $this->textranges[$rangeName]['values'][] = $config['_emptyother'];
            } elseif (count($this->textranges[$rangeName]['values']) == 2) {
                array_pop($this->textranges[$rangeName]['values']);
            }
            $this->textranges[$rangeName]['values'][] = $value;
        } else {
            $this->textranges[$rangeName]['values'][] = $value;
        }
    }

    private function cs_handle_daterange($rangeName, Search_Query $query, $config, $value)
    {
        if (! isset($this->dateranges[$rangeName])) {
            $this->dateranges[$rangeName] = [
                'query' => $query,
                'config' => $config,
                'values' => [],
            ];
        }

        if (isset($config['_emptyother']) && isset($config['_emptyfrom'])) {
            // is "from" value
            if (count($this->dateranges[$rangeName]['values']) == 0) {
                $this->dateranges[$rangeName]['values'][] = $config['_emptyother'];
            } elseif (count($this->dateranges[$rangeName]['values']) == 2) {
                array_shift($this->dateranges[$rangeName]['values']);
            }
            array_unshift($this->dateranges[$rangeName]['values'], $value);
        } elseif (isset($config['_emptyother']) && isset($config['_emptyto'])) {
            // is "to" value
            if (count($this->dateranges[$rangeName]['values']) == 0) {
                $this->dateranges[$rangeName]['values'][] = $config['_emptyother'];
            } elseif (count($this->dateranges[$rangeName]['values']) == 2) {
                array_pop($this->dateranges[$rangeName]['values']);
            }
            $this->dateranges[$rangeName]['values'][] = $value;
        } else {
            $this->dateranges[$rangeName]['values'][] = $value;
        }
    }

    private function cs_dataappend_categories(Search_Query $query, $config, $value)
    {
        if (isset($config['_filter']) && $config['_filter'] == 'categories' && $config['type'] != 'text') {
            if (! empty($config['_value'])) {
                $value = $config['_value'];
            }
        } elseif (! isset($config['_style'])) {
            return;
        }
        if ($value) {
            $deep = (isset($config['_showdeep']) && $config['_showdeep'] != 'n') || (isset($config['_deep']) && $config['_deep'] != 'n');
            $query->filterCategory($value, $deep);
        }
    }

    private function cs_dataappend_daterange(Search_Query $query, $config, $value)
    {
        if ($vals = explode(',', $value)) {
            if (count($vals) == 2) {
                $from = $vals[0];
                $to = $vals[1];
                if (
                    (empty($config['_showtime']) || $config['_showtime'] === 'n') &&
                    (empty($config['_toendofday']) || $config['_toendofday'] === 'y')
                ) {
                    $to += (24 * 3600) - 1; // end date should be the end of the day, not the beginning
                }
                if (! empty($config['_field'])) {
                    $field = $config['_field'];
                } else {
                    $field = 'modification_date';
                }
                $query->filterRange($from, $to, $field);
            }
        }
    }

    private function cs_dataappend_distance(Search_Query $query, $config, $value)
    {
        if ($vals = array_filter(preg_split('/,/', $value))) {  // ignore if dist, lat or lon is missing
            if (count($vals) == 3) {
                $distance = $vals[0];
                $lat = $vals[1];
                $lon = $vals[2];
                if (! empty($config['_field'])) {
                    $field = $config['_field'];
                } else {
                    $field = 'geo_point';
                }
                $query->filterDistance($distance, $lat, $lon, $field);

                if (! empty($config['sort']) || ! empty($config['_mode'])) {
                    $order = empty($config['_mode']) ? 'asc' : $config['_mode'];
                    $sortOrder = new Search\Query\OrderClause(new Search\Query\Order(
                        $field,
                        'distance',
                        $order,
                        [
                            'distance' => $distance,
                            'lat' => $lat,
                            'lon' => $lon,
                        ]
                    ));
                    $query->setOrder($sortOrder);
                }
            }
        }
    }
}
