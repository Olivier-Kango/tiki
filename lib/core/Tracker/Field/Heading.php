<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for Heading
 *
 * Letter key: ~h~
 *
 */
class Tracker_Field_Heading extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'h' => [
                'name' => tr('Heading'),
                'description' => tr('Display a heading between fields to delimit a section and allow visual folding of the fields.'),
                'readonly' => true,
                'help' => 'Heading-Tracker-Field',
                'prefs' => ['trackerfield_heading'],
                'tags' => ['basic'],
                'default' => 'y',
                'params' => [
                    'level' => [
                        'name' => tr('Heading Level'),
                        'description' => tr('Level of the heading to use for complex tracker structures needing multiple heading levels.'),
                        'default' => 3,
                        'filter' => 'int',
                        'legacy_index' => 0,
                    ],
                    'toggle' => [
                        'name' => tr('Section Toggle'),
                        'description' => tr('Default State'),
                        'filter' => 'alpha',
                        'default' => 'o',
                        'options' => [
                            '' => tr('No toggle'),
                            'o' => tr('Open'),
                            'c' => tr('Closed'),
                        ],
                        'legacy_index' => 1,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();

        return [];
    }

    public function renderInput($context = [])
    {
        return $this->renderOutput($context);
    }

    public function renderOutput($context = [])
    {
        if (isset($context['list_mode']) && $context['list_mode'] === 'csv') {
            return;
        }
        global $prefs;
        $headerlib = TikiLib::lib('header');

        $class = null;
        $level = (int)$this->getOption('level', 3);
        if ($level <= 0) {
            $level = 3;
        }
        $toggle = $this->getOption('toggle');
        $inTable = isset($context['inTable']) ? $context['inTable'] : '';
        $name = htmlspecialchars(tra($this->getConfiguration('name')));
        //to distinguish header description display on tiki-view_tracker.php versus when plugin tracker is used
        $desclass = isset($context['pluginTracker']) && $context['pluginTracker'] == 'y' ?
            'trackerplugindesc' : 'description';
        $data_toggle = '';
        if (($toggle === 'o' || $toggle === 'c')) {
            $class = ' ' . ($toggle === 'c' ? 'trackerHeaderClose' : 'trackerHeaderOpen');
            $data_toggle = 'data-bs-toggle="' . $toggle . '"';
        }
        if ($inTable) {
            $js = '
(function() {
    var processTrackerPageForHeaders = function( $div ) {
        if ($(".hdrField", $div).length) {    // check
            var $hdrField = $(".hdrField", $div).first();
            var level = $hdrField.data("level");
            var name = $hdrField.data("name");
            var toggle = $hdrField.data("toggle");

            $hdr = $("<h" + level + ">").text(name.trim());

            if (toggle) {
                var $section = $div.nextUntil(":not(div)");
                $hdr.on("click", function(){
                    $section.toggle();
                    var $i = $("i", this);
                    if ($i.hasClass("fa-chevron-right")) {
                        $i.replaceWith("<i class=\"fas fa-chevron-down\"></i>");
                    } else {
                        $i.replaceWith("<i class=\"fas fa-chevron-right\"></i>");
                    }
                });
                if (toggle === "c") {
                    $hdr.append("<small> <i class=\"fas fa-chevron-right\"></i></small>");
                    $section.hide();
                } else {
                    $hdr.append("<small> <i class=\"fas fa-chevron-down\"></i></small>");
                }
            }
            $div.replaceWith($hdr);
            return false;
        }
    }
    $(".hdrField").parents(".tracker-field-group row").each(function() {
        processTrackerPageForHeaders($(this));
    });
})();';
        } else {
            $js = '';   // TODO div mode for plugins or something
        }
        $headerlib->add_jq_onready($js);

        // just a marker for jQ to find
        $html = '<span class="hdrField' . $class . '" data-level="' . $level . '" ' . ' data-name="' . $name . '" '
            . $data_toggle . ' style="display:none;"></span>';

        return $html;
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
