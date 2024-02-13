<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

function wikiplugin_mediaplayer_info()
{
    return [
        'name' => tra('Media Player'),
        'documentation' => 'PluginMediaplayer',
        'description' => tra('Add a media player to a page'),
        'extraparams' => true,
        'prefs' => [ 'wikiplugin_mediaplayer' ],
        'iconname' => 'play',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            // The following param needs an URL with an extension (ex.: example.wmv works but not tiki-download_file.php?fileId=4&display)
            'src' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra("Complete URL to the media to include, which has the appropriate extension.
                    If your URL doesn't have an extension, use the File type parameter below."),
                'since' => '6.0',
                'accepted' => 'asx, asf, avi, mov, mpg, mpeg, mp4, qt, ra, smil, wmv, 3g2, 3gp, aif, aac, au, gsm,
                    mid, midi, mov, m4a, snd, ra, ram, rm, wav, wma, bmp, html, pdf, psd, qif, qtif, qti, tif, tiff,
                    xaml',
                'filter' => 'url',
                'default' => '',
            ],

            // The type parameter is verified for Quicktime, Windows Media Player, Real Player, iframe (PDF)
            'type' => [
                'required' => false,
                'name' => tra('File type'),
                'description' => tr('File type for source URL, e.g. %0mp4%1, %0pdf%1 or %0odp%1. Specify one of the supported file types when
                    the URL of the file is missing the file extension. This is the case for File Gallery files which
                    have a URL such as %0tiki-download_file.php?fileId=4&display%1 or %0display4%1 if you have Clean URLs
                    enabled.', '<code>', '</code>'),
                'since' => '10.0',
                'filter' => 'url',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Player width in px or %'),
                'since' => '10.0',
                'default' => '',
                ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                    'description' => tra('Player height in px or %'),
                'since' => '10.0',
                'default' => '',
                ],
            'style' => [
                'required' => false,
                'name' => tra('Style'),
                'description' => tra('Set the style'),
                'since' => '3.0',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Native Video (HTML5)'), 'value' => 'native'],
                ],
            ],
            'mediatype' => [
                'required' => false,
                'name' => tra('Media Type'),
                'description' => tra('Media type for HTML5'),
                'since' => '13.2',
                'filter' => 'word',
                'options' => [
                    [
                        'text' => '', 'value' => ''
                    ],
                    [
                        'text' => tra('Audio'), 'value' => 'audio'
                    ],
                    [
                        'text' => tra('Video'), 'value' => 'video'
                    ]
                ]
            ],
        ],
    ];
}
function wikiplugin_mediaplayer($data, $params)
{
    global $prefs;
    $access = TikiLib::lib('access');
    static $iMEDIAPLAYER = 0;
    $id = 'mediaplayer' . ++$iMEDIAPLAYER;
    $params['type'] = strtolower(isset($params['type']) ? $params['type'] : '');

    if (empty($params['src'])) {
        return '';
    }
    //checking if pdf generation request
    if (in_array($params['type'], ['pdf']) && isset($_GET['display']) && strstr($_GET['display'], 'pdf') != '') {
        return "<pdfpage>.<pdfinclude src='" . TikiLib::lib('access')->absoluteUrl($params['src']) . "' /></pdfpage>";
    }
    $defaults_html5 = [
        'width' => '',
        'height' => '',
    ];
    $defaults = [
        'width' => 320,
        'height' => 240,
    ];
    if (preg_match('/webm/', $params['type']) > 0 && $params['type'] != 'video/webm') {
        $params['type'] = 'video/webm';
    }
    if ($params['type'] == 'video/webm') {
        $params['style'] = 'native';
    }

    if (empty($params['type'])) {
        preg_match('/(?:dl|display|fileId=)(\d*)/', $params['src'], $matches);
        if (! empty($matches[1])) { // fileId 0 is also invalid
            $fileId = $matches[1];
            $filegallib = TikiLib::lib('filegal');
            $file = $filegallib->get_file_info($fileId);
            if (! empty($file['filetype']) && $file['fileId'] == $fileId) {
                $fileExtension = pathinfo($file['filename'], PATHINFO_EXTENSION);
                $params['type'] = $fileExtension;
                if (! in_array($fileExtension, ['pdf', 'odt', 'ods', 'odp'])) {
                    $params['style'] = ! empty($params['style']) ? $params['style'] : 'native';
                    $params['type'] = $file['filetype'];
                }
            }
        }
    }
    if (! empty($params['style']) && $params['style'] == 'native') {
        $params = array_merge($defaults_html5, $params);
    } else {
        $params = array_merge($defaults, $params);
    }
    if (! empty($params['src']) && (empty($params['style']) || $params['style'] != 'native')) {
        $headerlib = TikiLib::lib('header');
        $js = "\n var media_$id = $('#$id').media( {";
        foreach ($params as $param => $value) {
            if ($param == 'src') {
                continue;
            }

            if (
                is_numeric($value) == false &&
                strtolower($value) != 'true' &&
                strtolower($value) != 'false'
            ) {
                $value = "\"" . $value . "\"";
            }

            $js .= "$param: $value,";
        }
        // Force scaling (keeping the aspect ratio) of the QuickTime player
        //  Tried with .mp4. Not sure how this will work with other formats, not using QuickTime.
        // See: http://jquery.malsup.com/media/#players for default players for different formats. arildb
        $js .= " params: { 
                scale: 'aspect'
                } 
            } );";

        if (in_array($params['type'], ['pdf', 'odt', 'ods', 'odp'])) {
            if ($prefs['fgal_pdfjs_feature'] === 'n') {
                return "<p>" . tr('PDF.js feature is disabled. If you do not have permission to enable, ask the site administrator.') . "</p>";
            }
            if ($prefs['fgal_pdfjs_feature'] === 'y') {
                $smarty = TikiLib::lib('smarty');

                $url = TikiLib::lib('access')->absoluteUrl($params['src']);
                $smarty->assign('url', $url);
                $smarty->assign('mediaplayerId', $iMEDIAPLAYER);
                $oldPdfJsFile = VendorHelper::getAvailableVendorPath('pdfjs', '/npm-asset/pdfjs-dist/build/pdf.js');
                $oldPdfJsFileAvailable = file_exists($oldPdfJsFile);
                $smarty->assign('oldPdfJsFileAvailable', $oldPdfJsFileAvailable);

                $pdfJsfile = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/build/pdf.js');
                $pdfJsAvailable = file_exists($pdfJsfile);
                $smarty->assign('pdfJsAvailable', $pdfJsAvailable);

                $headerlib = TikiLib::lib('header');
                $headerlib->add_css("
                    .iframe-container {
                        overflow: hidden;
                        padding-top: 56.25%;
                        position: relative;
                        height: 900px;
                    }
                    
                    .iframe-container iframe {
                        border: 0;
                        height: 100%;
                        left: 0;
                        position: absolute;
                        top: 0;
                        width: 100%;
                    }
                    
                    @media (max-width: 767px) {
                        .iframe-container {
                            height: 500px;
                        } 
                    }
                    
                    @media (min-width: 768px) AND (max-width: 991px) {
                        .iframe-container {
                            height: 600px;
                        }
                    }
                    
                    @media (min-width: 992px) AND (max-width: 1209px){
                        .iframe-container {
                            height: 700px;
                        }
                    }
                ");

                $fileId = '';
                $sourceLink = '';

                $parts = parse_url($params['src'], PHP_URL_QUERY);
                if ($parts) {
                    parse_str($parts, $query);
                    if (! empty($query['fileId'])) {
                        $fileId = $query['fileId'];
                    }
                } else {
                    preg_match('/(display|dl)(.*)$/', $params['src'], $matches);
                    if (! empty($matches[2])) {
                        $fileId = $matches[2];
                    }
                }

                if (! empty($fileId)) {
                    $sourceLink = smarty_modifier_sefurl($fileId, 'display');
                } else {
                    global $base_url;
                    $sourceLink = TikiLib::lib('access')->absoluteUrl($params['src']);

                    // Not an internal link, lets set a security token.
                    if (strrpos($sourceLink, $base_url) === false) {
                        $data = Tiki_Security::get()->encode(['url' => $params['src']]);
                        $sourceLink = TikiLib::tikiUrl('tiki-download_file.php', [
                            'data'   => $data,
                        ]);
                    }
                }

                if (! empty($sourceLink)) {
                    $htmlViewFile = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/web/viewer.html') . '?file=';
                    $sourceLink = $htmlViewFile . urlencode(TikiLib::lib('access')->absoluteUrl($sourceLink));
                }

                $smarty->assign('source_link', $sourceLink);
                return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_mediaplayer_pdfjs.tpl') . '~/np~';
            } elseif ($params['type'] === 'pdf') {
                $js = '
var found = false;
$.each(navigator.plugins, function(i, plugins) { // navigator.plugins is unspecified according to https://developer.mozilla.org/fr/docs/Web/API/NavigatorPlugins/plugins . Something other in NavigatorPlugins may be standard. 
    $.each(plugins, function(i, plugin) {
        if (plugin.type === "application/pdf") {
            found = true;
            return;
        }
    });
});
if (!found) {
    // IE doesnt bother using the plugins array (sometimes?), plus ActiveXObject is hidden now so just try and catch... :(
    try {
        var oAcro7 = new ActiveXObject("AcroPDF.PDF.1");
        if (oAcro7) {
            found = true;
        }
    } catch (e) {
    }
}
if (found) {
    ' . $js . '
} else {
    // no pdf plugin
    $("#' . $id . '").text(tr("Download file:") + " " + "' . $params['src'] . '");
}';
            }
        }

        $headerlib->add_jq_onready($js);

        return "<a href=\"" . $params['src'] . "\" id=\"$id\"></a>";
    }

    // Check the style of the player
    if (empty($params['style'])) {
        $player = $params['player'];
    } elseif ($params['style'] == 'native') {
        $player = '';
    }

    // check if native native HTML5 video object is requested

    if ($params['style'] == 'native') {
        if (! empty($params['mediatype']) && $params['mediatype'] == 'audio') {
            $mediatype = 'audio';
        } else {
            $mediatype = 'video';
        }
        $code = '<' . $mediatype;
        if (! empty($params['height'])) {
            $code .= ' height="' . $params['height'] . '"';
        }
        if (! empty($params['width'])) {
            $code .= ' width="' . $params['width'] . '"';
        }
        $code .= ' style="max-width: 100%" controls>';
        $code .= '    <source src="' . $params['src'] . '" type=\'' . $params['type'] . '\'>'; // type can be e.g. 'video/webm; codecs="vp8, vorbis"'
        $code .= '</' . $mediatype . '>';
    }

    return "~np~$code~/np~";
}
