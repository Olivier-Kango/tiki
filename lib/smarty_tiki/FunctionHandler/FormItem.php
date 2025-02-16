<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * smarty_function_form_item handler: Display a basic form item in proper Bootstrap syntax
 *
 * params will be used as params for as smarty self_link params, except those special params specific to smarty button :
 *  - _label : this is the name of the label that should show up
 *  - _field: the form input field should be passed to this parameter.
 *  Usage of this function should be something like {formitem _field={$f_title} _label="Title"}
  */
class FormItem extends Base
{
    public function handle($params, Template $template)
    {
        if (! is_array($params) || ! isset($params['_field']) || ! isset($params['_label'])) {
            return;
        }
        global $tikilib, $prefs;

        $class = "";
        if (isset($params['class'])) {
            $class = $params['class'];
        }
        $id = "";
        if (isset($params['id'])) {
            $temp = $params['id'];
            $id = "id='" . $temp . "'";
        }

        $help = "";
        if (isset($params['_help'])) {
            $help = '<span class="form-text">' . $params['_help'] . '</span>';
        }

        if ($params['_help-popup']) {
            if ($params['_help-popup-title']) {
                $popup_title = $params['_help-popup-title'];
            } else {
                $popup_title = 'Dismissible popover';
            }
            $popup = '<a tabindex="0" data-bs-toggle="popover" data-bs-trigger="focus" title="' . $params['_help-popup-title'] . '" data-bs-content="' . $params['_help-popup'] . '"><span class="fas fa-question-circle"></span></a>';
        }

        if ($params["mandatory"] == "y") { //override optional label
            $params['_field'] = preg_replace("/(\&nbsp\;\<small\>\<i\>\(\w*\)\<\/i\>\<\/small\>)*(.*)/", "$2", $params['_field']);
        }

        if ($params['is_checkbox'] == 'y') {
            $html = '<div class="form-check"><label>' . $params['_field'] . $params['_label'] . '</label> ' . $popup . '</div>';
        } else {
            $html = '<div ' . $id . ' class="tiki-form-group row ' . $params['class'] . '"><label>' . $params['_label'] . '</label> ' . $popup . $help . $params['_field'] . '</div>';
        }

        return $html;
    }
}
