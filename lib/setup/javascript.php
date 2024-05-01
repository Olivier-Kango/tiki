<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// need to rebuild because they were created in tiki-setup and just removed due to clear cache
// we need to create upfront in case codemirror is used later on.
require_once("lib/codemirror_tiki/tiki_codemirror.php");
createCodemirrorModes();

// Javascript auto-detection
//   (to be able to generate non-javascript code if there is no javascript, when noscript tag is not useful enough)
//   It uses cookies instead of session vars to keep the correct value after a session timeout

$js_cookie = getCookie('javascript_enabled');

if ($prefs['disableJavascript'] == 'y') {
    $prefs['javascript_enabled'] = 'n';
} elseif (! empty($js_cookie)) {
    // Update the pref with the cookie value
    $prefs['javascript_enabled'] = 'y';
    if ($js_cookie !== 'y') {       // if it's not 'y' then it's the expiry in ms
        setcookie('javascript_enabled', 'y', $js_cookie / 1000);
        $js_cookie = 'y';
    }
    setCookieSection('javascript_enabled_detect', '', '', time() - 3600);   // remove the test cookie
    $_COOKIE['javascript_enabled_detect'] = '';
} else {
    if (isset($_COOKIE['runs_before_js_detect'])) { // pre-tiki 14 method detected, delete both test cookies (reloading here caused redirect a loop in some case)
        setcookie('runs_before_js_detect', '', time() - 3600);
        setcookie('javascript_enabled_detect', '', time() - 3600);
    }
    $prefs['javascript_enabled'] = '';
}

// the first and second time, we should not trust the absence of javascript_enabled cookie yet,
// as it could be a redirection and the js will not get a chance to run yet, so we wait until the third run,
// assuming that js is on before then
$javascript_enabled_detect = getCookie('javascript_enabled_detect', '', '0');

// Cookie Consent: setCookieSection uses the session tiki_cookie_jar to simulate cookies,
// so check that $feature_no_cookie is not true because cookies are not really set when using cookie_consent_feature
// and so javascript will get disabled by mistake
if ($prefs['javascript_assume_enabled'] === 'y') {
    $prefs['javascript_enabled'] = 'y';
} elseif (empty($javascript_enabled_detect) && $feature_no_cookie) {
    $prefs['javascript_enabled'] = 'y';                 // assume javascript should be enabled while cookie consent is pending
} elseif ($prefs['javascript_enabled'] === '' && $prefs['disableJavascript'] != 'y' && $javascript_enabled_detect < 3) {
    // Set the cookie to 'y', through javascript - expires: approx. 1 year
    $prefs['javascript_enabled'] = 'y';                                         // temporarily enable to we output the test js
    $plus_one_year = ($tikilib->now + 365 * 24 * 3600) * 1000;      // ms
    $headerlib->add_js("setCookieBrowser('javascript_enabled', '{$plus_one_year}', '', new Date({$plus_one_year}));", 0);       // setCookieBrowser does not use the tiki_cookie_jar

    if (
        strpos($_SERVER['PHP_SELF'], 'tiki-download') === false &&
        strpos($_SERVER['PHP_SELF'], 'tiki-ajax_services.php') === false &&
        strpos($_SERVER['PHP_SELF'], 'tiki-login.php') === false &&
        strpos($_SERVER['PHP_SELF'], 'tiki-install.php') === false
    ) {
        $javascript_enabled_detect++;
        setCookieSection('javascript_enabled_detect', $javascript_enabled_detect, '', $plus_one_year / 1000);
    }
} elseif ($js_cookie !== 'y') { // no js cookie detected
    $prefs['javascript_enabled'] = 'n';
}

