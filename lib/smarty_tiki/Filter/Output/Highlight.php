<?php

namespace SmartyTiki\Filter\Output;

// / (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Smarty outfilter highlight
 * -------------------------------------------------------------
 * Purpose:  Adds Google-cache-like highlighting for terms in a
 *           template after its rendered. This can be used
 *           easily integrated with the wiki search functionality
 *           to provide highlighted search terms.
 * -------------------------------------------------------------
 */
class Highlight implements \Smarty\Filter\FilterInterface
{
    public function filter($code, \Smarty\Template $template)
    {
        global $prefs;
        $source = $code;

        if (empty($_REQUEST['highlight'])) {
            return $source;
        }
        if (! preg_match('/<.+\s.*?class="[^"]*\bhighlightable\b[^"]*"/', $source, $m, PREG_OFFSET_CAPTURE)) {   // the main page contents appears without the col1 but with 2 and 3 appended
            return $source;
        }
        $highlight = $_REQUEST['highlight'];

        if (isset($_REQUEST['boolean']) && ($_REQUEST['boolean'] == 'on' || $_REQUEST['boolean'] == 'y')) {
            $highlight = str_replace(['(', ')', '*', '-', '"', '~', '<', '>'], ' ', $highlight);
        }

        if ($prefs['feature_referer_highlight'] == 'y') {
            $refererhi = self::refererhi();
            if (isset($refererhi) && ! empty($refererhi)) {
                if (isset($highlight) && ! empty($highlight)) {
                    $highlight = $highlight . " " . $refererhi;
                } else {
                    $highlight = $refererhi;
                }
            }
        }
        if (! isset($highlight) || empty($highlight)) {
            return $source;
        }

        $matches = [];

        $end = 0;

        if ($end = strrpos($source, 'id="col2"')) {
            $stop_pattern = '(<div[^>]*\s+id="col2".*)';
        } elseif ($end = strrpos($source, 'id="col3"')) {
            $stop_pattern = '(<div[^>]*\s+id="col3".*)';
        } else {
            $stop_pattern = '';
        }

        $result = false;

        if (function_exists('mb_eregi')) {
            // UTF8 support enabled
            $result = mb_eregi('^(.*<article [^>]*>)(.*)' . $stop_pattern . '$', $source, $matches);
        } else {
            // We do not fallback on the preg_match function, since it is limited by 'pcre.backtrack_limit' which is too low by default (100K)
            //  and this script will not be allowed to change its value on most systems
            //
            if (( $start = strpos($source, '<article ') ) > 0) {
                $matches = [
                    $source,
                    substr($source, 0, $start),
                    ( $end > $start ? substr($source, $start, $end - $start) : substr($source, $start) ),
                    ( $end > $start ? substr($source, $end) : '' )
                ];
                $result = true;
            }
        }

        if (! $result) {
            return $source;
        }
        if (strlen($matches[2]) > ini_get('pcre.backtrack_limit')) {
            return $source;
        }

        if (! isset($matches[3])) {
            $matches[3] = '';
        }

        // Avoid highlight parsing in unknown cases where $matches[2] is empty, which will result in an empty page.
        if ($matches[2] != '') {
            $source = preg_replace_callback(
                '~(?:<head>.*</head>                            # head blocks
                |<div[^>]*nohighlight.*</div><!--nohighlight--> # div with nohightlight
                |<div[^>]*adminoption.*</div>                   # pref in a popup so double quote breaks it
                |<script[^>]+>.*</script>                       # script blocks
                |<a[^>]*onmouseover.*onmouseout[^>]*>           # onmouseover (user popup)
                |<[^>]*>                                        # all html tags
                |(' . self::enlightColor($highlight) . '))~xsiU',
                [self::class, 'enlightColor'],  // Pass the method as callback
                $matches[2]
            );
        }

        return $matches[1] . $source . $matches[3];
    }

    public static function enlightColor($matches)
    {
        static $colword = [];
        if (is_string($matches)) { // just to set the color array
            // Wrap all the highlight words with tags bolding them and changing
            // their background colors
            $i = 0;
            $seaword = $seasep = '';
            $wordArr = preg_split('~%20|\+|\s+~', $matches);
            foreach ($wordArr as $word) {
                if ($word == '') {
                    continue;
                }
                $seaword .= $seasep . preg_quote($word, '~');
                $seasep = '|';
                $colword[strtolower($word)] = 'highlight_word highlight_word_' . $i % 5;
                $i++;
            }
            return $seaword;
        }
        // actual replacement callback
        if (isset($matches[1])) {
            return '<span class= "' . $colword[strtolower($matches[1])] . '">' . $matches[1] . '</span>';
        }
        return $matches[0];
    }

// helper function
// q= for Google, p= for Yahoo
    public static function refererhi()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;
        if ($referer === null || empty($referer['query'])) {
            return '';
        }
        parse_str($referer['query'], $vars);
        if (isset($vars['q'])) {
            return $vars['q'];
        } elseif (isset($vars['p'])) {
            return $vars['p'];
        }
        return '';
    }
}
