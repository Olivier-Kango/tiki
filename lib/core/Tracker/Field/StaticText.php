<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for Static text
 *
 * Letter key: ~S~
 *
 */
class Tracker_Field_StaticText extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'S' => [
                'name' => tr('Static Text'),
                'description' => tr('Display the field description as static text to present notes or additional instructions.'),
                'readonly' => true,
                'help' => 'Static-Text-Tracker-Field',
                'prefs' => ['trackerfield_statictext'],
                'tags' => ['basic'],
                'default' => 'y',
                'params' => [
                    'wikiparse' => [
                        'name' => tr('Wiki Parse'),
                        'description' => tr('Indicates if the description should be parsed as wiki syntax for formatting.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Handle line breaks as new lines only'),
                            1 => tr('Wiki Parse'),
                            2 => tr('Wiki Parse with Pretty Tracker replacements before parsing'),
                            3 => tr('Wiki Parse with Pretty Tracker replacements after parsing'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'max' => [
                        'name' => tr('Maximum Length (List)'),
                        'description' => tr('Maximum number of characters to be displayed in list mode'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        global $prefs;

        $value = $this->getConfiguration('description');

        if ($this->getOption('wikiparse') == 1) {
            $value = TikiLib::lib('parser')->parse_data($value);
        } elseif ($this->getOption('wikiparse') == 2 || $this->getOption('wikiparse') == 3) { // do pretty tracker replacements
            $definition = Tracker_Definition::get($this->getConfiguration('trackerId'));
            $itemData = $this->getItemData();

            if ($this->getOption('wikiparse') == 3) {
                $value = TikiLib::lib('parser')->parse_data($value);
            }

            preg_match_all('/\{\$f_(\w+)\}/', $value, $matches);

            foreach ($matches[1] as $fieldIdOrName) {
                $field = $definition->getField($fieldIdOrName);
                $fieldId = $field['fieldId'];

                if (isset($itemData[$fieldId])) {
                    $fieldValue = $itemData[$field['fieldId']];
                    $value = str_replace(['{$f_' . $fieldId . '}', '{$f_' . $field['permName'] . '}'], $fieldValue, $value);
                }
            }

            $value = str_replace('{$itemId}', $this->getItemId(), $value);
            if ($prefs['feature_wiki_argvariable'] === 'y') {
                // TODO add user, page etc
                $value = str_replace('{{itemId}}', $this->getItemId(), $value);
            }

            if ($this->getOption('wikiparse') == 2) {
                $value = TikiLib::lib('parser')->parse_data($value);
            }
        }

        return ['value' => $value];
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate('trackerinput/statictext.tpl', $context);
    }

    public function handleSave($value, $oldValue)
    {
        return [
            'value' => false,
        ];
    }

    public function importRemote($value)
    {
        return '';
    }

    public function exportRemote($value)
    {
        return '';
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }
}
