<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Yaml\Yaml;

function wikiplugin_code_info()
{
    global $tikipath;
    $themes = [
        ['text' => tr('default'), 'value' => 'default',],
    ];
    $themes_folder = rtrim($tikipath ?? '', '/') . '/' . CODEMIRROR_DIST_PATH . '/theme';

    if (is_dir($themes_folder)) {
        foreach (scandir($themes_folder) as $file) {
            $match = null;
            if (preg_match('/(.*)(\.css)$/', $file, $match)) {
                $themes[] = [
                    'text' => $match[1],
                    'value' => $match[1]
                ];
            }
        }
    }

    return [
        'searchable_by_default' => true,
        'name' => tra('Code'),
        'documentation' => 'PluginCode',
        'description' => tra('Display code with syntax highlighting and line numbering.'),
        'prefs' => ['wikiplugin_code'],
        'body' => tra('Code to be displayed'),
        'iconname' => 'laptop-code',
        'introduced' => 1,
        'filter' => 'rawhtml_unsafe',
        'format' => 'html',
        'tags' => [ 'basic' ],
        'params' => [
            'caption' => [
                'required' => false,
                'name' => tra('Caption'),
                'description' => tra('Code snippet label.'),
                'since' => '1',
                'filter' => 'text',
            ],
            'wrap' => [
                'required' => false,
                'name' => tra('Line Wrapping'),
                'description' => tra('Wrap lines of code which do not fit in the display box\'s width. Enabling avoids overflow or hidden line ends.'),
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => '1'],
                    ['text' => tra('No'), 'value' => '0'],
                ],
                'filter' => 'digits',
                'default' => '1'
            ],
            'colors' => [
                'required' => false,
                'name' => tra('Colors'),
                'description' => tra('Any supported language listed at http://codemirror.net/mode/.  Pref feature_syntax_highlighter must be true for this to have any effect'),
                'since' => '17',
                'filter' => 'text',
                'advanced' => false,
            ],
            'ln' => [
                'required' => false,
                'name' => tra('Line Numbers'),
                'description' => tra('Show line numbers for each line of code.'),
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => '1'],
                    ['text' => tra('No'), 'value' => '0'],
                ],
                'filter' => 'digits',
                'advanced' => true,
            ],
            'rtl' => [
                'required' => false,
                'name' => tra('Right to Left'),
                'description' => tra('Switch the text display from left to right, to right to left (left to right by default)'),
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => '1'],
                    ['text' => tra('No'), 'value' => '0'],
                ],
                'filter' => 'digits',
                'advanced' => true,
            ],
            'mediawiki' => [
                'required' => false,
                'name' => tra('Code Tag'),
                'description' => tra('Encloses the code in an HTML code tag, for example: &lt;code&gt;user input&lt;code&gt;'),
                'since' => '8.3',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => '1'],
                    ['text' => tra('No'), 'value' => '0'],
                ],
                'filter' => 'digits',
                'default' => '0',
                'advanced' => true,
            ],
            'theme' => [
                'required' => false,
                'name' => tra('Theme'),
                'description' => tra('Any supported theme listed at https://codemirror.net/demo/theme.html'),
                'since' => '17',
                'options' => $themes,
                'filter' => 'text',
            ],
        ],
    ];
}

function wikiplugin_code($data, $params)
{
    global $prefs;
    static $code_count;

    $defaults = [
        'wrap' => '1',
        'mediawiki' => '0',
        'ishtml' => false
    ];

    $params = array_merge($defaults, $params);

    extract($params, EXTR_SKIP);
    $code = trim($data);
    if ($mediawiki == '1') {
        return "<code>$code</code>";
    }

    $code = str_replace('&lt;x&gt;', '', $code);
    $code = str_replace('<x>', '', $code);

    $id = 'codebox' . ++$code_count;
    $boxid = " id=\"$id\" ";
    $data_clipboard_target = " data-clipboard-target=\"#$id\" ";

    $out = $code;

    if (isset($colors)) {
        if ($colors == '1') {
            // remove old geshi setting as it upsets codemirror
            unset($colors);
        } else {
            // codemirror expects language names in lower case
            $colors = strtolower($colors);
            check_valid_code($colors, $code);
        }
    }

    // respect wrap parameter when Codemirror is off and line wrap when Codemirror is on to avoid broken view while JavaScript loads.
    if ((isset($prefs['feature_syntax_highlighter']) && $prefs['feature_syntax_highlighter'] == 'y') || $wrap == 1) {
        $pre_style = 'white-space:pre-wrap;'

            // If needed, break words
            . ' overflow-wrap: break-word;' // CSS 3 working draft
            . ' word-wrap: break-word;'; // Original proprietary Microsoft name

        if (! isset($theme) && isset($prefs['feature_syntax_highlighter_theme'])) {
            $theme = $prefs['feature_syntax_highlighter_theme'];
        }
    }



    $out = (isset($caption) ? '<div class="codecaption">' . $caption . '</div>' : "" )
        . '<div class="codelisting_container">'
        . '<div class="icon_copy_code far fa-clipboard" tabindex="0" ' . $data_clipboard_target . '><span class="copy_code_tooltiptext">Copy to clipboard</span></div>'
        . '<pre class="codelisting" '
        . (isset($theme) ? ' data-theme="' . $theme . '" ' : '')
        . (isset($colors) ? ' data-syntax="' . $colors . '" ' : '')
        . (isset($ln) ? ' data-line-numbers="' . $ln . '" ' : '')
        . (isset($wrap) ? ' data-wrap="' . $wrap . '" ' : '')
        . ' dir="' . ( (isset($rtl) && $rtl == 1) ? 'rtl' : 'ltr') . '" '
        . (isset($pre_style) ? ' style="' . $pre_style . '"' : '')
        . $boxid . '>'
        . '<div class="code">'
        . (TikiLib::lib('parser')->option['wysiwyg'] || $ishtml ? $out : htmlentities($out, ENT_QUOTES, 'UTF-8'))
        . '</div>'
        . '</pre>'
        . '</div>';

    return $out;
}

/**
 * Check whether the code provided in the specific language is valid.
 * Feel free to add more patterns to check for other languages. Any supported language listed at http://codemirror.net/mode/
 *
 * @param $colors string containing language name
 * @param $code string containing code to check
 *
 * @return bool
 *
 * @throws Exception If the code is not valid in the provided language
 *
 */
function check_valid_code($colors, $code)
{
    switch ($colors) {
        case 'yaml':
            try {
                Yaml::parse($code);
            } catch (Exception $e) {
                Feedback::error(tr('Could not parse YAML code: "%0"', $e->getMessage()));
            }
            break;

        default:
            return true;
    }
    return true;
}
