<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for TextArea
 *
 * Letter key: ~a~
 *
 */
class Tracker_Field_TextArea extends Tracker_Field_Text
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'a' => [
                'name' => tr('Text Area'),
                'description' => tr('Multi-line text input'),
                'help' => 'Textarea-Tracker-Field',
                'prefs' => ['trackerfield_textarea'],
                'tags' => ['basic'],
                'default' => 'y',
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
                        'legacy_index' => 8,
                    ],
                    'toolbars' => [
                        'name' => tr('Toolbars'),
                        'description' => tr('Enable the toolbars as syntax helpers.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Disable'),
                            1 => tr('Enable'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'width' => [
                        'name' => tr('Width'),
                        'description' => tr('Size of the text area, in characters.'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                    ],
                    'height' => [
                        'name' => tr('Height'),
                        'description' => tr('Size of the text area, in lines.'),
                        'filter' => 'int',
                        'legacy_index' => 2,
                    ],
                    'max' => [
                        'name' => tr('Character Limit'),
                        'description' => tr('Maximum number of characters to be stored.'),
                        'filter' => 'int',
                        'legacy_index' => 3,
                    ],
                    'listmax' => [
                        'name' => tr('Display Limit (List)'),
                        'description' => tr('Maximum number of characters to be displayed in list mode before the value gets truncated.'),
                        'filter' => 'int',
                        'legacy_index' => 4,
                    ],
                    'wordmax' => [
                        'name' => tr('Word Count'),
                        'description' => tr('Limit the length of the text, in number of words.'),
                        'filter' => 'int',
                        'legacy_index' => 5,
                    ],
                    'distinct' => [
                        'name' => tr('Distinct Values'),
                        'description' => tr('All values in the field must be different.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'legacy_index' => 6,
                    ],
                    'wysiwyg' => [
                        'name' => tr('Use WYSIWYG'),
                        'description' => tr('Use a rich text editor instead of inputting plain text.'),
                        'default' => 'n',
                        'filter' => 'alpha',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'legacy_index' => 7,
                    ],

                ],
            ],
        ];
    }

    public function postSaveHook($value)
    {
        $itemId = (int)$this->getItemId();
        $fieldId = (int)$this->getFieldId();
        $permName = $this->getConfiguration('permName');
        $wikilib = TikiLib::lib('wiki');
        $wikilib->update_wikicontent_relations($value, 'trackeritemfield', sprintf("%d:%d", $itemId, $fieldId));
        $wikilib->update_wikicontent_links($value, 'trackeritemfield', sprintf("%d:%d", $itemId, $fieldId));
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();
        $data = $this->processMultilingual($requestData, $ins_id);

        global $user, $prefs;
        $language = $prefs['language'];
        $c = 0;
        if (isset($requestData[$ins_id])) {
            $value = (array) $data['value'];

            foreach ($value as $key => $val) {
                $newvalue = TikiLib::lib('parser')->process_save_plugins(
                    $val,
                    [
                        'type' => 'trackeritem',
                        'itemId' => $this->getItemId(),
                        'user' => $user,
                    ]
                );
                if ($newvalue !== $val) {
                    if (isset($data['lingualvalue'][$c])) {
                        $data['lingualvalue'][$c]['value'] = $newvalue;
                        $data['lingualpvalue'][$c]['value'] = $this->attemptParse($newvalue);
                        $data['value'][$data['lingualvalue'][$c]['lang']] = $newvalue;
                        if ($data['lingualvalue'][$c]['lang'] === $language) {
                            $data['pvalue'] = $data['lingualpvalue'][$c]['value'];
                        }
                    } else {
                        $data['value'] = $newvalue;
                        $data['pvalue'] = $this->attemptParse($newvalue);
                    }
                }
                $c++;
            }
        }

        return $data;
    }

    public function renderInput($context = [])
    {
        global $prefs;

        $cols = $this->getOption('width');
        $rows = $this->getOption('height');

        $data = [
            'toolbar' => $this->getOption('toolbars') ? 'y' : 'n',
            'cols' => ($cols >= 1) ? $cols : 80,
            'rows' => ($rows >= 1) ? $rows : 6,
            'keyup' => '',
        ];

        if ($this->getOption('wordmax')) {
            $data['keyup'] .= "wordCount({$this->getOption('wordmax')}, this, 'wcpt_{$this->getConfiguration('fieldId')}', '" . addcslashes(tr('Word Limit Exceeded'), "'") . "');";
        }
        if ($this->getOption('max')) {
            $data['keyup'] .= "charCount({$this->getOption('max')}, this, 'ccpt_{$this->getConfiguration('fieldId')}', '" . addcslashes(tr('Character Limit Exceeded'), "'") . "');";
        }
        $data['element_id'] = 'area_' . uniqid();
        if ($this->getOption('wysiwyg') === 'y') {    // wysiwyg
            $is_html = '<input type="hidden" name="allowhtml" value="' . ($prefs['wysiwyg_htmltowiki'] == 'n' ? '1' : '0') . '">';
        } else {
            $is_html = '';
        }
        return $this->renderTemplate('trackerinput/textarea.tpl', $context, $data) . $is_html;
    }

    public function renderInnerOutput($context = [])
    {
        $output = parent::renderInnerOutput($context);

        if (! empty($context['list_mode']) && $context['list_mode'] === 'y' && $this->getOption('listmax')) {
            return smarty_modifier_truncate(strip_tags($output), $this->getOption('listmax'));
        } elseif (! empty($context['isMain_context'])) {
            return strip_tags($output);
        } else {
            return $output;
        }
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
                $data["{$baseKey}_{$lang}"] = $typeFactory->$fieldType($content);
                $data["{$baseKey}_{$lang}_raw"] = $typeFactory->identifier($content);
            }

            return $data;
        } else {
            $data = [
                $baseKey => $typeFactory->$fieldType($value),
                "{$baseKey}_raw" => $typeFactory->identifier($value),
            ];

            return $data;
        }
    }

    public function getProvidedFields(): array
    {
        global $prefs;
        $baseKey = $this->getBaseKey();

        $data = [$baseKey, "{$baseKey}_raw"];

        if ($this->getConfiguration('isMultilingual') == 'y') {
            foreach ($prefs['available_languages'] as $lang) {
                $data[] = "{$baseKey}_{$lang}";
                $data[] = "{$baseKey}_{$lang}_raw";
            }
        }

        return $data;
    }

    public function getProvidedFieldTypes(): array
    {
        global $prefs;

        $baseKey = $this->getBaseKey();
        $fieldType = $this->getIndexableType();

        $data = [
            $baseKey => $fieldType,
            "{$baseKey}_raw" => 'identifier'
        ];

        if ($this->getConfiguration('isMultilingual') == 'y') {
            foreach ($prefs['available_languages'] as $lang) {
                $data["{$baseKey}_{$lang}"] = $fieldType;
                $data["{$baseKey}_{$lang}_raw"] = 'identifier';
            }
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

    public function getTabularSchema()
    {
        global $prefs;
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $baseKey = $this->getBaseKey();
        $name = $this->getConfiguration('name');

        $plain = function ($lang) {
            return function ($value, $extra) use ($lang) {
                if (isset($extra['text'])) {
                    $value = $extra['text'];
                } elseif ($lang && isset($value[$lang])) {
                    $value = $lang;
                }

                return $value;
            };
        };

        $render = function ($lang) use ($plain) {
            $f = $plain($lang);
            return function ($value, $extra) use ($f) {
                $value = $f($value, $extra);

                return $this->attemptParse($value);
            };
        };

        if ('y' !== $this->getConfiguration('isMultilingual', 'n')) {
            $schema->addNew($permName, "default")
                ->setLabel($name)
                ->setReadOnly(true)
                ->setPlainReplacement('default-raw')
                ->addQuerySource('text', "{$baseKey}_raw")
                ->setRenderTransform($render(null))
                ;
            $schema->addNew($permName, 'default-raw')
                ->setLabel($name)
                ->addQuerySource('text', "{$baseKey}_raw")
                ->setRenderTransform($plain(null))
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = $value;
                })
                ;
            // convert incoming html to wiki syntax and the opposite on export
            $schema->addNew($permName, 'wiki-html')
                ->setLabel($name)
                ->addQuerySource('text', "{$baseKey}_raw")
                ->setRenderTransform($render(null))
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = TikiLib::lib('edit')->parseToWiki($value);
                });
        } else {
            $lang = $prefs['language'];
            $schema->addNew($permName, "current")
                ->setLabel($name)
                ->setReadOnly(true)
                ->setPlainReplacement('current-raw')
                ->addQuerySource('text', "{$baseKey}_{$lang}_raw")
                ->setRenderTransform($render($lang))
                ;
            $schema->addNew($permName, 'current-raw')
                ->setLabel(tr('%0 (%1)', $name, $lang))
                ->setReadOnly(true)
                ->addIncompatibility($permName, 'current')
                ->addQuerySource('text', "{$baseKey}_{$lang}_raw")
                ->setRenderTransform($plain($lang))
                ;

            foreach ($prefs['available_languages'] as $lang) {
                $schema->addNew($permName, $lang)
                    ->setLabel($name)
                    ->setPlainReplacement("$lang-raw")
                    ->addQuerySource('text', "{$baseKey}_{$lang}_raw")
                    ->addIncompatibility($permName, 'current')
                    ->addIncompatibility($permName, 'current-raw')
                    ->setRenderTransform($render($lang))
                    ;
                $schema->addNew($permName, "$lang-raw")
                    ->setLabel(tr('%0 (%1)', $name, $lang))
                    ->addQuerySource('text', "{$baseKey}_{$lang}_raw")
                    ->addIncompatibility($permName, 'current')
                    ->addIncompatibility($permName, 'current-raw')
                    ->addIncompatibility($permName, $lang)
                    ->setRenderTransform($plain($lang))
                    ->setParseIntoTransform(function (&$info, $value) use ($permName, $lang) {
                        $info['fields'][$permName][$lang] = $value;
                    })
                    ;
            }
        }

        return $schema;
    }

    protected function attemptParse($text)
    {
        global $prefs;

        $parseOptions = [
            'objectType' => 'trackeritem',
            'objectId' => json_encode([$this->getItemId(), $this->getFieldId()]),
            'fieldName' => 'value'
        ];
        if ($this->getOption('wysiwyg') === 'y') {
            $parseOptions['is_html'] = ($prefs['wysiwyg_htmltowiki'] !== 'y');
        } else {
            $parseOptions['is_html'] = false;
        }
        return TikiLib::lib('parser')->parse_data($text, $parseOptions);
    }

    protected function getIndexableType()
    {
        return 'wikitext';
    }
}
