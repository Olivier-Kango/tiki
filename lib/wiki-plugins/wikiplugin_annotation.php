<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_annotation_info()
{
    global $prefs;
    return [
        'name' => tra('Image Annotation'),
        'documentation' => 'PluginAnnotation',
        'description' => tra('Annotate an image'),
        'prefs' => ['wikiplugin_annotation'],
        'body' => tra('Autogenerated content. Leave blank initially.'),
        'filter' => 'striptags',
        'iconname' => 'edit',
        'introduced' => 2,
        'tags' => [ 'basic' ],
        'format' => 'html',
        'params' => [
            'src' => [
                'required' => true,
                'name' => tra('Location'),
                'description' => ($prefs['feature_sefurl'] === 'y') ?
                    tr(
                        'Absolute URL to the image, relative path from Tiki site root or an image from the file gallery %0',
                        '<code>display1</code>'
                    ) : tra('Absolute URL to the image or relative path from Tiki site root.'),
                'filter' => 'url',
                'default' => '',
                'since' => '3.0',
            ],
            'width' => [
                'required' => true,
                'name' => tra('Width'),
                'description' => tra('Image width in pixels.'),
                'filter' => 'digits',
                'default' => '',
                'since' => '3.0',
            ],
            'height' => [
                'required' => true,
                'name' => tra('Height'),
                'description' => tra('Image height in pixels.'),
                'filter' => 'digits',
                'default' => '',
                'since' => '3.0',
            ],
            'align' => [
                'required' => false,
                'name' => tra('Alignment'),
                'description' => tra('Image alignment.'),
                'filter' => 'alpha',
                'advanced' => true,
                'default' => 'left',
                'since' => '2.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Center'), 'value' => 'center'],
                ],
            ],
            'class' => [
                'required' => false,
                'name' => tra('CSS Class'),
                'description' => tra('Class of the containing DIV element.'),
                'filter' => 'text',
                'default' => '',
                'since' => '15.0',
                'advanced' => true,
            ],
            'showlist' => [
                'required' => false,
                'name' => tra('Show List'),
                'description' => tra('Show the list of annotations below the image.') . ' ' . tra('(y/n)'),
                'filter' => 'alpha',
                'default' => 'y',
                'since' => '15.0',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'showlink' => [
                'required' => false,
                'name' => tra('Show Link'),
                'description' => tra('Show the link below the label in the popups.') . ' ' . tra('(y/n)'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '15.0',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
        ]
    ];
}

function wikiplugin_annotation($data, $params)
{
    global $page, $tiki_p_edit;
    $headerlib = TikiLib::lib('header');
    $smarty = TikiLib::lib('smarty');
    $ticketHtml = smarty_function_ticket([], $smarty->getEmptyInternalTemplate());

    $defaults = [];
    $plugininfo = wikiplugin_annotation_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    $annotations = [];
    foreach (explode("\n", $data) as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        if (preg_match("/^\(\s*(\d+)\s*,\s*(\d+)\s*\)\s*,\s*\(\s*(\d+)\s*,\s*(\d+)\s*\)(.*)\[(.*)\]$/", $line, $parts)) {
            $parts = array_map('trim', $parts);
            list( $full, $x1, $y1, $x2, $y2, $label, $target ) = $parts;

            $annotations[] = [
                'x1' => $x1,
                'y1' => $y1,
                'x2' => $x2,
                'y2' => $y2,
                'value' => $label,
                'target' => $target,
            ];
        }
    }

    $annotations = json_encode($annotations);

    $headerlib->add_jsfile('lib/jquery_tiki/wikiplugin-annotation.js');

    static $uid = 0;
    $uid++;
    $cid = 'container-annotation-' . $uid;

    $labelSave = tra('Save changes to annotations');
    $message = tra('Image annotations changed.');

    if ($tiki_p_edit == 'y') {
        $editableStr = tra('Editable');

        $form = <<<FORM
<form method="post" action="tiki-wikiplugin_edit.php" class="form save-annotations">
    <div style="display:none">
        $ticketHtml
        <input type="hidden" name="page" value="$page"/>
        <input type="hidden" name="type" value="annotation"/>
        <input type="hidden" name="index" value="$uid"/>
        <input type="hidden" name="message" value="$message"/>
        <textarea id="$cid-content" name="content"></textarea>
    </div>
    <div class="tiki-form-group row">
        <input type="submit" class="btn btn-primary btn-sm" value="$labelSave"/>
        <label>
            <input type="checkbox" id="$cid-editable">
            {$editableStr}
        </label>
    </div>
</form>
FORM;
    } else {
        $form = '';
    }

    // inititalise the annotations
    $showlink = $params['showlink'] === 'y' ? 'true' : 'false';

    $headerlib->add_jq_onready('$("#' . $cid . '").imageAnnotation(' . $annotations . ', ' . $showlink . ');');

    $close = smarty_function_icon(['name' => 'close'], $smarty->getEmptyInternalTemplate());
    $delete = smarty_function_icon(['name' => 'trash'], $smarty->getEmptyInternalTemplate());

    $labelStr = tra('Label');
    $linkStr = tra('Link');
    $saveStr = tra('Save');
    $closeStr = tra('Close');
    $removeStr = tra('Remove');

    if ($tiki_p_edit == 'y') {
        $editor_form = <<<EDITORFORM
        <div class="editor card">
            <div class="card-body">
                <form method="post" action="#">
                    <div class="tiki-form-group mt-3 row">
                        <label style="width:100%">
                            <span class="col-sm-3">$labelStr</span>
                            <span class="col-sm-9"><textarea name="label" class="form-control"></textarea></span>
                        </label>
                    </div>
                    <div class="tiki-form-group row">
                        <label style="width:100%">
                            <span class="col-sm-3">$linkStr</span>
                            <span class="col-sm-9"><input type="text" name="link" class="form-control"></span>
                        </label>
                    </div>
                    <div class="tiki-form-group row">
                        <div class="col-sm-9 offset-sm-3">
                            $ticketHtml
                            <input type="submit" class="btn btn-primary btn-sm" value="$saveStr">
                            <div class="float-end">
                                <a class="btn btn-primary btn-sm minimize" href="#" title="$closeStr">$close</a>
                                <a class="btn btn-primary btn-sm delete" href="#" title="$removeStr">$delete</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
EDITORFORM;
    } else {
        $editor_form = '';
    }

    if ($params['showlist'] === 'y') {
        $list_div = '<div class="list-box"><div>';
    } else {
        $list_div = '';
    }

    return <<<ANNOTATION
<div class="wp-annotation {$params['class']}">
    <div id="$cid" style="background:url({$params['src']}); width:{$params['width']}px; height:{$params['height']}px;">
{$editor_form}
    </div>
    {$list_div}
    {$form}
</div>
ANNOTATION;
}