if ($prefs['javascript_enabled'] == 'n') {
    // disable js dependant features
    $prefs['feature_tabs'] = 'n';
    $prefs['feature_jquery'] = 'n';
    $prefs['feature_shadowbox'] = 'n';
    $prefs['feature_wysiwyg'] = 'n';
    $prefs['feature_ajax'] = 'n';
    // assign short variable for use in templates
    $smarty->assign('js', 0);
    //for use in setting tags for css menus as fallback for action dropdowns in case of no javascript
    $smarty->assign('libeg', '<li>');
    $smarty->assign('liend', '</li>');
}

// LEGACY: assign short variable for use in templates
$smarty->assign('js', 1);
//for use in setting tags for css menus as fallback for action dropdowns in case of no javascript
$smarty->assign('libeg', '');
$smarty->assign('liend', '');

$prefs['feature_jquery'] = 'y'; // just in case

// load translations lang object from /lang/xx/language.js if there
if (file_exists('lang/' . $prefs['language'] . '/language.js')) {
    // after the usual lib includes (up to 10) but before custom.js (50)
    $headerlib
        ->add_jsfile_late('lang/' . $prefs['language'] . '/language.js')
        ->add_js("$.lang = '" . $prefs['language'] . "';");
}


/** Use custom.js in lang dir if there **/
$language = $prefs['language'];
if (is_file("lang/$language/custom.js")) {
    TikiLib::lib('header')->add_jsfile_late("lang/$language/custom.js");    // before styles custom.js
}

//LEGACY CODE, you should use _custom instead
if (! empty($tikidomain) && is_file("lang/$language/$tikidomain/custom.js")) {      // Note: lang tikidomain dirs not created automatically
    TikiLib::lib('header')->add_jsfile_late("lang/$language/$tikidomain/custom.js");
}

// Is there a lang/$language/custom.js in the current base theme (no options)?
$custom_js = ThemeLib::getThemePath(null, '', "lang/$language/custom.js");
if ($custom_js) {
    $headerlib->add_jsfile_late($custom_js);
}

// Is there a lang/$language/custom.js in the current theme options?
$custom_js = ThemeLib::getThemePath(null, null, "lang/$language/custom.js");
if ($custom_js) {
    $headerlib->add_jsfile_late($custom_js);
}


//Is there a lang/$language/custom.js in_custom/shared
$custom_js = \Tiki\Paths\Customization::getSharedPublicPath(\Tiki\Paths\Customization::getCustomLangFragment());
if ($custom_js) {
    $headerlib->add_jsfile_late($custom_js);
}

//Is there a lang/$language/custom.js from _custom/sites
$custom_js = \Tiki\Paths\Customization::getCurrentSitePublicPath(\Tiki\Paths\Customization::getCustomLangFragment());
if ($custom_js) {
    $headerlib->add_jsfile_late($custom_js);
}

/* Use js/custom.js in themes and options dir if there */

// Before Tiki 27, we used to also look in /themes/js for a custom.js file

// Is there a js/custom.js in the current base theme (no options)?
$custom_js = ThemeLib::getThemePath(null, '', 'js/custom.js');
if ($custom_js) {
    $headerlib->add_jsfile($custom_js);
}

// Is there a js/custom.js in the current theme options?
$custom_js = ThemeLib::getThemePath(null, null, 'js/custom.js');
if ($custom_js) {
    $headerlib->add_jsfile($custom_js);
}

//Is there a _custom/shared/js/custom.js
$custom_js = \Tiki\Paths\Customization::getSharedPublicPath(TIKI_CUSTOMIZATIONS_JAVASCRIPT_PATH_FRAGMENT);
if ($custom_js) {
    $headerlib->add_jsfile($custom_js);
}

//Is there a _custom/sites/my.domain/js/custom.js
$custom_js = \Tiki\Paths\Customization::getCurrentSitePublicPath(TIKI_CUSTOMIZATIONS_JAVASCRIPT_PATH_FRAGMENT);
if ($custom_js) {
    $headerlib->add_jsfile($custom_js);
}

