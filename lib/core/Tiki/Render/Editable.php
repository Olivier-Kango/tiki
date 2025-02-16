<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Render_Editable
{
    private $inner;
    private $layout = 'inline';
    private $group = false;
    private $label = null;
    private $fieldFetchUrl;
    private $objectStoreUrl;
    private $field;

    public function __construct($html, array $parameters)
    {
        $this->inner = $html;
        $this->field = $parameters['field'];

        if (! empty($parameters['layout']) && in_array($parameters['layout'], ['inline', 'block', 'dialog'])) {
            $this->layout = $parameters['layout'];
        }

        if (! empty($parameters['group'])) {
            $this->group = $parameters['group'];
        }

        if (! empty($parameters['label'])) {
            $this->label = $parameters['label'];
        }

        if (empty($parameters['object_store_url'])) {
            throw new Exception(tr('Internal error: mandatory parameter object_store_url is missing'));
        }

        $servicelib = TikiLib::lib('service');
        if (! empty($parameters['field_fetch_url'])) {
            $this->fieldFetchUrl = $parameters['field_fetch_url'];
        }

        $this->objectStoreUrl = $parameters['object_store_url'];
    }

    public function __toString()
    {
        global $prefs;

        if ($prefs['ajax_inline_edit'] != 'y') {
            return $this->inner === null ? '' : $this->inner;
        }

        // block = dialog goes to span as well
        $tag = ($this->layout == 'block') ? 'div' : 'span';
        $fieldId = $this->field['id'];
        $fieldType = $this->field['type'];
        $fieldFetch = smarty_modifier_escape(json_encode($this->fieldFetchUrl));
        $objectStore = $this->objectStoreUrl;
        $objectStore['edit'] = 'inline';
        $objectStore = smarty_modifier_escape(json_encode($objectStore));
        $label = smarty_modifier_escape($this->label);

        $value = $this->inner;
        if (is_null($value)) {
            $value = '';
        }
        if (trim(strip_tags($value)) == '') {
            // When the value is empty, make sure it becomes visible/clickable
            $value .= '&nbsp;';
        }

        $class = "editable-inline";
        if ($this->layout == 'dialog') {
            $class = "editable-dialog";
        }

        if (! $this->fieldFetchUrl) {
            $class .= ' loaded';
        }

        $group = smarty_modifier_escape($this->group);
        $smarty = TikiLib::lib('smarty');
        return "<$tag class=\"$class\" data-field-fetch-url=\"$fieldFetch\" data-object-store-url=\"$objectStore\" data-group=\"$group\" data-label=\"$label\" data-field-id=\"$fieldId\" data-field-type=\"$fieldType\">$value" . smarty_function_icon(['name' => 'edit', 'iclass' => 'ml-2'], $smarty->getEmptyInternalTemplate()) . "</$tag>";
    }
}
