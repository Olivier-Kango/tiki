<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

//This the default icon set, it associates icon names to icon fonts. It is used as fallback for all other icon sets.

// This script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

function iconset_default()
{
    return [
        'name' => tr('Default (Font-awesome)'), // Mandatory, will be displayed as Icon set option in the Look&Feel admin UI
        'description' => tr('The default system icon set using Font-awesome fonts'), // TODO display as Icon set description in the Look&Feel admin UI
        'tag' => 'span', // The default html tag for the icons in the icon set.
        'prepend' => 'fas fa-',
        'append' => ' fa-fw',
        'styles' => [
            'default' => [
                'name' => tr('Solid'),
                'description' => tr(''),
                'prepend' => 'fas fa-',
                'append' => '',
            ],
            'outline' => [
                'name' => tr('Outline'),
                'description' => tr('Font Awesome Regular'),
                'prepend' => 'far fa-',
                'append' => '',
            ],
            'light' => [
                'name' => tr('Light'),
                'description' => tr('Font Awesome Pro Only'),
                'prepend' => 'fal fa-',
                'append' => '',
            ],
            'brands' => [
                'name' => tr('Brands'),
                'description' => tr(''),
                'prepend' => 'fab fa-',
                'append' => '',
            ],
        ],
        'rotate' => [
            // Rotate the icon (only values accepted by fontawesome)
            '90' => ' fa-rotate-90',
            '180' => ' fa-rotate-180',
            '270' => ' fa-rotate-270',
            'horizontal' => ' fa-flip-horizontal',
            'vertical' => ' fa-flip-vertical',
        ],
        'icons' => [
            /* This is the definition of an icon in the icon set if it's an "alias" to one of the default icons.
             * The key must be unique, it is the "name" parameter at the icon function,
             * so eg: {icon name="actions"}
             * will find 'actions' in the array and apply the specified configuration */

            'actions' => [
                'id' => 'play-circle',    // id to match the defaults defined below
            ],
            'admin' => [
                'id' => 'cog',
            ],
            'add' => [
                'id' => 'plus-circle',
            ],
            'admin_ads' => [
                'id' => 'film',
            ],
            'admin_articles' => [
                'id' => 'newspaper',
                'prepend' => 'far fa-'
            ],
            'admin_blogs' => [
                'id' => 'bold',
            ],
            'admin_calendar' => [
                'id' => 'calendar-alt',
                'prepend' => 'far fa-'
            ],
            'admin_category' => [
                'id' => 'sitemap fa-rotate-270',
            ],
            'admin_comments' => [
                'id' => 'comment',
            ],
            'admin_community' => [
                'id' => 'users',
            ],
            'admin_connect' => [
                'id' => 'link',
            ],
            'admin_copyright' => [
                'id' => 'copyright',
                'prepend' => 'far fa-'
            ],
            'admin_directory' => [
                'id' => 'folder',
                'prepend' => 'far fa-'
            ],
            'admin_faqs' => [
                'id' => 'question',
            ],
            'admin_features' => [
                'id' => 'power-off',
            ],
            'admin_fgal' => [
                'id' => 'folder-open',
            ],
            'admin_forums' => [
                'id' => 'comments',
            ],
            'admin_freetags' => [
                'id' => 'tags',
            ],
            'admin_gal' => [
                'id' => 'file-image',
                'prepend' => 'far fa-'
            ],
            'admin_general' => [
                'id' => 'cog',
            ],
            'admin_i18n' => [
                'id' => 'language',
            ],
            'admin_intertiki' => [
                'id' => 'exchange-alt',
            ],
            'admin_login' => [
                'id' => 'sign-in-alt',
            ],
            'admin_user' => [
                'id' => 'user',
            ],
            'admin_look' => [
                'id' => 'image',
                'prepend' => 'far fa-'
            ],
            'admin_maps' => [
                'id' => 'map-marker-alt',
            ],
            'admin_messages' => [
                'id' => 'envelope',
                'prepend' => 'far fa-'
            ],
            'admin_metatags' => [
                'id' => 'tag',
            ],
            'admin_module' => [
                'id' => 'shapes',
            ],
            'admin_payment' => [
                'id' => 'credit-card',
                'prepend' => 'far fa-'
            ],
            'admin_performance' => [
                'id' => 'tachometer-alt',
            ],
            'admin_polls' => [
                'id' => 'tasks',
            ],
            'admin_profiles' => [
                'id' => 'cube',
            ],
            'admin_rating' => [
                'id' => 'check-square',
            ],
            'admin_rss' => [
                'id' => 'rss',
            ],
            'admin_score' => [
                'id' => 'trophy',
            ],
            'admin_search' => [
                'id' => 'search',
            ],
            'admin_semantic' => [
                'id' => 'arrows-alt-h',
            ],
            'admin_security' => [
                'id' => 'lock',
            ],
            'admin_sefurl' => [
                'id' => 'search-plus',
            ],
            'admin_mautic' => [
                'id' => 'book',
            ],
            'admin_share' => [
                'id' => 'share-alt',
            ],
            'admin_socialnetworks' => [
                'id' => 'thumbs-up',
            ],
            'admin_stats' => [
                'id' => 'chart-bar',
                'prepend' => 'far fa-'
            ],
            'admin_textarea' => [
                'id' => 'edit',
            ],
            'admin_trackers' => [
                'id' => 'database',
            ],
            'admin_userfiles' => [
                'id' => 'cog',
            ],
            'admin_video' => [
                'id' => 'video',
            ],
            'admin_webmail' => [
                'id' => 'inbox',
            ],
            'admin_webservices' => [
                'id' => 'mouse-pointer',
            ],
            'admin_wiki' => [
                'id' => 'file-alt',
                'prepend' => 'far fa-'
            ],
            'admin_workspace' => [
                'id' => 'desktop',
            ],
            'admin_wysiwyg' => [
                'id' => 'file-alt',
            ],
            'admin_print' => [
                'id' => 'print',
            ],
            'admin_packages' => [
                'id' => 'gift',
            ],
            'admin_rtc' => [
                'id' => 'bullhorn',
            ],
            'admin_wizard' => [
                'id' => 'magic',
            ],
            'admin_section_general' => [
                'id' => 'tools',
            ],
            'admin_section_content' => [
                'id' => 'toolbox',
            ],
            'admin_section_other' => [
                'id' => 'flask',
            ],
            'admin_section_community' => [
                'id' => 'users-cog',
            ],
            'admin_section_backend' => [
                'id' => 'cogs',
            ],
            'adn' => [
                'id' => 'adn',
                'prepend' => 'fab fa-'
            ],
            //align-center in defaults
            //align-justify in defaults
            //align-left in defaults
            //align-right in defaults
            'amazon' => [
                'id' => 'amazon',
                'prepend' => 'fab fa-'
            ],
            //anchor in defaults
            'android' => [
                'id' => 'android',
                'prepend' => 'fab fa-'
            ],
            'angellist' => [
                'id' => 'angellist',
                'prepend' => 'fab fa-'
            ],
            'apple' => [
                'id' => 'apple',
                'prepend' => 'fab fa-'
            ],
            'area-chart' => [
                'id' => 'chart-area'
            ],
            'arrows' => [
                'id' => 'arrows-alt'
            ],
            'arrows-h' => [
                'id' => 'arrows-alt-h'
            ],
            'arrows-v' => [
                'id' => 'arrows-alt-v'
            ],
            'articles' => [
                'id' => 'newspaper',
                'prepend' => 'far fa-'
            ],
            //arrow-up in defaults
            'attach' => [
                'id' => 'paperclip',
            ],
            'audio' => [
                'id' => 'file-audio',
                'prepend' => 'far fa-'
            ],
            'back' => [
                'id' => 'arrow-left',
            ],
            'background-color' => [
                'id' => 'paint-brush',
            ],
            'backlink' => [
                'id' => 'reply',
            ],
            //backward in defaults
            'backward_step' => [
                'id' => 'step-backward',
            ],
            'bar-chart' => [
                'id' => 'chart-bar'
            ],
            //ban in defaults
            'behance' => [
                'id' => 'behance',
                'prepend' => 'fab fa-'
            ],
            'behance-square' => [
                'id' => 'behance-square',
                'prepend' => 'fab fa-'
            ],
            'bitbucket' => [
                'id' => 'bitbucket',
                'prepend' => 'fab fa-'
            ],
            'black-tie' => [
                'id' => 'black-tie',
                'prepend' => 'fab fa-'
            ],
            'bluetooth' => [
                'id' => 'bluetooth',
                'prepend' => 'fab fa-'
            ],
            'bluetooth-b' => [
                'id' => 'bluetooth-b',
                'prepend' => 'fab fa-'
            ],
            //book in defaults
            'box' => [
                'id' => 'list-alt',
                'prepend' => 'far fa-'
            ],
            'btc' => [
                'id' => 'btc',
                'prepend' => 'fab fa-'
            ],
            'buysellads' => [
                'id' => 'buysellads',
                'prepend' => 'fab fa-'
            ],
            //caret-left & caret-right in defaults
            'cart' => [
                'id' => 'shopping-cart',
            ],
            'chart' => [
                'id' => 'chart-area',
            ],
            'cc-amex' => [
                'id' => 'cc-amex',
                'prepend' => 'fab fa-'
            ],
            'cc-diners-club' => [
                'id' => 'cc-diners-club',
                'prepend' => 'fab fa-'
            ],
            'cc-discover' => [
                'id' => 'cc-discover',
                'prepend' => 'fab fa-'
            ],
            'cc-jcb' => [
                'id' => 'cc-jcb',
                'prepend' => 'fab fa-'
            ],
            'cc-mastercard' => [
                'id' => 'cc-mastercard',
                'prepend' => 'fab fa-'
            ],
            'cc-paypal' => [
                'id' => 'cc-paypal',
                'prepend' => 'fab fa-'
            ],
            'cc-stripe' => [
                'id' => 'cc-stripe',
                'prepend' => 'fab fa-'
            ],
            'cc-visa' => [
                'id' => 'cc-visa',
                'prepend' => 'fab fa-'
            ],
            'chrome' => [
                'id' => 'chrome',
                'prepend' => 'fab fa-'
            ],
            'close' => [
                'id' => 'times',
            ],
            'cloud-download' => [
                'id' => 'cloud-download-alt',
            ],
            'cloud-upload' => [
                'id' => 'cloud-upload-alt',
            ],
            //code in defaults
            'code_file' => [
                'id' => 'file-code',
                'prepend' => 'far fa-'
            ],
            'code-fork' => [
                'id' => 'code-branch',
            ],
            'codepen' => [
                'id' => 'codepen',
                'prepend' => 'fab fa-'
            ],
            'codiepie' => [
                'id' => 'codiepie',
                'prepend' => 'fab fa-'
            ],
            'collapsed' => [
                'id' => 'plus-square',
                'prepend' => 'far fa-'
            ],
            //columns in defaults
            'comments' => [
                'id' => 'comments',
                'prepend' => 'far fa-'
            ],
            'compose' => [
                'id' => 'pencil-alt',
            ],
            'computer' => [
                'id' => 'desktop',
            ],
            'contacts' => [
                'id' => 'users',
            ],
            'content-template' => [
                'id' => 'file',
                'prepend' => 'far fa-'
            ],
            //copy in defaults
            'create' => [
                'id' => 'plus',
            ],
            'creative-commons' => [
                'id' => 'creative-commons',
                'prepend' => 'fab fa-'
            ],
            'css3' => [
                'id' => 'css3',
                'prepend' => 'fab fa-'
            ],
            'dashboard' => [
                'id' => 'tachometer-alt',
            ],
            'dashcube' => [
                'id' => 'dashcube',
                'prepend' => 'fab fa-'
            ],
            //database in defaults
            'delete' => [
                'id' => 'times',
            ],
            'delicious' => [
                'id' => 'delicious',
                'prepend' => 'fab fa-'
            ],
            'deviantart' => [
                'id' => 'deviantart',
                'prepend' => 'fab fa-'
            ],
            'difference' => [
                'id' => 'strikethrough',
            ],
            'disable' => [
                'id' => 'minus-square',
            ],
            'documentation' => [
                'id' => 'book',
            ],
            'down' => [
                'id' => 'sort-down',
            ],
            'dribbble' => [
                'id' => 'dribbble',
                'prepend' => 'fab fa-'
            ],
            'dropbox' => [
                'id' => 'dropbox',
                'prepend' => 'fab fa-'
            ],
            'drupal' => [
                'id' => 'drupal',
                'prepend' => 'fab fa-'
            ],
            'edge' => [
                'id' => 'edge',
                'prepend' => 'fab fa-'
            ],
            //edit in defaults
            'education' => [
                'id' => 'graduation-cap',
            ],
            'empire' => [
                'id' => 'empire',
                'prepend' => 'fab fa-'
            ],
            'envelope' => [
                'id' => 'envelope',
                'prepend' => 'far fa-'
            ],
            'envira' => [
                'id' => 'envira',
                'prepend' => 'fab fa-'
            ],
            'erase' => [
                'id' => 'eraser',
            ],
            'error' => [
                'id' => 'exclamation-circle',
            ],
            'excel' => [
                'id' => 'file-excel',
                'prepend' => 'far fa-'
            ],
            'exchange' => [
                'id' => 'exchange-alt'
            ],
            'expanded' => [
                'id' => 'minus-square',
                'prepend' => 'far fa-'
            ],
            'expeditedssl' => [
                'id' => 'expeditedssl',
                'prepend' => 'fab fa-'
            ],
            'export' => [
                'id' => 'download',
            ],
            'facebook' => [
                'id' => 'facebook',
                'prepend' => 'fab fa-'
            ],
            'facebook-f' => [
                'id' => 'facebook-f',
                'prepend' => 'fab fa-'
            ],
            'file' => [
                'id' => 'file',
                'prepend' => 'far fa-'
            ],
            'file-archive' => [
                'id' => 'folder',
            ],
            'file-archive-open' => [
                'id' => 'folder-open',
            ],
            'file-text' => [
                'id' => 'file-alt'
            ],
            'file-text-o' => [
                'id' => 'file-alt',
                'prepend' => 'far fa-'
            ],
            //filter in defaults
            'firefox' => [
                'id' => 'firefox',
                'prepend' => 'fab fa-'
            ],
            'first-order' => [
                'id' => 'first-order',
                'prepend' => 'fab fa-'
            ],
            //flag in defaults
            'flickr' => [
                'id' => 'flickr',
                'prepend' => 'fab fa-'
            ],
            'floppy' => [
                'id' => 'save',
                'prepend' => 'far fa-'
            ],
            'font-awesome' => [
                'id' => 'font-awesome',
                'prepend' => 'fab fa-'
            ],
            'font-color' => [
                'id' => 'font',
                'class' => 'text-danger'
            ],
            'fonticons' => [
                'id' => 'fonticons',
                'prepend' => 'fab fa-'
            ],
            'fort-awesome' => [
                'id' => 'fort-awesome',
                'prepend' => 'fab fa-'
            ],
            'forumbee' => [
                'id' => 'forumbee',
                'prepend' => 'fab fa-'
            ],
            //forward in defaults
            'forward_step' => [
                'id' => 'step-forward',
            ],
            'foursquare' => [
                'id' => 'foursquare',
                'prepend' => 'fab fa-'
            ],
            'fullscreen' => [
                'id' => 'expand-arrows-alt',
            ],
            'get-pocket' => [
                'id' => 'get-pocket',
                'prepend' => 'fab fa-'
            ],
            'gg' => [
                'id' => 'gg',
                'prepend' => 'fab fa-'
            ],
            'gg-circle' => [
                'id' => 'gg-circle',
                'prepend' => 'fab fa-'
            ],
            'git' => [
                'id' => 'git',
                'prepend' => 'fab fa-'
            ],
            'git-square' => [
                'id' => 'git-square',
                'prepend' => 'fab fa-'
            ],
            'github' => [
                'id' => 'github',
                'prepend' => 'fab fa-'
            ],
            'github-alt' => [
                'id' => 'github-alt',
                'prepend' => 'fab fa-'
            ],
            'github-square' => [
                'id' => 'github-square',
                'prepend' => 'fab fa-'
            ],
            'gitlab' => [
                'id' => 'gitlab',
                'prepend' => 'fab fa-'
            ],
            'glide' => [
                'id' => 'glide',
                'prepend' => 'fab fa-'
            ],
            'glide-g' => [
                'id' => 'glide-g',
                'prepend' => 'fab fa-'
            ],
            'google' => [
                'id' => 'google',
                'prepend' => 'fab fa-'
            ],
            'google-plus' => [
                'id' => 'google-plus',
                'prepend' => 'fab fa-'
            ],
            'google-plus-g' => [
                'id' => 'google-plus-g',
                'prepend' => 'fab fa-'
            ],
            'google-plus-square' => [
                'id' => 'google-plus-square',
                'prepend' => 'fab fa-'
            ],
            'group' => [
                'id' => 'users',
            ],
            'h1' => [
                'id' => 'heading',
            ],
            'h2' => [
                'id' => 'heading',
                'size' => '.9'
            ],
            'h3' => [
                'id' => 'heading',
                'size' => '.8'
            ],
            'hacker-news' => [
                'id' => 'hacker-news',
                'prepend' => 'fab fa-'
            ],
            'help' => [
                'id' => 'question-circle',
            ],
            'history' => [
                'id' => 'clock',
                'prepend' => 'far fa-'
            ],
            //history in defaults
            'horizontal-rule' => [
                'id' => 'minus',
            ],
            'houzz' => [
                'id' => 'houzz',
                'prepend' => 'fab fa-'
            ],
            'html' => [
                'id' => 'html5',
                'prepend' => 'fab fa-'
            ],
            'image' => [
                'id' => 'file-image',
                'prepend' => 'far fa-'
            ],
            'import' => [
                'id' => 'upload',
            ],
            //indent in defaults
            'index' => [
                'id' => 'spinner',
            ],
            'information' => [
                'id' => 'info-circle',
            ],
            'instagram' => [
                'id' => 'instagram',
                'prepend' => 'fab fa-'
            ],
            'internet-explorer' => [
                'id' => 'internet-explorer',
                'prepend' => 'fab fa-'
            ],
            'ioxhost' => [
                'id' => 'ioxhost',
                'prepend' => 'fab fa-'
            ],
            //italic in defaults
            'java' => [
                'id' => 'java',
                'prepend' => 'fab fa-'
            ],
            'joomla' => [
                'id' => 'joomla',
                'prepend' => 'fab fa-'
            ],
            'js' => [
                'id' => 'js',
                'prepend' => 'fab fa-'
            ],
            'jsfiddle' => [
                'id' => 'jsfiddle',
                'prepend' => 'fab fa-'
            ],
            'keyboard' => [
                'id' => 'keyboard',
                'prepend' => 'far fa-'
            ],
            'lastfm' => [
                'id' => 'lastfm',
                'prepend' => 'fab fa-'
            ],
            'lastfm-square' => [
                'id' => 'lastfm-square',
                'prepend' => 'fab fa-'
            ],
            'leanpub' => [
                'id' => 'leanpub',
                'prepend' => 'fab fa-'
            ],
            'less' => [
                'id' => 'less',
                'prepend' => 'fab fa-'
            ],
            'level-down' => [
                'id' => 'level-down-alt',
            ],
            'level-up' => [
                'id' => 'level-up-alt',
            ],
            'like' => [
                'id' => 'thumbs-up',
            ],
            'line-chart' => [
                'id' => 'chart-line'
            ],
            //link in defaults
            'link-external' => [
                'id' => 'external-link-alt',
            ],
            'link-external-alt' => [
                'id' => 'external-link-square-alt',
            ],
            'linkedin' => [
                'id' => 'linkedin',
                'prepend' => 'fab fa-'
            ],
            'linkedin-in' => [
                'id' => 'linkedin-in',
                'prepend' => 'fab fa-'
            ],
            'linux' => [
                'id' => 'linux',
                'prepend' => 'fab fa-'
            ],
            //list in defaults
            'list-numbered' => [
                'id' => 'list-ol',
            ],
            // special icons for list gui toolbars
            'listgui_display' => [
                'id' => 'desktop',
            ],
            'listgui_filter' => [
                'id' => 'filter',
            ],
            'listgui_format' => [
                'id' => 'indent',
            ],
            'listgui_pagination' => [
                'id' => 'book',
            ],
            'listgui_output' => [
                'id' => 'eye',
                'prepend' => 'far fa-'
            ],
            'listgui_column' => [
                'id' => 'columns',
            ],
            'listgui_tablesorter' => [
                'id' => 'table',
            ],
            'listgui_icon' => [
                'id' => 'user',
            ],
            'listgui_body' => [
                'id' => 'align-justify',
            ],
            'listgui_carousel' => [
                'id' => 'slideshare',
                'prepend' => 'fab fa-'
            ],
            'listgui_sort' => [
                'id' => 'sort-alpha-up',
            ],
            'listgui_wikitext' => [
                'id' => 'file-alt',
                'prepend' => 'far fa-'
            ],
            'listgui_caption' => [
                'id' => 'align-center',
            ],
            //lock in defaults
            //same fa icon used for admin_security, but not the same in other icon sets
            'log' => [
                'id' => 'history',
            ],
            'login' => [
                'id' => 'sign-in-alt',
            ],
            'logout' => [
                'id' => 'sign-out-alt',
            ],
            'long-arrow-down' => [
                'id' => 'long-arrow-alt-down',
            ],
            'long-arrow-left' => [
                'id' => 'long-arrow-alt-left',
            ],
            'long-arrow-right' => [
                'id' => 'long-arrow-alt-right',
            ],
            'long-arrow-up' => [
                'id' => 'long-arrow-alt-up',
            ],
            'mailbox' => [
                'id' => 'inbox',
            ],
            'magnifier' => [
                'id' => 'search',
            ],
            //map in defaults
            'maxcdn' => [
                'id' => 'maxcdn',
                'prepend' => 'fab fa-'
            ],
            'medium' => [
                'id' => 'medium',
                'prepend' => 'fab fa-'
            ],
            'menu' => [
                'id' => 'bars',
            ],
            'menu-extra' => [
                'id' => 'chevron-down',
            ],
            'menuitem' => [
                'id' => 'angle-right',
            ],
            'merge' => [
                'id' => 'random',
            ],
            'microsoft' => [
                'id' => 'microsoft',
                'prepend' => 'fab fa-'
            ],
            'minimize' => [
                'id' => 'compress',
            ],
            //minus in defaults
            'mixcloud' => [
                'id' => 'mixcloud',
                'prepend' => 'fab fa-'
            ],
            'module' => [
                'id' => 'square',
            ],
            'modules' => [
                'id' => 'shapes',
            ],
            'modx' => [
                'id' => 'modx',
                'prepend' => 'fab fa-'
            ],
            'money' => [
                'id' => 'money-bill',
            ],
            'more' => [
                'id' => 'ellipsis-h',
            ],
            'move' => [
                'id' => 'exchange-alt',
            ],
            'next' => [
                'id' => 'arrow-right',
            ],
            'notepad' => [
                'id' => 'file-alt',
                'prepend' => 'far fa-'
            ],
            'notification' => [
                'id' => 'bell',
                'prepend' => 'far fa-'
            ],
            'off' => [
                'id' => 'power-off',
            ],
            'ok' => [
                'id' => 'check-circle',
            ],
            'opencart' => [
                'id' => 'opencart',
                'prepend' => 'fab fa-'
            ],
            'openid' => [
                'id' => 'openid',
                'prepend' => 'fab fa-'
            ],
            'opera' => [
                'id' => 'opera',
                'prepend' => 'fab fa-'
            ],
            'optin-monster' => [
                'id' => 'optin-monster',
                'prepend' => 'fab fa-'
            ],
            //outdent in defaults
            'page-break' => [
                'id' => 'cut',
            ],
            'pagelines' => [
                'id' => 'pagelines',
                'prepend' => 'fab fa-'
            ],
            'paypal' => [
                'id' => 'paypal',
                'prepend' => 'fab fa-'
            ],
            //paste in defaults
            //pause in defaults
            'pdf' => [
                'id' => 'file-pdf',
                'prepend' => 'far fa-'
            ],
            'pencil' => [
                'id' => 'pencil-alt',
            ],
            'permission' => [
                'id' => 'key',
            ],
            'pie-chart' => [
                'id' => 'chart-pie',
            ],
            'pied-piper' => [
                'id' => 'pied-piper',
                'prepend' => 'fab fa-'
            ],
            'pied-piper-alt' => [
                'id' => 'pied-piper-alt',
                'prepend' => 'fab fa-'
            ],
            'pied-piper-pp' => [
                'id' => 'pied-piper-pp',
                'prepend' => 'fab fa-'
            ],
            'pinterest' => [
                'id' => 'pinterest',
                'prepend' => 'fab fa-'
            ],
            'pinterest-p' => [
                'id' => 'pinterest-p',
                'prepend' => 'fab fa-'
            ],
            'pinterest-square' => [
                'id' => 'pinterest-square',
                'prepend' => 'fab fa-'
            ],
            //play in defaults
            'plugin' => [
                'id' => 'puzzle-piece',
            ],
            'popup' => [
                'id' => 'list-alt',
                'prepend' => 'far fa-'
            ],
            'post' => [
                'id' => 'pencil-alt',
            ],
            'powerpoint' => [
                'id' => 'file-powerpoint',
                'prepend' => 'far fa-'
            ],
            'previous' => [
                'id' => 'arrow-left',
            ],
            //print in defaults
            'qq' => [
                'id' => 'qq',
                'prepend' => 'fab fa-'
            ],
            'quotes' => [
                'id' => 'quote-left',
            ],
            'ranking' => [
                'id' => 'sort-numeric-down',
            ],
            'reddit' => [
                'id' => 'reddit',
                'prepend' => 'fab fa-'
            ],
            'reddit-alien' => [
                'id' => 'reddit-alien',
                'prepend' => 'fab fa-'
            ],
            'reddit-square' => [
                'id' => 'reddit-square',
                'prepend' => 'fab fa-'
            ],
            'refresh' => [
                'id' => 'sync',
            ],
            'remove' => [
                'id' => 'times',
            ],
            'renren' => [
                'id' => 'renren',
                'prepend' => 'fab fa-'
            ],
            'repeat' => [
                'id' => 'redo',
            ],
            //rss in defaults
            'safari' => [
                'id' => 'safari',
                'prepend' => 'fab fa-'
            ],
            'sass' => [
                'id' => 'sass',
                'prepend' => 'fab fa-'
            ],
            'scissors' => [
                'id' => 'cut',
            ],
            'scribd' => [
                'id' => 'scribd',
                'prepend' => 'fab fa-'
            ],
            'screencapture' => [
                'id' => 'camera',
            ],
            //search in defaults
            'selectall' => [
                'id' => 'file-alt',
            ],
            'send' => [
                'id' => 'paper-plane',
            ],
            'settings' => [
                'id' => 'wrench',
            ],
            //share in defaults
            'sharethis' => [
                'id' => 'share-alt',
            ],
            'shorten' => [
                'id' => 'crop',
            ],
            'simplybuilt' => [
                'id' => 'simplybuilt',
                'prepend' => 'fab fa-'
            ],
            'skyatlas' => [
                'id' => 'skyatlas',
                'prepend' => 'fab fa-'
            ],
            'skype' => [
                'id' => 'skype',
                'prepend' => 'fab fa-'
            ],
            'slack' => [
                'id' => 'slack',
                'prepend' => 'fab fa-'
            ],
            'smile' => [
                'id' => 'smile',
                'prepend' => 'far fa-'
            ],
            'snapchat' => [
                'id' => 'snapchat',
                'prepend' => 'fab fa-'
            ],
            'snapchat-ghost' => [
                'id' => 'snapchat-ghost',
                'prepend' => 'fab fa-'
            ],
            'snapchat-square' => [
                'id' => 'snapchat-square',
                'prepend' => 'fab fa-'
            ],
            //sort in defaults
            'sort-asc' => [
                'id' => 'sort-up',
            ],
            'sort-alpha-asc' => [
                'id' => 'sort-alpha-up',
            ],
            'sort-alpha-desc' => [
                'id' => 'sort-alpha-down',
            ],
            'sort-amount-asc' => [
                'id' => 'sort-amount-up',
            ],
            'sort-amount-desc' => [
                'id' => 'sort-amount-down',
            ],
            'sort-desc' => [
                'id' => 'sort-down',
            ],
            'sort-down' => [
                'id' => 'sort-down',
            ],
            'sort-numeric-asc' => [
                'id' => 'sort-numeric-up',
            ],
            'sort-numeric-desc' => [
                'id' => 'sort-numeric-down',
            ],
            'sort-up' => [
                'id' => 'sort-up',
            ],
            'soundcloud' => [
                'id' => 'soundcloud',
                'prepend' => 'fab fa-'
            ],
            'spotify' => [
                'id' => 'spotify',
                'prepend' => 'fab fa-'
            ],
            'stack-exchange' => [
                'id' => 'stack-exchange',
                'prepend' => 'fab fa-'
            ],
            'stack-overflow' => [
                'id' => 'stack-overflow',
                'prepend' => 'fab fa-'
            ],
            //star in defaults
            'star-empty' => [
                'id' => 'star',
                'prepend' => 'far fa-'
            ],
            'star-empty-selected' => [
                'id' => 'star',
                'prepend' => 'far fa-',
                'class' => 'text-success'
            ],
            'star-half-rating' => [
                'id' => 'star-half',
                'prepend' => 'far fa-'
            ],
            'star-half-selected' => [
                'id' => 'star-half',
                'prepend' => 'far fa-',
                'class' => 'text-success'
            ],
            'star-selected' => [
                'id' => 'star',
                'class' => 'text-success'
            ],
            'status-open' => [
                'id' => 'circle',
                'style' => 'color:green'
            ],
            'status-pending' => [
                'id' => 'adjust',
                'style' => 'color:orange'
            ],
            'status-closed' => [
                'id' => 'times-circle',
                'prepend' => 'far fa-',
                'style' => 'color:grey'
            ],
            'steam' => [
                'id' => 'steam',
                'prepend' => 'fab fa-'
            ],
            'steam-square' => [
                'id' => 'steam-square',
                'prepend' => 'fab fa-'
            ],
            //stop in defaults
            'stop-watching' => [
                'id' => 'eye-slash',
                'prepend' => 'far fa-'
            ],
            'structure' => [
                'id' => 'sitemap',
            ],
            'stumbleupon' => [
                'id' => 'stumbleupon',
                'prepend' => 'fab fa-'
            ],
            'success' => [
                'id' => 'check',
            ],
            //table in defaults
            //tag in defaults
            //tags in defaults
            'textfile' => [
                'id' => 'file-alt',
                'prepend' => 'far fa-'
            ],
            //th-list in defaults
            'themeisle' => [
                'id' => 'themeisle',
                'prepend' => 'fab fa-'
            ],
            'three-d' => [
                'id' => 'cube',
            ],
            //thumbs-down in defaults
            //thumbs-up in defaults
            'ticket' => [
                'id' => 'ticket-alt',
            ],
            'time' => [
                'id' => 'clock',
                'prepend' => 'far fa-'
            ],
            'title' => [
                'id' => 'text-width',
            ],
            'toggle-left' => [
                'id' => 'caret-square-left',
                'prepend' => 'far fa-'
            ],
            'toggle-off' => [
                'id' => 'toggle-off',
            ],
            'toggle-on' => [
                'id' => 'toggle-on',
            ],
            'toggle-right' => [
                'id' => 'caret-square-right',
                'prepend' => 'far fa-'
            ],
            'trackers' => [
                'id' => 'database',
            ],
            'translate' => [
                'id' => 'language',
            ],
            'trash' => [
                'id' => 'trash-alt',
                'prepend' => 'far fa-'
            ],
            'trello' => [
                'id' => 'trello',
                'prepend' => 'fab fa-'
            ],
            'tripadvisor' => [
                'id' => 'tripadvisor',
                'prepend' => 'fab fa-'
            ],
            'tumblr' => [
                'id' => 'tumblr',
                'prepend' => 'fab fa-'
            ],
            'tumblr-square' => [
                'id' => 'tumblr-square',
                'prepend' => 'fab fa-'
            ],
            'twitch' => [
                'id' => 'twitch',
                'prepend' => 'fab fa-'
            ],
            'twitter' => [
                'id' => 'twitter',
                'prepend' => 'fab fa-'
            ],
            'twitter-square' => [
                'id' => 'twitter-square',
                'prepend' => 'fab fa-'
            ],
            //tv in defaults
            //undo in defaults
            //unlink in defaults
            //unlock in defaults
            'unlike' => [
                'id' => 'thumbs-down',
            ],
            'up' => [
                'id' => 'sort-up',
            ],
            'usb' => [
                'id' => 'usb',
                'prepend' => 'fab fa-'
            ],
            'viacoin' => [
                'id' => 'viacoin',
                'prepend' => 'fab fa-'
            ],
            'video' => [
                'id' => 'file-video',
                'prepend' => 'far fa-'
            ],
            'video_file' => [
                'id' => 'file-video',
                'prepend' => 'far fa-'
            ],
            'view' => [
                'id' => 'search-plus',
            ],
            'vimeo' => [
                'id' => 'vimeo-square',
                'prepend' => 'fab fa-'
            ],
            'vine' => [
                'id' => 'vine',
                'prepend' => 'fab fa-'
            ],
            'vk' => [
                'id' => 'vk',
                'prepend' => 'fab fa-'
            ],
            'warning' => [
                'id' => 'exclamation-triangle',
            ],
            'watch' => [
                'id' => 'eye',
                'prepend' => 'far fa-'
            ],
            'watch-group' => [
                'id' => 'users',
            ],
            'weibo' => [
                'id' => 'weibo',
                'prepend' => 'fab fa-'
            ],
            'whatsapp' => [
                'id' => 'whatsapp',
                'prepend' => 'fab fa-'
            ],
            'windows' => [
                'id' => 'windows',
                'prepend' => 'fab fa-'
            ],
            'wiki' => [
                'id' => 'file-alt',
                'prepend' => 'far fa-'
            ],
            'wizard' => [
                'id' => 'magic',
            ],
            'word' => [
                'id' => 'file-word',
                'prepend' => 'far fa-'
            ],
            'wysiwyg' => [
                'id' => 'file-alt',
            ],
            'xbox' => [
                'id' => 'xbox',
                'prepend' => 'fab fa-'
            ],
            'xing' => [
                'id' => 'xing',
                'prepend' => 'fab fa-'
            ],
            'xing-square' => [
                'id' => 'xing-square',
                'prepend' => 'fab fa-'
            ],
            'yahoo' => [
                'id' => 'yahoo',
                'prepend' => 'fab fa-'
            ],
            'youtube' => [
                'id' => 'youtube',
                'prepend' => 'fab fa-'
            ],
            'youtube-square' => [
                'id' => 'youtube-square',
                'prepend' => 'fab fa-'
            ],
            'zip' => [
                'id' => 'file-archive',
                'prepend' => 'far fa-'
            ],
        ],
        /*
         * All the available icons in this set based on https://fontawesome.com/v5/cheatsheet/free
         *
         * Solid icons list, https://fontawesome.com/v5/cheatsheet/free/solid
         * Solid Style (fas), use solid, regular (outline), style in the plugin icons parameter
         * Note: all icons from https://fontawesome.com/v5/cheatsheet/free/regular are already here
        */
        'defaults' => [
            'ad',
            'address-book',
            'address-card',
            'adjust',
            'air-freshener',
            'align-center',
            'align-justify',
            'align-left',
            'align-right',
            'allergies',
            'ambulance',
            'american-sign-language-interpreting',
            'anchor',
            'angle-double-down',
            'angle-double-left',
            'angle-double-right',
            'angle-double-up',
            'angle-down',
            'angle-left',
            'angle-right',
            'angle-up',
            'angry',
            'ankh',
            'apple-alt',
            'archive',
            'archway',
            'arrow-alt-circle-down',
            'arrow-alt-circle-left',
            'arrow-alt-circle-right',
            'arrow-alt-circle-up',
            'arrow-circle-down',
            'arrow-circle-left',
            'arrow-circle-right',
            'arrow-circle-up',
            'arrow-down',
            'arrow-left',
            'arrow-right',
            'arrow-up',
            'arrows-alt',
            'arrows-alt-h',
            'arrows-alt-v',
            'assistive-listening-systems',
            'asterisk',
            'at',
            'atlas',
            'atom',
            'audio-description',
            'award',
            'baby',
            'baby-carriage',
            'backspace',
            'backward',
            'bacon',
            'bacteria',
            'bacterium',
            'bahai',
            'balance-scale',
            'balance-scale-left',
            'balance-scale-right',
            'ban',
            'band-aid',
            'barcode',
            'bars',
            'baseball-ball',
            'basketball-ball',
            'bath',
            'battery-empty',
            'battery-full',
            'battery-half',
            'battery-quarter',
            'battery-three-quarters',
            'bed',
            'beer',
            'bell',
            'bell-slash',
            'bezier-curve',
            'bible',
            'bicycle',
            'biking',
            'binoculars',
            'biohazard',
            'birthday-cake',
            'blender',
            'blender-phone',
            'blind',
            'blog',
            'bold',
            'bolt',
            'bomb',
            'bone',
            'bong',
            'book',
            'book-dead',
            'book-medical',
            'book-open',
            'book-reader',
            'bookmark',
            'border-all',
            'border-none',
            'border-style',
            'bowling-ball',
            'box',
            'box-open',
            'box-tissue',
            'boxes',
            'braille',
            'brain',
            'bread-slice',
            'briefcase',
            'briefcase-medical',
            'broadcast-tower',
            'broom',
            'brush',
            'bug',
            'building',
            'bullhorn',
            'bullseye',
            'burn',
            'bus',
            'bus-alt',
            'business-time',
            'calculator',
            'calendar',
            'calendar-alt',
            'calendar-check',
            'calendar-day',
            'calendar-minus',
            'calendar-plus',
            'calendar-times',
            'calendar-week',
            'camera',
            'camera-retro',
            'campground',
            'candy-cane',
            'cannabis',
            'capsules',
            'car',
            'car-alt',
            'car-battery',
            'car-crash',
            'car-side',
            'caravan',
            'caret-down',
            'caret-left',
            'caret-right',
            'caret-square-down',
            'caret-square-left',
            'caret-square-right',
            'caret-square-up',
            'caret-up',
            'carrot',
            'cart-arrow-down',
            'cart-plus',
            'cash-register',
            'cat',
            'certificate',
            'chair',
            'chalkboard',
            'chalkboard-teacher',
            'charging-station',
            'chart-area',
            'chart-bar',
            'chart-line',
            'chart-pie',
            'check',
            'check-circle',
            'check-double',
            'check-square',
            'cheese',
            'chess',
            'chess-bishop',
            'chess-board',
            'chess-king',
            'chess-knight',
            'chess-pawn',
            'chess-queen',
            'chess-rook',
            'chevron-circle-down',
            'chevron-circle-left',
            'chevron-circle-right',
            'chevron-circle-up',
            'chevron-down',
            'chevron-left',
            'chevron-right',
            'chevron-up',
            'child',
            'church',
            'circle',
            'circle-notch',
            'city',
            'clinic-medical',
            'clipboard',
            'clipboard-check',
            'clipboard-list',
            'clock',
            'clone',
            'closed-captioning',
            'cloud',
            'cloud-download-alt',
            'cloud-meatball',
            'cloud-moon',
            'cloud-moon-rain',
            'cloud-rain',
            'cloud-showers-heavy',
            'cloud-sun',
            'cloud-sun-rain',
            'cloud-upload-alt',
            'cocktail',
            'code',
            'code-branch',
            'coffee',
            'cog',
            'cogs',
            'coins',
            'columns',
            'comment',
            'comment-alt',
            'comment-dollar',
            'comment-dots',
            'comment-medical',
            'comment-slash',
            'comments',
            'comments-dollar',
            'compact-disc',
            'compass',
            'compress',
            'compress-alt',
            'compress-arrows-alt',
            'concierge-bell',
            'cookie',
            'cookie-bite',
            'copy',
            'copyright',
            'couch',
            'credit-card',
            'crop',
            'crop-alt',
            'cross',
            'crosshairs',
            'crow',
            'crown',
            'crutch',
            'cube',
            'cubes',
            'cut',
            'database',
            'deaf',
            'democrat',
            'desktop',
            'dharmachakra',
            'diagnoses',
            'dice',
            'dice-d20',
            'dice-d6',
            'dice-five',
            'dice-four',
            'dice-one',
            'dice-six',
            'dice-three',
            'dice-two',
            'digital-tachograph',
            'directions',
            'disease',
            'divide',
            'dizzy',
            'dna',
            'dog',
            'dollar-sign',
            'dolly',
            'dolly-flatbed',
            'donate',
            'door-closed',
            'door-open',
            'dot-circle',
            'dove',
            'download',
            'drafting-compass',
            'dragon',
            'draw-polygon',
            'drum',
            'drum-steelpan',
            'drumstick-bite',
            'dumbbell',
            'dumpster',
            'dumpster-fire',
            'dungeon',
            'edit',
            'egg',
            'eject',
            'ellipsis-h',
            'ellipsis-v',
            'envelope',
            'envelope-open',
            'envelope-open-text',
            'envelope-square',
            'equals',
            'eraser',
            'ethernet',
            'euro-sign',
            'exchange-alt',
            'exclamation',
            'exclamation-circle',
            'exclamation-triangle',
            'expand',
            'expand-alt',
            'expand-arrows-alt',
            'external-link-alt',
            'external-link-square-alt',
            'eye',
            'eye-dropper',
            'eye-slash',
            'fan',
            'fast-backward',
            'fast-forward',
            'faucet',
            'fax',
            'feather',
            'feather-alt',
            'female',
            'fighter-jet',
            'file',
            'file-alt',
            'file-archive',
            'file-audio',
            'file-code',
            'file-contract',
            'file-csv',
            'file-download',
            'file-excel',
            'file-export',
            'file-image',
            'file-import',
            'file-invoice',
            'file-invoice-dollar',
            'file-medical',
            'file-medical-alt',
            'file-pdf',
            'file-powerpoint',
            'file-prescription',
            'file-signature',
            'file-upload',
            'file-video',
            'file-word',
            'fill',
            'fill-drip',
            'film',
            'filter',
            'fingerprint',
            'fire',
            'fire-alt',
            'fire-extinguisher',
            'first-aid',
            'fish',
            'fist-raised',
            'flag',
            'flag-checkered',
            'flag-usa',
            'flask',
            'flushed',
            'folder',
            'folder-minus',
            'folder-open',
            'folder-plus',
            'font',
            'football-ball',
            'forward',
            'frog',
            'frown',
            'frown-open',
            'funnel-dollar',
            'futbol',
            'gamepad',
            'gas-pump',
            'gavel',
            'gem',
            'genderless',
            'ghost',
            'gift',
            'gifts',
            'glass-cheers',
            'glass-martini',
            'glass-martini-alt',
            'glass-whiskey',
            'glasses',
            'globe',
            'globe-africa',
            'globe-americas',
            'globe-asia',
            'globe-europe',
            'golf-ball',
            'gopuram',
            'graduation-cap',
            'greater-than',
            'greater-than-equal',
            'grimace',
            'grin',
            'grin-alt',
            'grin-beam',
            'grin-beam-sweat',
            'grin-hearts',
            'grin-squint',
            'grin-squint-tears',
            'grin-stars',
            'grin-tears',
            'grin-tongue',
            'grin-tongue-squint',
            'grin-tongue-wink',
            'grin-wink',
            'grip-horizontal',
            'grip-lines',
            'grip-lines-vertical',
            'grip-vertical',
            'guitar',
            'h-square',
            'hamburger',
            'hammer',
            'hamsa',
            'hand-holding',
            'hand-holding-heart',
            'hand-holding-medical',
            'hand-holding-usd',
            'hand-holding-water',
            'hand-lizard',
            'hand-middle-finger',
            'hand-paper',
            'hand-peace',
            'hand-point-down',
            'hand-point-left',
            'hand-point-right',
            'hand-point-up',
            'hand-pointer',
            'hand-rock',
            'hand-scissors',
            'hand-sparkles',
            'hand-spock',
            'hands',
            'hands-helping',
            'hands-wash',
            'handshake',
            'handshake-alt-slash',
            'handshake-slash',
            'hanukiah',
            'hard-hat',
            'hashtag',
            'hat-cowboy',
            'hat-cowboy-side',
            'hat-wizard',
            'hdd',
            'head-side-cough',
            'head-side-cough-slash',
            'head-side-mask',
            'head-side-virus',
            'heading',
            'headphones',
            'headphones-alt',
            'headset',
            'heart',
            'heart-broken',
            'heartbeat',
            'helicopter',
            'highlighter',
            'hiking',
            'hippo',
            'history',
            'hockey-puck',
            'holly-berry',
            'home',
            'horse',
            'horse-head',
            'hospital',
            'hospital-alt',
            'hospital-symbol',
            'hospital-user',
            'hot-tub',
            'hotdog',
            'hotel',
            'hourglass',
            'hourglass-end',
            'hourglass-half',
            'hourglass-start',
            'house-damage',
            'house-user',
            'hryvnia',
            'i-cursor',
            'ice-cream',
            'icicles',
            'icons',
            'id-badge',
            'id-card',
            'id-card-alt',
            'igloo',
            'image',
            'images',
            'inbox',
            'indent',
            'industry',
            'infinity',
            'info',
            'info-circle',
            'italic',
            'jedi',
            'joint',
            'journal-whills',
            'kaaba',
            'key',
            'keyboard',
            'khanda',
            'kiss',
            'kiss-beam',
            'kiss-wink-heart',
            'kiwi-bird',
            'landmark',
            'language',
            'laptop',
            'laptop-code',
            'laptop-house',
            'laptop-medical',
            'laugh',
            'laugh-beam',
            'laugh-squint',
            'laugh-wink',
            'layer-group',
            'leaf',
            'lemon',
            'less-than',
            'less-than-equal',
            'level-down-alt',
            'level-up-alt',
            'life-ring',
            'lightbulb',
            'link',
            'lira-sign',
            'list',
            'list-alt',
            'list-ol',
            'list-ul',
            'location-arrow',
            'lock',
            'lock-open',
            'long-arrow-alt-down',
            'long-arrow-alt-left',
            'long-arrow-alt-right',
            'long-arrow-alt-up',
            'low-vision',
            'luggage-cart',
            'lungs',
            'lungs-virus',
            'magic',
            'magnet',
            'mail-bulk',
            'male',
            'map',
            'map-marked',
            'map-marked-alt',
            'map-marker',
            'map-marker-alt',
            'map-pin',
            'map-signs',
            'marker',
            'mars',
            'mars-double',
            'mars-stroke',
            'mars-stroke-h',
            'mars-stroke-v',
            'mask',
            'medal',
            'medkit',
            'meh',
            'meh-blank',
            'meh-rolling-eyes',
            'memory',
            'menorah',
            'mercury',
            'meteor',
            'microchip',
            'microphone',
            'microphone-alt',
            'microphone-alt-slash',
            'microphone-slash',
            'microscope',
            'minus',
            'minus-circle',
            'minus-square',
            'mitten',
            'mobile',
            'mobile-alt',
            'money-bill',
            'money-bill-alt',
            'money-bill-wave',
            'money-bill-wave-alt',
            'money-check',
            'money-check-alt',
            'monument',
            'moon',
            'mortar-pestle',
            'mosque',
            'motorcycle',
            'mountain',
            'mouse',
            'mouse-pointer',
            'mug-hot',
            'music',
            'network-wired',
            'neuter',
            'newspaper',
            'not-equal',
            'notes-medical',
            'object-group',
            'object-ungroup',
            'oil-can',
            'om',
            'otter',
            'outdent',
            'pager',
            'paint-brush',
            'paint-roller',
            'palette',
            'pallet',
            'paper-plane',
            'paperclip',
            'parachute-box',
            'paragraph',
            'parking',
            'passport',
            'pastafarianism',
            'paste',
            'pause',
            'pause-circle',
            'paw',
            'peace',
            'pen',
            'pen-alt',
            'pen-fancy',
            'pen-nib',
            'pen-square',
            'pencil-alt',
            'pencil-ruler',
            'people-arrows',
            'people-carry',
            'pepper-hot',
            'percent',
            'percentage',
            'person-booth',
            'phone',
            'phone-alt',
            'phone-slash',
            'phone-square',
            'phone-square-alt',
            'phone-volume',
            'photo-video',
            'piggy-bank',
            'pills',
            'pizza-slice',
            'place-of-worship',
            'plane',
            'plane-arrival',
            'plane-departure',
            'plane-slash',
            'play',
            'play-circle',
            'plug',
            'plus',
            'plus-circle',
            'plus-square',
            'podcast',
            'poll',
            'poll-h',
            'poo',
            'poo-storm',
            'poop',
            'portrait',
            'pound-sign',
            'power-off',
            'pray',
            'praying-hands',
            'prescription',
            'prescription-bottle',
            'prescription-bottle-alt',
            'print',
            'procedures',
            'project-diagram',
            'pump-medical',
            'pump-soap',
            'puzzle-piece',
            'qrcode',
            'question',
            'question-circle',
            'quidditch',
            'quote-left',
            'quote-right',
            'quran',
            'radiation',
            'radiation-alt',
            'rainbow',
            'random',
            'receipt',
            'record-vinyl',
            'recycle',
            'redo',
            'redo-alt',
            'registered',
            'remove-format',
            'reply',
            'reply-all',
            'republican',
            'restroom',
            'retweet',
            'ribbon',
            'ring',
            'road',
            'robot',
            'rocket',
            'route',
            'rss',
            'rss-square',
            'ruble-sign',
            'ruler',
            'ruler-combined',
            'ruler-horizontal',
            'ruler-vertical',
            'running',
            'rupee-sign',
            'sad-cry',
            'sad-tear',
            'satellite',
            'satellite-dish',
            'save',
            'school',
            'screwdriver',
            'scroll',
            'sd-card',
            'search',
            'search-dollar',
            'search-location',
            'search-minus',
            'search-plus',
            'seedling',
            'server',
            'shapes',
            'share',
            'share-alt',
            'share-alt-square',
            'share-square',
            'shekel-sign',
            'shield-alt',
            'shield-virus',
            'ship',
            'shipping-fast',
            'shoe-prints',
            'shopping-bag',
            'shopping-basket',
            'shopping-cart',
            'shower',
            'shuttle-van',
            'sign',
            'sign-in-alt',
            'sign-language',
            'sign-out-alt',
            'signal',
            'signature',
            'sim-card',
            'sink',
            'sitemap',
            'skating',
            'skiing',
            'skiing-nordic',
            'skull',
            'skull-crossbones',
            'slash',
            'sleigh',
            'sliders-h',
            'smile',
            'smile-beam',
            'smile-wink',
            'smog',
            'smoking',
            'smoking-ban',
            'sms',
            'snowboarding',
            'snowflake',
            'snowman',
            'snowplow',
            'soap',
            'socks',
            'solar-panel',
            'sort',
            'sort-alpha-down',
            'sort-alpha-down-alt',
            'sort-alpha-up',
            'sort-alpha-up-alt',
            'sort-amount-down',
            'sort-amount-down-alt',
            'sort-amount-up',
            'sort-amount-up-alt',
            'sort-down',
            'sort-numeric-down',
            'sort-numeric-down-alt',
            'sort-numeric-up',
            'sort-numeric-up-alt',
            'sort-up',
            'spa',
            'space-shuttle',
            'spell-check',
            'spider',
            'spinner',
            'splotch',
            'spray-can',
            'square',
            'square-full',
            'square-root-alt',
            'stamp',
            'star',
            'star-and-crescent',
            'star-half',
            'star-half-alt',
            'star-of-david',
            'star-of-life',
            'step-backward',
            'step-forward',
            'stethoscope',
            'sticky-note',
            'stop',
            'stop-circle',
            'stopwatch',
            'stopwatch-20',
            'store',
            'store-alt',
            'store-alt-slash',
            'store-slash',
            'stream',
            'street-view',
            'strikethrough',
            'stroopwafel',
            'subscript',
            'subway',
            'suitcase',
            'suitcase-rolling',
            'sun',
            'superscript',
            'surprise',
            'swatchbook',
            'swimmer',
            'swimming-pool',
            'synagogue',
            'sync',
            'sync-alt',
            'syringe',
            'table',
            'table-tennis',
            'tablet',
            'tablet-alt',
            'tablets',
            'tachometer-alt',
            'tag',
            'tags',
            'tape',
            'tasks',
            'taxi',
            'teeth',
            'teeth-open',
            'temperature-high',
            'temperature-low',
            'tenge',
            'terminal',
            'text-height',
            'text-width',
            'th',
            'th-large',
            'th-list',
            'theater-masks',
            'thermometer',
            'thermometer-empty',
            'thermometer-full',
            'thermometer-half',
            'thermometer-quarter',
            'thermometer-three-quarters',
            'thumbs-down',
            'thumbs-up',
            'thumbtack',
            'ticket-alt',
            'times',
            'times-circle',
            'tint',
            'tint-slash',
            'tired',
            'toggle-off',
            'toggle-on',
            'toilet',
            'toilet-paper',
            'toilet-paper-slash',
            'toolbox',
            'tools',
            'tooth',
            'torah',
            'torii-gate',
            'tractor',
            'trademark',
            'traffic-light',
            'trailer',
            'train',
            'tram',
            'transgender',
            'transgender-alt',
            'trash',
            'trash-alt',
            'trash-restore',
            'trash-restore-alt',
            'tree',
            'trophy',
            'truck',
            'truck-loading',
            'truck-monster',
            'truck-moving',
            'truck-pickup',
            'tshirt',
            'tty',
            'tv',
            'umbrella',
            'umbrella-beach',
            'underline',
            'undo',
            'undo-alt',
            'universal-access',
            'university',
            'unlink',
            'unlock',
            'unlock-alt',
            'upload',
            'user',
            'user-alt',
            'user-alt-slash',
            'user-astronaut',
            'user-check',
            'user-circle',
            'user-clock',
            'user-cog',
            'user-edit',
            'user-friends',
            'user-graduate',
            'user-injured',
            'user-lock',
            'user-md',
            'user-minus',
            'user-ninja',
            'user-nurse',
            'user-plus',
            'user-secret',
            'user-shield',
            'user-slash',
            'user-tag',
            'user-tie',
            'user-times',
            'users',
            'users-cog',
            'users-slash',
            'utensil-spoon',
            'utensils',
            'vector-square',
            'venus',
            'venus-double',
            'venus-mars',
            'vest',
            'vest-patches',
            'vial',
            'vials',
            'video',
            'video-slash',
            'vihara',
            'virus',
            'virus-slash',
            'viruses',
            'voicemail',
            'volleyball-ball',
            'volume-down',
            'volume-mute',
            'volume-off',
            'volume-up',
            'vote-yea',
            'vr-cardboard',
            'walking',
            'wallet',
            'warehouse',
            'water',
            'wave-square',
            'weight',
            'weight-hanging',
            'wheelchair',
            'wifi',
            'wind',
            'window-close',
            'window-maximize',
            'window-minimize',
            'window-restore',
            'wine-bottle',
            'wine-glass',
            'wine-glass-alt',
            'won-sign',
            'wrench',
            'x-ray',
            'yen-sign',
            'yin-yang',

            /* Brands icons list, https://fontawesome.com/v5/cheatsheet/free/brands
             * Brands Style (fab), use brands style in the plugin icons parameter
             *
             */
            '500px',
            'accessible-icon',
            'accusoft',
            'acquisitions-incorporated',
            'adn',
            'adversal',
            'affiliatetheme',
            'airbnb',
            'algolia',
            'alipay',
            'amazon',
            'amazon-pay',
            'amilia',
            'android',
            'angellist',
            'angrycreative',
            'angular',
            'app-store',
            'app-store-ios',
            'apper',
            'apple',
            'apple-pay',
            'artstation',
            'asymmetrik',
            'atlassian',
            'audible',
            'autoprefixer',
            'avianex',
            'aviato',
            'aws',
            'bandcamp',
            'battle-net',
            'behance',
            'behance-square',
            'bimobject',
            'bitbucket',
            'bitcoin',
            'bity',
            'black-tie',
            'blackberry',
            'blogger',
            'blogger-b',
            'bluetooth',
            'bluetooth-b',
            'bootstrap',
            'btc',
            'buffer',
            'buromobelexperte',
            'buy-n-large',
            'buysellads',
            'canadian-maple-leaf',
            'cc-amazon-pay',
            'cc-amex',
            'cc-apple-pay',
            'cc-diners-club',
            'cc-discover',
            'cc-jcb',
            'cc-mastercard',
            'cc-paypal',
            'cc-stripe',
            'cc-visa',
            'centercode',
            'centos',
            'chrome',
            'chromecast',
            'cloudflare',
            'cloudscale',
            'cloudsmith',
            'cloudversify',
            'codepen',
            'codiepie',
            'confluence',
            'connectdevelop',
            'contao',
            'cotton-bureau',
            'cpanel',
            'creative-commons',
            'creative-commons-by',
            'creative-commons-nc',
            'creative-commons-nc-eu',
            'creative-commons-nc-jp',
            'creative-commons-nd',
            'creative-commons-pd',
            'creative-commons-pd-alt',
            'creative-commons-remix',
            'creative-commons-sa',
            'creative-commons-sampling',
            'creative-commons-sampling-plus',
            'creative-commons-share',
            'creative-commons-zero',
            'critical-role',
            'css3',
            'css3-alt',
            'cuttlefish',
            'd-and-d',
            'd-and-d-beyond',
            'dailymotion',
            'dashcube',
            'deezer',
            'delicious',
            'deploydog',
            'deskpro',
            'dev',
            'deviantart',
            'dhl',
            'diaspora',
            'digg',
            'digital-ocean',
            'discord',
            'discourse',
            'dochub',
            'docker',
            'draft2digital',
            'dribbble',
            'dribbble-square',
            'dropbox',
            'drupal',
            'dyalog',
            'earlybirds',
            'ebay',
            'edge',
            'edge-legacy',
            'elementor',
            'ello',
            'ember',
            'empire',
            'envira',
            'erlang',
            'ethereum',
            'etsy',
            'evernote',
            'expeditedssl',
            'facebook',
            'facebook-f',
            'facebook-messenger',
            'facebook-square',
            'fantasy-flight-games',
            'fedex',
            'fedora',
            'figma',
            'firefox',
            'firefox-browser',
            'first-order',
            'first-order-alt',
            'firstdraft',
            'flickr',
            'flipboard',
            'fly',
            'font-awesome',
            'font-awesome-alt',
            'font-awesome-flag',
            'fonticons',
            'fonticons-fi',
            'fort-awesome',
            'fort-awesome-alt',
            'forumbee',
            'foursquare',
            'free-code-camp',
            'freebsd',
            'fulcrum',
            'galactic-republic',
            'galactic-senate',
            'get-pocket',
            'gg',
            'gg-circle',
            'git',
            'git-alt',
            'git-square',
            'github',
            'github-alt',
            'github-square',
            'gitkraken',
            'gitlab',
            'gitter',
            'glide',
            'glide-g',
            'gofore',
            'goodreads',
            'goodreads-g',
            'google',
            'google-drive',
            'google-pay',
            'google-play',
            'google-plus',
            'google-plus-g',
            'google-plus-square',
            'google-wallet',
            'gratipay',
            'grav',
            'gripfire',
            'grunt',
            'guilded',
            'gulp',
            'hacker-news',
            'hacker-news-square',
            'hackerrank',
            'hips',
            'hire-a-helper',
            'hive',
            'hooli',
            'hornbill',
            'hotjar',
            'houzz',
            'html5',
            'hubspot',
            'ideal',
            'imdb',
            'innosoft',
            'instagram',
            'instagram-square',
            'instalod',
            'intercom',
            'internet-explorer',
            'invision',
            'ioxhost',
            'itch-io',
            'itunes',
            'itunes-note',
            'java',
            'jedi-order',
            'jenkins',
            'jira',
            'joget',
            'joomla',
            'js',
            'js-square',
            'jsfiddle',
            'kaggle',
            'keybase',
            'keycdn',
            'kickstarter',
            'kickstarter-k',
            'korvue',
            'laravel',
            'lastfm',
            'lastfm-square',
            'leanpub',
            'less',
            'line',
            'linkedin',
            'linkedin-in',
            'linode',
            'linux',
            'lyft',
            'magento',
            'mailchimp',
            'mandalorian',
            'markdown',
            'mastodon',
            'maxcdn',
            'mdb',
            'medapps',
            'medium',
            'medium-m',
            'medrt',
            'meetup',
            'megaport',
            'mendeley',
            'microblog',
            'microsoft',
            'mix',
            'mixcloud',
            'mixer',
            'mizuni',
            'modx',
            'monero',
            'napster',
            'neos',
            'nimblr',
            'node',
            'node-js',
            'npm',
            'ns8',
            'nutritionix',
            'octopus-deploy',
            'odnoklassniki',
            'odnoklassniki-square',
            'old-republic',
            'opencart',
            'openid',
            'opera',
            'optin-monster',
            'orcid',
            'osi',
            'page4',
            'pagelines',
            'palfed',
            'patreon',
            'paypal',
            'penny-arcade',
            'perbyte',
            'periscope',
            'phabricator',
            'phoenix-framework',
            'phoenix-squadron',
            'php',
            'pied-piper',
            'pied-piper-alt',
            'pied-piper-hat',
            'pied-piper-pp',
            'pied-piper-square',
            'pinterest',
            'pinterest-p',
            'pinterest-square',
            'playstation',
            'product-hunt',
            'pushed',
            'python',
            'qq',
            'quinscape',
            'quora',
            'r-project',
            'raspberry-pi',
            'ravelry',
            'react',
            'reacteurope',
            'readme',
            'rebel',
            'red-river',
            'reddit',
            'reddit-alien',
            'reddit-square',
            'redhat',
            'renren',
            'replyd',
            'researchgate',
            'resolving',
            'rev',
            'rocketchat',
            'rockrms',
            'rust',
            'safari',
            'salesforce',
            'sass',
            'schlix',
            'scribd',
            'searchengin',
            'sellcast',
            'sellsy',
            'servicestack',
            'shirtsinbulk',
            'shopify',
            'shopware',
            'simplybuilt',
            'sistrix',
            'sith',
            'sketch',
            'skyatlas',
            'skype',
            'slack',
            'slack-hash',
            'slideshare',
            'snapchat',
            'snapchat-ghost',
            'snapchat-square',
            'soundcloud',
            'sourcetree',
            'speakap',
            'speaker-deck',
            'spotify',
            'squarespace',
            'stack-exchange',
            'stack-overflow',
            'stackpath',
            'staylinked',
            'steam',
            'steam-square',
            'steam-symbol',
            'sticker-mule',
            'strava',
            'stripe',
            'stripe-s',
            'studiovinari',
            'stumbleupon',
            'stumbleupon-circle',
            'superpowers',
            'supple',
            'suse',
            'swift',
            'symfony',
            'teamspeak',
            'telegram',
            'telegram-plane',
            'tencent-weibo',
            'the-red-yeti',
            'themeco',
            'themeisle',
            'think-peaks',
            'tiktok',
            'trade-federation',
            'trello',
            'tumblr',
            'tumblr-square',
            'twitch',
            'twitter',
            'twitter-square',
            'typo3',
            'uber',
            'ubuntu',
            'uikit',
            'umbraco',
            'uncharted',
            'uniregistry',
            'unity',
            'unsplash',
            'untappd',
            'ups',
            'usb',
            'usps',
            'ussunnah',
            'vaadin',
            'viacoin',
            'viadeo',
            'viadeo-square',
            'viber',
            'vimeo',
            'vimeo-square',
            'vimeo-v',
            'vine',
            'vk',
            'vnv',
            'vuejs',
            'watchman-monitoring',
            'waze',
            'weebly',
            'weibo',
            'weixin',
            'whatsapp',
            'whatsapp-square',
            'whmcs',
            'wikipedia-w',
            'windows',
            'wix',
            'wizards-of-the-coast',
            'wodu',
            'wolf-pack-battalion',
            'wordpress',
            'wordpress-simple',
            'wpbeginner',
            'wpexplorer',
            'wpforms',
            'wpressr',
            'xbox',
            'xing',
            'xing-square',
            'y-combinator',
            'yahoo',
            'yammer',
            'yandex',
            'yandex-international',
            'yarn',
            'yelp',
            'yoast',
            'youtube',
            'youtube-square',
            'zhihu'
        ],
    ];
}