// setup timezone array
$tz = TikiDate::getTimezoneAbbreviations();
$headerlib->add_js(
    '
try {
    var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    setCookie("local_tz", timezone);
} catch (e) {}

// this is used by tiki-confirm.js checkTimeout, so needs to be always set
const scriptStartDate = new Date(); //Be careful, this will not be a global if this script ever becomes a module - benoitg - 2023-11-21

if (! timezone) {
    function inArray(item, array) {
        for (var i in array) {
            if (array[i] === item) {
                return i;
            }
        }
        return false;
    }
    var allTimeZoneCodes = ' . json_encode(array_map("strtoupper", $tz)) . ';
    var now_string = now.toString();
    var offsethours = - now.getTimezoneOffset() / 60;
    setCookie("local_tzoffset", offsethours);
    var m = now_string.match(/[ \(]([A-Z]{3,6})[ \)]?[ \d]*$/);    // try three or more char tz first at the end or just before the year
    if (!m) {
        m = now_string.match(/[ \(]([A-Z]{1,6})[ \)]?[ \d]*$/);    // might be a "military" one if not
    }
    if (m) {
        m = m[1];
    } else {    // IE (sometimes) gives UTC +offset instead of the abbreviation
        // sadly this workaround will fail for non-whole hour offsets
        var hours = - now.getTimezoneOffset() / 60;
        m = "GMT" + (hours > 0 ? " + " : "") + hours;
    }
    // Etc/GMT+ is equivalent to GMT-
    if (m.substring(0,4) == "GMT + ") {
        m = "Etc / GMT - " + m.substring(4);
        setCookie("local_tz", m);
    }
    if (m.substring(0,4) == "GMT - ") {
        m = "Etc / GMT + " + m.substring(4);
        setCookie("local_tz", m);
    }
    if (inArray(m, allTimeZoneCodes)) {
        setCookie("local_tz", m);
    }
}
',
    2
);

$jqueryTiki['ui'] = $prefs['feature_jquery_ui'] === 'y' ? true : false;
$jqueryTiki['ui_theme'] = $prefs['feature_jquery_ui_theme'];
$jqueryTiki['tooltips'] = $prefs['feature_jquery_tooltips'] === 'y' ? true : false;
$jqueryTiki['autocomplete'] = $prefs['feature_jquery_autocomplete'] === 'y' ? true : false;
$jqueryTiki['smartmenus'] = $prefs['jquery_smartmenus_enable'] === 'y' ? true : false;
$jqueryTiki['smartmenus_collapsible_behavior'] = $prefs['jquery_smartmenus_collapsible_behavior'];
$jqueryTiki['smartmenus_open_close_click'] = $prefs['jquery_smartmenus_open_close_click'] === 'y' ? true : false;
$jqueryTiki['reflection'] = $prefs['feature_jquery_reflection'] === 'y' ? true : false;
$jqueryTiki['tablesorter'] = $prefs['feature_jquery_tablesorter'] === 'y' ? true : false;
$jqueryTiki['colorbox'] = $prefs['feature_shadowbox'] === 'y' ? true : false;
$jqueryTiki['cboxCurrent'] = "{current} / {total}";
$jqueryTiki['sheet'] = $prefs['feature_sheet'] === 'y' ? true : false;
$jqueryTiki['carousel'] = $prefs['feature_jquery_carousel'] === 'y' ? true : false;
$jqueryTiki['validate'] = $prefs['feature_jquery_validation'] === 'y' ? true : false;
$jqueryTiki['zoom'] = $prefs['feature_jquery_zoom'] === 'y' ? true : false;
$jqueryTiki['url_fragment_guesser'] = ($prefs['url_fragment_guesser'] ?? 'n') === 'y' ? true : false;

