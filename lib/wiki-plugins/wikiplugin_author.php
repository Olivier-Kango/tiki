<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_author_info()
{
    return [
        'name' => tra('Author'),
        'documentation' => 'PluginAuthor',
        'description' => tra('Add popups and color coding that identifies authors'),
        'prefs' => ['wikiplugin_author'],
        'body' => tra('text'),
        'iconname' => 'pencil',
        'introduced' => 6,
        'params' => [
            'author' => [
                'required' => true,
                'name' => tra('Username'),
                'description' => tra('Username of the author of the text.'),
                'since' => '6.0',
                'default' => '',
                'filter' => 'username',
            ],
            'deleted_by' => [
                'required' => false,
                'name' => tra('Deleted by User'),
                'description' => tra('Username of the person who deleted the text.'),
                'since' => '6.0',
                'default' => '',
                'filter' => 'username',
            ],
            'visible'   => [
                'required'  => false,
                'name'      => tra('Make Visible'),
                'description' => tra("Should this author's contribution be visible (default: no)."),
                'since' => '6.0',
                'filter' => 'text',
                'default' => 0,
                'options' => [
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                ],
            ],
            'popup' => [
                'required'  => false,
                'name'      => tra('Show popup with author/deleted by'),
                'description' => tra('Generate a popup with names of author(s) (default: no).'),
                'since' => '6.0',
                'filter' => 'text',
                'default' => 0,
                'options' => [
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                ],
            ],
        ], // params
    ];
}

function wikiplugin_author($data, $params)
{
    $headerlib = TikiLib::lib('header');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    global $authors;

    static $style = 0;
    static $id = 0;

    $blocktags = '/(<+\/?address.*?>|<+\/?blockcode.*?>|<+\/?blockquote.*?>|<+\/?div.*?>|<+\/?h1.*?>|<+\/?h2.*?>|<+\/?h3.*?>|<+\/?h4.*?>|<+\/?h5.*?>|<+\/?h6.*?>|<+\/?hr.*?>|<+\/?h.*?>|<+\/?li.*?>|<+\/?ol.*?>|<+\/?pre.*?>|<+\/?p.*?>|<+\/?section.*?>|<+\/?table.*?>|<+\/?td.*?>|<+\/?th.*?>|<+\/?tr.*?>|<+\/?ul.*?>)/';
    $default = ['popup' => 0];
    $params = array_merge($default, $params);
    if (! is_array($authors)) {
        $authors = [];
    }
    if (empty($params['author'])) {
        Feedback::error(tra('The author parameter is missing'));
        return;
    }
    if (empty($params['visible'])) {
        $params['visible'] = 0;
    }
    if (empty($params['popup'])) {
        $params['popup'] = 0;
    }

    $author = $params['author'];
    if (! isset($authors[$author])) {
        $authors[$author] = [];
    }
    if (! isset($authors[$author]['style'])) {
        $authors[$author]['style'] = "author$style";
        $style++;
        if ($style > 15) {
            $style = 0; // so far only 16 colors defined
        }
    }

    $content = preg_split($blocktags, $data, -1, PREG_SPLIT_DELIM_CAPTURE);
    $html = '';
    foreach ($content as $data) {
        if ($data != '') {
            if (preg_match($blocktags, $data) > 0) {
                $html .= $data;
            } else {
                if ($params['visible'] == 1 or $params['popup'] == 1) {
                    $html .= '<span id="author' . $id . '-link" ';
                }
                if ($params['visible'] == 1) {
                    $html .= 'class="' . $authors[$author]['style'];
                    if (isset($params['deleted_by'])) {
                        $html .= ' deleted';
                    }
                    $html .= '"';
                }
                if ($params['popup'] == 1) {
                    $html .= ' onclick="javascript:void()"';
                }
                if ($params['visible'] == 1 or $params['popup'] == 1) {
                    $html .= ">$data</span>";
                } else {
                    $html .= $data;
                }

                if ($params['popup'] == 1) {
                    //Mouseover for detailed info
                    $js = "\$('#author$id-link').on('mouseover', function(event) {
                        \$('#author$id').css('left', event.pageX).css('top', event.pageY);
                        showJQ('#author$id', '', '');
                        1000
                    });";
                    $js .= "\$('#author$id-link').on('mouseout', function(event) { setTimeout(function() {hideJQ('#author$id', '', '')}, 1000); });";
                    $headerlib->add_jq_onready($js);
                    $html .= "<span id=\"author$id\" class=\"plugin-mouseover\" style=\"width: 200px; height: 80px; padding: 2px \">" .
                        tra('Author') . ": $author" . (isset($params['deleted_by']) ? "<br />" . tra('deleted by') . ': ' . $params['deleted_by'] : '') . "</span>";
                }
                $id++;
            } // content is not a block tag
        } // content <>""
    } // foreach
    return $html;
}
