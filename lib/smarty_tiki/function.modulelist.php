<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function smarty_function_modulelist($params, $smarty)
{
    $moduleZones = $smarty->getTemplateVars('module_zones');

    global $prefs;
    if (empty($params['zone'])) {
        return tr("Missing %0 parameter", 'zone');
    }

    $zone = $params['zone'];

    $tag = "div";
    $class = 'modules';
    if (! empty($params['class'])) {
        $class .= ' ' . $params['class'];
        if (strpos($class, 'navbar') !== false) {
            $tag = 'nav';
        }
    }

    $id = $zone . '_modules';
    if (! empty($params['id'])) {
        $id = $params['id'];
    }

    $dir = '';
    if (Language::isRTL()) {
        $dir = ' dir="rtl"';
    }

    $content = '';
    $key = $zone . '_modules';

    if (isset($moduleZones[$key]) && is_array($moduleZones[$key])) {
        $content = implode(
            '',
            array_map(
                function ($module) {
                    $devices = $module["params"]["device"];
                    $moduleContent = (isset($module['data']) ? $module['data'] : '');

                    if(isset($devices) && is_array($devices) && !empty($devices)) {
                        $device_classes =  '';

                        if(!in_array('TABLET', $devices)){
                            $device_classes .= ' no_display_on_tablet';
                        }

                        if(!in_array('MOBILE', $devices)){
                            $device_classes .= ' no_display_on_mobile';
                        }

                        if(!in_array('LAPTOP', $devices)){
                            $device_classes .= ' no_display_on_laptop';
                        }

                        if(!in_array('DESKTOP', $devices)){
                            $device_classes .= ' no_display_on_desktop';
                        }

                        if(!in_array('PRINT', $devices)){
                            $device_classes .= ' no_display_on_print';
                        }

                        
                        $dom = new DOMDocument;
                        $dom->loadHTML($moduleContent);
                        $divs = $dom->getElementsByTagName('div');

                        $divs[0]->setAttribute('class', $divs[0]->getAttribute('class') .' '. $device_classes);

                        $moduleContent = $dom->saveHTML();
                    }
                    return $moduleContent;
                },
                $moduleZones[$key]
            )
        );
    }

    return <<<OUT
<$tag class="$class" id="$id"$dir>
    $content
</$tag>
OUT;
}