// Default effect
$jqueryTiki['effect'] = $prefs['jquery_effect'];
// "horizontal" | "vertical" etc
$jqueryTiki['effect_direction'] = $prefs['jquery_effect_direction'];
// "slow" | "normal" | "fast" | milliseconds (int) ]
$jqueryTiki['effect_speed'] = $prefs['jquery_effect_speed'] === 'normal' ? '400' : $prefs['jquery_effect_speed'];
$jqueryTiki['effect_tabs'] = $prefs['jquery_effect_tabs'];      // Different effect for tabs
$jqueryTiki['effect_tabs_direction'] = $prefs['jquery_effect_tabs_direction'];
$jqueryTiki['effect_tabs_speed'] = $prefs['jquery_effect_tabs_speed'] === 'normal' ? '400' : $prefs['jquery_effect_tabs_speed'];
$jqueryTiki['home_file_gallery'] = $prefs['home_file_gallery'];

$jqueryTiki['autosave'] = $prefs['ajax_autosave'] === 'y' ? true : false;
$jqueryTiki['sefurl'] = $prefs['feature_sefurl'] === 'y' ? true : false;
$jqueryTiki['ajax'] = $prefs['feature_ajax'] === 'y' ? true : false;
$jqueryTiki['syntaxHighlighter'] = $prefs['feature_syntax_highlighter'] === 'y' ? true : false;
$jqueryTiki['select2'] = $prefs['jquery_select2'] === 'y' ? true : false;
$jqueryTiki['select2_sortable'] = $prefs['jquery_select2_sortable'] === 'y' ? true : false;
$jqueryTiki['mapTileSets'] = $tikilib->get_preference('geo_tilesets', ['openstreetmap'], true);
$jqueryTiki['infoboxTypes'] = Services_Object_Controller::supported();
$jqueryTiki['googleStreetView'] = $prefs['geo_google_streetview'] === 'y' ? true : false;
$jqueryTiki['googleStreetViewOverlay'] = $prefs['geo_google_streetview_overlay'] === 'y' ? true : false;
$jqueryTiki['googleMapsAPIKey'] = $prefs['gmap_key'];
$jqueryTiki['structurePageRepeat'] = $prefs['page_n_times_in_a_structure'] === 'y' ? true : false;
$jqueryTiki['no_cookie'] = false;
$jqueryTiki['cookie_consent_analytics'] = $prefs['cookie_consent_analytics'] === 'y' ? true : false;
$jqueryTiki['language'] = $prefs['language'];
$jqueryTiki['useInlineComment'] = $prefs['feature_inline_comments'] === 'y' ? true : false;
$jqueryTiki['useInlineAnnotations'] = $prefs['comments_inline_annotator'] === 'y' ? true : false;
$jqueryTiki['helpurl'] = $prefs['feature_help'] === 'y' ? $prefs['helpurl'] : '';
$jqueryTiki['shortDateFormat'] = $prefs['short_date_format_js'];
$jqueryTiki['shortTimeFormat'] = $prefs['short_time_format_js'];
$jqueryTiki['changeMonth'] = $prefs['change_month'] === 'y' ? true : false;
$jqueryTiki['changeYear'] = $prefs['change_year'] === 'y' ? true : false;
$jqueryTiki['username'] = $user;
$jqueryTiki['userRealName'] = TikiLib::lib('user')->clean_user($user);
$jqueryTiki['userAvatar'] = $base_url . TikiLib::lib('userprefs')->get_public_avatar_path($user);
$jqueryTiki['autoToc_inline'] = $prefs['wiki_inline_auto_toc'] === 'y' ? true : false;
$jqueryTiki['autoToc_pos'] = $prefs['wiki_toc_pos'];
$jqueryTiki['autoToc_offset'] = $prefs['wiki_toc_offset'];
$jqueryTiki['autoToc_tabs'] = $prefs['wiki_toc_tabs'];
$jqueryTiki['bingMapsAPIKey'] = $prefs['geo_bingmaps_key'];
$jqueryTiki['nextzenAPIKey'] = $prefs['geo_nextzen_key'];
$jqueryTiki['numericFieldScroll'] = $prefs['unified_numeric_field_scroll'];
$jqueryTiki['themeIconset'] = $prefs['theme_iconset'];
//set at 4 hours if empty
$jqueryTiki['securityTimeout'] = ! empty($prefs['site_security_timeout']) ? $prefs['site_security_timeout']
    : TikiLib::lib('access')->getDefaultTimeout();

