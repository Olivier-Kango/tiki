<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for Text
 *
 * Letter key: ~t~
 *
 */
class Tracker_Field_Text extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            't' => [
                'name' => tr('Text Field'),
                'description' => tr('Single-line text input'),
                'help' => 'Text-Tracker-Field',
                'prefs' => ['trackerfield_text'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['d', 'D', 'R', 'M', 'm', 't', 'a', 'L'],
                'params' => [
                    'samerow' => [
                        'name' => tr('Same Row'),
                        'description' => tr('Display the field name and input on the same row.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'default' => 1,
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'size' => [
                        'name' => tr('Display Size'),
                        'description' => tr('Visible size of the field, in characters.'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                    ],
                    'prepend' => [
                        'name' => tr('Prepend'),
                        'description' => tr('Text to prepend when displaying the value.'),
                        'filter' => 'text',
                        'legacy_index' => 2,
                    ],
                    'append' => [
                        'name' => tr('Append'),
                        'description' => tr('Text to append when displaying the value.'),
                        'filter' => 'text',
                        'legacy_index' => 3,
                    ],
                    'max' => [
                        'name' => tra('Maximum Length'),
                        'description' => tra('Maximum amount of characters to store.'),
                        'filter' => 'int',
                        'legacy_index' => 4,
                    ],
                    'autocomplete' => [
                        'name' => tra('Autocomplete'),
                        'description' => tra('Enable autocompletion while typing in the field.'),
                        'filter' => 'alpha',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'legacy_index' => 5,
                    ],
                    'exact' => [
                        'name' => tr('Index exact value'),
                        'description' => tr('In addition to indexing the content of the field, also index it as an identifier in tracker_field_{perm name}_exact. This option is not available for multilingual fields. Mostly for identifiers like product codes or ISBN numbers.'),
                        'filter' => 'alpha',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'legacy_index' => 6,
                    ],
                    'labelasplaceholder' => [
                        'name' => tr('Use label as placeholder'),
                        'description' => tr('Display the field name as a placeholder in the input field instead of separate label.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'default' => 0,
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $data = $this->processMultilingual($requestData, $this->getInsertId());

        return $data;
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate('trackerinput/text.tpl', $context);
    }

    public function renderInnerOutput($context = [])
    {
        $pre = '';
        $post = '';

        $context = array_merge([
            'list_mode' => '',
        ], $context);

        if ($this->getConfiguration('type') == 't') {
            if ($this->getOption('prepend')) {
                if ($context['list_mode'] !== 'csv') {
                    $pre = '<span class="formunit">' . $this->getOption('prepend') . '</span>';
                } else {
                    $pre = $this->getOption('prepend');
                }
            }

            if ($this->getOption('append')) {
                if ($context['list_mode'] !== 'csv') {
                    $post = '<span class="formunit">' . $this->getOption('append') . '</span>';
                } else {
                    $post = $this->getOption('append');
                }
            }
        }
        $value = parent::renderInnerOutput($context);
        if ($this->getConfiguration('type') === 't' && $context['list_mode'] !== 'csv') {   // not TextAreas or csv output
            $value = smarty_modifier_escape($value);
        }
        if (
            $this->getConfiguration('isMultilingual') == 'y' &&
            $context['list_mode'] === 'csv' &&
            $_GET['controller'] === 'tracker' &&
            $_GET['action'] === 'export_items'
        ) {
            $value = str_replace('"', '\"', json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        return $pre . $value . $post;
    }

    public function renderOutput($context = [])
    {
        if (isset($context['history']) && $context['history'] == 'y' && is_array($this->getConfiguration('value'))) {
            return $this->renderTemplate('trackeroutput/text_history.tpl');
        } else {
            return parent::renderOutput($context);
        }
    }

    protected function processMultilingual($requestData, $id_string)
    {
        global $prefs;
        $language = $prefs['language'];
        $multilingual = $this->getConfiguration('isMultilingual') == 'y';

        if (! isset($requestData[$id_string])) {
            $value = $this->getValue();
            if ($multilingual) {
                if (! is_array($value)) {
                    $newValue = json_decode($value, true);
                } else {
                    $newValue = false;
                }

                if ($newValue !== false && ! is_null($newValue)) {
                    $value = $newValue;
                }
            }
        } else {
            $jit = new JitFilter($requestData);
            $value = $jit->$id_string->wikicontent();
        }

        if (is_array($value)) {
            $thisVal = $value[$language] ?? '';
        } else {
            $thisVal = $value;
        }

        $data = [
            'value' => $value,
            'pvalue' => trim($this->attemptParse($thisVal), "\n"),
            'lingualvalue' => [],
            'lingualpvalue' => [],
        ];

        if ($multilingual) {
            // When multilingual is turned on after data exists, this may well be a string
            // rather than an array. Assume it's empty, $thisVal will replace all values.
            if (! is_array($value)) {
                $value = [];
            }
            foreach ($prefs['available_languages'] as $num => $lang) { // TODO add a limit on number of langs - 40+ makes this blow up
                if (! isset($value[$lang])) {
                    $value[$lang] = $thisVal;
                }

                $data['lingualvalue'][$num] = [
                    'id' => str_replace(['[', ']'], ['_', ''], $this->getInsertId()) . '_' . $lang,
                    'lang' => $lang,
                    'value' => $value[$lang],
                ];
                $data['lingualpvalue'][$num] = [
                    'lang' => $lang,
                    'value' => $this->attemptParse($value[$lang]),
                ];
            }
        }

        return $data;
    }

    protected function attemptParse($text)
    {
        return $text;
    }

    public function handleSave($value, $oldValue)
    {
        if (is_array($value)) {
            return [
                'value' => json_encode(array_map([$this, 'filterValue'], $value)),
            ];
        } else {
            return [
                'value' => $this->filterValue($value),
            ];
        }
    }

    public function filterValue($value)
    {
        $length = $this->getOption('max');

        if ($length) {
            $f_len = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
            $f_substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';

            if ($f_len($value) > $length) {
                return $f_substr($value, 0, $length);
            }
        }

        return $value;
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $fieldType = $this->getIndexableType();
        $baseKey = $this->getBaseKey();

        if ($this->getConfiguration('isMultilingual') == 'y') {
            if (! empty($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = implode("\n", $decoded);
                } else {
                    $decoded = [];
                }
            } else {
                $decoded = [];
            }

            $data = [$baseKey => $typeFactory->$fieldType($value)];
            foreach ($decoded as $lang => $content) {
                $data[$baseKey . '_' . $lang] = $typeFactory->$fieldType($content);
                /*
                I'm leaving this in for now because I don't have time to fix it.  This is from a hack in https://gitlab.com/tikiwiki/tiki/-/merge_requests/4877/diffs?commit_id=8c1bd7b40b2043c42a077276ba1265b1252ac900

                It relies on assuming that without a base prefix this will be merged as a key matching title, but the raw value of title is handled handled directly in TrackerItemSource.php

                So, either we expose multilingual values in an API on fields, or we handle the title field directly in EVERY field that can be used for title.  And from TrackerLib::get_isMain_value(), that is not just text fields... benoitg - 2024-10-04
                */
                if ($this->isMainField()) {
                    $data['title_' . $lang] = $typeFactory->$fieldType($content);
                }
            }

            return $data;
        } else {
            $data = [
                $baseKey => $typeFactory->$fieldType($value),
            ];

            if ($this->getOption('exact') == 'y') {
                $data[$baseKey . '_exact'] = $typeFactory->identifier($value);
            }

            return $data;
        }
    }

    public function getProvidedFields(): array
    {
        global $prefs;
        $baseKey = $this->getBaseKey();

        $data = [$baseKey];

        if ($this->getConfiguration('isMultilingual') == 'y') {
            foreach ($prefs['available_languages'] as $lang) {
                $data[] = $baseKey . '_' . $lang;
                //See matching comment in getDocumentPart
                if ($this->isMainField()) {
                    $data[] = 'title_' . $lang;
                }
            }
        } elseif ($this->getOption('exact') == 'y') {
            $data[] = $baseKey . '_exact';
        }
        return $data;
    }

    public function getProvidedFieldTypes(): array
    {
        global $prefs;

        $baseKey = $this->getBaseKey();
        $fieldType = $this->getIndexableType();

        $data = [$baseKey => $fieldType];

        if ($this->getConfiguration('isMultilingual') == 'y') {
            foreach ($prefs['available_languages'] as $lang) {
                $data[$baseKey . '_' . $lang] = $fieldType;
            }
        } elseif ($this->getOption('exact') == 'y') {
            $data[$baseKey . '_exact'] = 'identifier';
        }

        return $data;
    }

    public function getGlobalFields(): array
    {
        global $prefs;
        $baseKey = $this->getBaseKey();

        $data = [$baseKey => true];

        if ($this->getConfiguration('isMultilingual') == 'y') {
            foreach ($prefs['available_languages'] as $lang) {
                $data[$baseKey . '_' . $lang] = true;
            }
        }

        return $data;
    }

    protected function getIndexableType()
    {
        return 'sortable';
    }

    public function isValid($ins_fields_data)
    {
        $value = $this->getValue();

        $validation = $this->getConfiguration('validation');
        $param = $this->getConfiguration('validationParam');
        $message = $this->getConfiguration('validationMessage');

        if (! $validation) {
            return true;
        }

        if ($validation === 'distinct' && empty($param)) {
            $param = "trackerId={$this->getConfiguration('trackerId')}&fieldId={$this->getConfiguration('fieldId')}&itemId={$this->getItemId()}";
        }

        $validators = TikiLib::lib('validators');
        $validators->setInput($value);
        $ret = $validators->validateInput($validation, $param);
        return $ret;
    }

    public function getTabularSchema()
    {
        global $prefs;
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $baseKey = $this->getBaseKey();
        $name = $this->getConfiguration('name');

        $renderLink = function ($lang) {
            return function ($value, $extra) use ($lang) {
                $smarty = TikiLib::lib('smarty');

                if (isset($extra['text'])) {
                    $value = $extra['text'];
                } elseif ($lang && isset($value[$lang])) {
                    $value = $lang;
                }

                if ($value) {
                    return smarty_function_object_link([
                        'type' => 'trackeritem',
                        'id' => $extra['itemId'],
                        'title' => $value,
                    ], $smarty->getEmptyInternalTemplate());
                } else {
                    return '';
                }
            };
        };

        if ('y' !== $this->getConfiguration('isMultilingual', 'n')) {
            $schema->addNew($permName, 'default')
                ->setLabel($name)
                ->setRenderTransform(function ($value) {
                    return $value;
                })
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = $value;
                })
                ;
            $schema->addNew($permName, 'link')
                ->setLabel($name)
                ->setPlainReplacement('default')
                ->addQuerySource('itemId', 'object_id')
                ->addIncompatibility($permName, 'default')
                ->setRenderTransform($renderLink(null))
                ;
            $schema->addNew($permName, 'binary')
                ->setLabel($name)
                ->setRenderTransform(function ($value) {
                    return hex2bin($value);
                })
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = bin2hex($value);
                })
                ;
        } else {
            $lang = $prefs['language'];
            $schema->addNew($permName, 'current')
                ->setLabel(tr('%0 (%1)', $name, $lang))
                ->setReadOnly(true)
                ->addQuerySource('text', "{$baseKey}_{$lang}")
                ->setRenderTransform(function ($value, $extra) use ($lang) {
                    if (isset($extra['text'])) {
                        return $extra['text'];
                    } elseif ($lang && isset($value[$lang])) {
                        return $value[$lang];
                    }
                })
                ;
            $schema->addNew($permName, "link-current")
                ->setLabel($name)
                ->setReadOnly(true)
                ->setPlainReplacement($lang)
                ->addQuerySource('itemId', 'object_id')
                ->addQuerySource('text', "{$baseKey}_{$lang}")
                ->setRenderTransform($renderLink($lang))
                ;

            foreach ($prefs['available_languages'] as $lang) {
                $schema->addNew($permName, $lang)
                    ->setLabel(tr('%0 (%1)', $name, $lang))
                    ->addQuerySource('text', "{$baseKey}_{$lang}")
                    ->setRenderTransform(function ($value, $extra) use ($lang) {
                        if (isset($extra['text'])) {
                            return $extra['text'];
                        } elseif ($lang && isset($value[$lang])) {
                            return $value[$lang];
                        }
                    })
                    ->setParseIntoTransform(function (&$info, $value) use ($permName, $lang) {
                        $info['fields'][$permName][$lang] = $value;
                    })
                    ;
                $schema->addNew($permName, "link-$lang")
                    ->setLabel($name)
                    ->setPlainReplacement($lang)
                    ->addQuerySource('itemId', 'object_id')
                    ->addQuerySource('text', "{$baseKey}_{$lang}")
                    ->addIncompatibility($permName, 'default')
                    ->addIncompatibility($permName, $lang)
                    ->setRenderTransform($renderLink($lang))
                    ;
            }
        }

        return $schema;
    }

    public function getFilterCollection()
    {
        global $prefs;

        $filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $generateFulltext = function ($field) {
            return function ($control, Search_Query $query) use ($field) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterContent($value, $field);
                }
            };
        };

        if ('y' !== $this->getConfiguration('isMultilingual', 'n')) {
            $filters->addNew($permName, 'fulltext')
                ->setLabel($name)
                ->setHelp(tr('Full-text search of the content of the field.'))
                ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_ft"))
                ->setApplyCondition($generateFulltext($baseKey))
                ;

            $filters->addNew($permName, 'initial')
                ->setLabel($name)
                ->setHelp(tr('Search for a value prefix.'))
                ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_init"))
                ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                    $value = $control->getValue();

                    if ($value) {
                        $query->filterInitial($value, $baseKey);
                    }
                })
                ;
            if ('y' === $this->getOption('exact', 'n')) {
                $filters->addNew($permName, 'exact')
                    ->setLabel($name)
                    ->setHelp(tr('Search for a precise value.'))
                    ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_em"))
                    ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                        $value = $control->getValue();

                        if ($value) {
                            $query->filterIdentifier($value, $baseKey . '_exact');
                        }
                    })
                    ;
            }
        } else {
            $language = $prefs['language'];
            $filters->addNew($permName, "fulltext-current")
                ->setLabel($name)
                ->setHelp(tr('Full-text search in the current language.'))
                ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_current_ft"))
                ->setApplyCondition($generateFulltext("{$baseKey}_{$language}"))
                ;

            $fields = [];
            foreach ($prefs['available_languages'] as $lang) {
                $field = "{$baseKey}_{$lang}";
                $fields[] = $field;

                $filters->addNew($permName, "fulltext-$lang")
                    ->setLabel(tr('%0 (%1)', $name, $lang))
                    ->setHelp(tr('Full-text search in a specific language (%0).', $lang))
                    ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_{$lang}_ft"))
                    ->setApplyCondition($generateFulltext($field))
                    ;
            }

            $filters->addNew($permName, "fulltext")
                ->setLabel(tr('%0 (any language)', $name))
                ->setHelp(tr('Full-text search in any language.'))
                ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_ft"))
                ->setApplyCondition($generateFulltext(implode(',', $fields)))
                ;
        }

        return $filters;
    }
}
