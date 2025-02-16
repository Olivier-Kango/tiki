<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for simple fields:
 *
 * - email key ~m~
 */
class Tracker_Field_Email extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    private $type;

    public static function getManagedTypesInfo(): array
    {
        return [
            'm' => [
                'name' => tr('Email'),
                'description' => tr('Enable an email address to be input with the option of making it active.'),
                'help' => 'Email-Tracker-Field',
                'prefs' => ['trackerfield_email'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['d', 'D', 'R', 'M', 'm', 't', 'a', 'L'],
                'params' => [
                    'link' => [
                        'name' => tr('Link Type'),
                        'description' => tr('How the email address will be rendered.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Plain text'),
                            1 => tr('Encoded mailto link'),
                            2 => tr('Simple mailto link'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'watchopen' => [
                        'name' => tr('Watch Open'),
                        'description' => tr('Notify this address every time the status changes to open.'),
                        'filter' => 'alpha',
                        'options' => [
                            '' => tr('No'),
                            'o' => tr('Yes'),
                        ],
                        'legacy_index' => 1,
                    ],
                    'watchpending' => [
                        'name' => tr('Watch Pending'),
                        'description' => tr('Notify this address every time the status changes to pending.'),
                        'filter' => 'alpha',
                        'options' => [
                            '' => tr('No'),
                            'p' => tr('Yes'),
                        ],
                        'legacy_index' => 2,
                    ],
                    'watchclosed' => [
                        'name' => tr('Watch Closed'),
                        'description' => tr('Notify this address every time the status changes to closed.'),
                        'filter' => 'alpha',
                        'options' => [
                            '' => tr('No'),
                            'c' => tr('Yes'),
                        ],
                        'legacy_index' => 3,
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
                    'confirmed' => [
                        'name' => tr("Confirmation of the email address"),
                        'description' => tr("Enable this to ask for a confirmation of the email address with a second input field"),
                        'deprecated' => false,
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function build($type, $trackerDefinition, $fieldInfo, $itemData)
    {
        switch ($type) {
            case 'm':
                return new self($fieldInfo, $itemData, $trackerDefinition, 'email');
        }
    }

    public function __construct($fieldInfo, $itemData, $trackerDefinition, $type)
    {
        $this->type = $type;
        parent::__construct($fieldInfo, $itemData, $trackerDefinition);
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => $typeFactory->sortable($this->getValue()),
            "{$baseKey}_text" => $typeFactory->identifier($this->getValue()),
        ];
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey, "{$baseKey}_text"];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => 'sortable',
            "{$baseKey}_text" => 'identifier'
        ];
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey => true, "{$baseKey}_text" => true];
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();

        return [
            'value' => (isset($requestData[$ins_id]))
                ? $requestData[$ins_id]
                : $this->getValue(),
            'value_confirmed' => (isset($requestData[$ins_id . "_confirm"]))
            ? $requestData[$ins_id . "_confirm"]
            : $this->getValue(),
        ];
    }

    public function renderOutput($context = [])
    {
        $opt = $this->getOption('link');
        $value = $this->getValue();

        if ($opt == 0 || $context['list_mode'] == 'csv' || empty($value)) {
            return $value;
        } else {
            if ($opt == 1) {
                return TikiLib::scrambleEmail($value);
            } else {    // link == 2
                return "<a href=\"mailto:$value\">$value</a>";
            }
        }
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate("trackerinput/{$this->type}.tpl", $context);
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

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $smarty = TikiLib::lib('smarty');

        $schema->addNew($permName, 'default')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;
        $schema->addNew($permName, 'mailto')
            ->setLabel($this->getConfiguration('name'))
            ->setPlainReplacement('default')
            ->setRenderTransform(function ($value) {
                $escape = smarty_modifier_escape($value);
                return "<a href=\"mailto:$escape\">$escape</a>";
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = strip_tags($value);
            })
            ;

        return $schema;
    }

    public function getFilterCollection()
    {
        $filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();


        $filters->addNew($permName, 'lookup')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_lookup"))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterContent($value, $baseKey);
                }
            })
            ;

        return $filters;
    }
}