if (empty($object)) {
    $object = current_object();
}
if (empty($object)) {
    $object = ['type' => '', 'object' => ''];
}
$jqueryTiki['current_object'] = $object;
$username_pattern = " / ^ ['\-_a-zA-Z0-9\.]*$/";
if (is_string($prefs['username_pattern'])) {
    if (trim($prefs['username_pattern']) && trim($prefs['username_pattern']) !== '') {
        $jqueryTiki['usernamePattern'] = trim($prefs['username_pattern']);
    } else {
        $jqueryTiki['usernamePattern'] = $username_pattern;
    }
} else {
    $jqueryTiki['usernamePattern'] = $username_pattern;
}
$jqueryTiki['print_pdf_from_url'] = "{$prefs['print_pdf_from_url']}";


if ($prefs['feature_calendar'] === 'y') {
    $calendarlib = TikiLib::lib('calendar');
    $jqueryTiki['firstDayofWeek'] = $calendarlib->firstDayofWeek();
}

$js = '
// JS Object to hold prefs for jq
var jqueryTiki = ' . json_encode($jqueryTiki, JSON_UNESCAPED_SLASHES) . "\n";

if ($prefs['feature_syntax_highlighter'] !== 'y') {
    // add a dummy syntaxHighlighter object as it seems to be used all over the place without checking for the feature
    $js .= '
var syntaxHighlighter = {
    ready: function (textarea, settings) {
        return null;
    },
    sync: function (textarea) {
        return null;
    },
    add: function (editor , $input, none, skipResize) {
        return null;
    },
    remove: function ($input) {
        return null;
    },
    get: function ($input) {
        return null;
    },
    fullscreen: function (textarea) {
        return null;
    },
    find: function (textareaEditor, val) {
        return null;
    },
    searchCursor: [],
    replace: function (textareaEditor, val, replaceVal) {
        return null;
    },
    insertAt: function (textareaEditor, replaceString, perLine, blockLevel) {
        return null;
    }
};
';
}

if ($prefs['jquery_ui_modals_draggable'] === 'y') {
    $js .= '
$(document) . on("shown.bs.modal", function (event) {
    $(event . target) . find(".modal-dialog")
        . css({left: "", top: ""})
        . draggable({
             handle: ".modal-header",
             cursor: "grabbing"
        });
});
';
    $headerlib->add_css(' . modal - header {cursor: grab}');
}

if ($prefs['jquery_ui_modals_resizable'] === 'y') {
    $js .= '
$(document) . on("tiki.modal.redraw", function (event) {
    var $modalContent = $(event . target);
    if (! $modalContent . is(".modal-content")) {
        $modalContent = $modalContent . find(".modal-content")
    }
    if ($modalContent . is(".ui-resizable") && $modalContent . resizable("instance") . started) {
        $modalContent . resizable("destroy");
    }
    $modalContent
        . css({width: "", height: ""})
        . resizable({
            minHeight: 100,
            minWidth: 200
        })
        . find(".modal-body") . css({
            "overflow": "auto"
        });
});
';
}

if ($prefs['feature_realtime'] === 'y') {
    $js .= '
var tikiOpenWS = function (endpoint) {
    return new WebSocket(' . json_encode(preg_replace('#http://#', 'ws://', preg_replace('#https://#', 'wss://', $base_url)) . 'ws/') . ' + endpoint + "?token=' . session_id() . '");
}
';
    // TODO: use a preference for automatic start of WS session on each page - seems resource intensive...
    $js .= '
initTikiGlobalWS();
';
}

$headerlib->add_js($js);

if ($prefs['feature_ajax'] != 'y') {
    $prefs['ajax_autosave'] = 'n';
}
