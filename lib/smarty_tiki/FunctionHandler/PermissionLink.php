<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Generates a link to the object permission screen, and verifies if there are
 * active permissions to render the link differently as required.
 *
 * Important parameters: type and id, for the target object - otherwise global
 *                       permType, if different from type
 *                       title, the name of the object
 *
 * Almost mandatory: mode, display style of the button
 *                      glyph: simple fa (Font Awesome)
 *                      icon: classic tiki icon
 *                      link: plain text link (label)
 *                      text: glyph + label
 *                      button: button with label
 *                      button_link: button with label (btn-link)
 *
 * Occasional: label, alter the displayed text from default
               group, parameter to objectpermissions
               textFilter, parameter to objectpermissions
               showDisabled, parameter to objectpermissions
               addclass: add classes separated by spaces
 */
class PermissionLink extends Base
{
    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');
        $params = new \JitFilter($params);
        $type = $params->type->text();
        $objectType = $params->objectType->text();

        if (! $objectType) {
            $objectType = $type;
        }
        $id = $params->id->none();

        $objectlib = \TikiLib::lib('object');
        if (isset($params['type'], $params['id'])) {
            $arguments = [
                'objectType' => $objectType,
                'objectId' => $id,
                'permType' => $type,
                'objectName' => $params->title->none() ?: $objectlib->get_title($type, $id),
            ];
        } else {
            $arguments = [];
        }

        if ($params->permType->text()) {
            $arguments['permType'] = $params->permType->text();
        }

        if ($params->textFilter->text()) {
            $arguments['textFilter'] = $params->textFilter->text();
        }

        if ($params->group->groupname()) {
            $arguments['group'] = $params->group->groupname();
        }

        if ($params->showDisabled->word() == 'y') {
            $arguments['show_disabled_features'] = 'y';
        }

        if ($params->parentId->text()) {
            $arguments['parentId'] = $params->parentId->text();
        }

        if (! empty($arguments)) {
            $link = 'tiki-objectpermissions.php?' . http_build_query($arguments, '', '&');
        } else {
            $link = 'tiki-objectpermissions.php';
        }

        $perms = \Perms::get($type, $id);
        $source = $perms->getResolver()->from();

        return $smarty->fetch('permission_link.tpl', [
            'permission_link' => [
                'url' => $link,
                'active' => $source == 'object',
                'mode' => $params->mode->word() ?: 'glyph',
                'label' => $params->label->text() ?: tr('Permissions'),
                'count' => $params->count->int(),
                'type' => $type,
                'addclass' => $params->addclass->text(),
            ],
        ]);
    }
}
