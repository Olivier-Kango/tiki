<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_youtube_info()
{
    return [
        'name' => tra('YouTube'),
        'documentation' => 'PluginYouTube',
        'description' => tra('Embed a YouTube video in a page'),
        'prefs' => [ 'wikiplugin_youtube' ],
        'iconname' => 'youtube',
        'introduced' => 2,
        'tags' => [ 'basic' ],
        'params' => [
            'movie' => [
                'required' => true,
                'name' => tra('Movie'),
                'description' => tr('Complete URL to the YouTube video or last part (after %0www.youtube.com/v/%1 and
                    before the first question mark)', '<code>', '</code>'),
                'since' => '2.0',
                'filter' => 'url',
            ],
            'privacyEnhanced' => [
                'required' => false,
                'name' => tra('Privacy-Enhanced'),
                'description' => tra('Enable privacy-enhanced mode'),
                'default' => '',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels (e.g., 560). Leave empty to use responsive behavior.'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels (e.g., 315). Leave empty to use responsive behavior.'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start time'),
                'description' => tra('Start time offset in seconds'),
                'filter' => 'digits',
                'default' => 0,
            ],
            'quality' => [
                'required' => false,
                'name' => tra('Quality'),
                'description' => tr('Quality of the video. Default is %0high%1.', '<code>', '</code>'),
                'since' => '2.0',
                'default' => 'high',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('High'), 'value' => 'high'],
                    ['text' => tra('Medium'), 'value' => 'medium'],
                    ['text' => tra('Low'), 'value' => 'low'],
                ],
                'advanced' => true
            ],
            'allowFullScreen' => [
                'required' => false,
                'name' => tra('Allow full-screen'),
                'description' => tra('Enlarge video to full screen size'),
                'since' => '5.0',
                'default' => '',
                'filter' => 'alpha',
                 'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                 ],
                 'advanced' => true
            ],
            'related' => [
                'required' => false,
                'name' => tra('Related'),
                'description' => tra('Show related videos (shown by default)'),
                'since' => '6.1',
                'default' => '',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true
            ],
            'background' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Toolbar background color. Use an HTML color code.') . ' ' . tra('Example')
                    . ': <code>ffffff</code>',
                'accepted' => tra('HTML color code, e.g. ffffff'),
                'since' => '6.1',
                'filter' => 'text',
                'default' => '',
                'advanced' => true
            ],
            'border' => [
                'required' => false,
                'name' => tra('Borders'),
                'description' => tra('Toolbar border colors. Use an HTML color code.') . ' ' . tra('Example')
                    . ': <code>ffffff</code>',
                'accepted' => tra('HTML color code, e.g. ffffff'),
                'since' => '6.1',
                'filter' => 'text',
                'default' => '',
                'advanced' => true
            ],
            'borderRadius' => [
                'required' => false,
                'name' => tra('Border Radius'),
                'description' => tra('Apply rounded corners to the container. Default: ') . '<code>Yes</code>',
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true
            ],
        ],
    ];
}

function wikiplugin_youtube($data, $params)
{
    global $tikilib;

    $sYoutubeId  = getYoutubeId($params['movie']);
    if (empty($sYoutubeId)) {
        Feedback::error(tra('Invalid YouTube URL provided'));
        return '';
    }

    if ($params['privacyEnhanced'] == 'y') {
        $fqdn = 'www.youtube-nocookie.com';
    } else {
        $fqdn = 'www.youtube.com';
    }

    $src = '//' . $fqdn . '/embed/' . $sYoutubeId . '?';
    // backward compatibility
    if ($params['allowFullScreen'] == 'y') {
        $params['allowFullScreen'] = 'true';
    } elseif ($params['allowFullScreen'] == 'n') {
        $params['allowFullScreen'] = 'false';
    }

    if (! empty($params['allowFullScreen'])) {
        if ($params['allowFullScreen'] == 'true') {
            $src .= '&fs=1';
        } else {
            $src .= '&fs=0';
        }
    }
    if (! empty($params['start'])) {
        $src .= '&start=' . $params['start'];
    }
    if ($params['related'] == 'n') {
        $params['movie'] .= '&rel=0';
    }
    if (! empty($params['border'])) {
        $src .= '&color1=0x' . $params['border'];
    }
    if (! empty($params['background'])) {
        $src .= '&color2=0x' . $params['background'];
    }

    $containerStyles = [
        'position' => 'relative',
        'overflow' => 'hidden',
        'max-width' => '100%',
        'max-height' => '75vh', // keep a reasonable max-height to avoid huge embeds
    ];

    $useResponsive = true;
    $aspectRatio = null;

    // compute aspect-ratio from provided width/height (use "W/H" string form)
    if (! empty($params['width']) && is_numeric($params['width']) && $params['width'] > 0 &&
        ! empty($params['height']) && is_numeric($params['height']) && $params['height'] > 0
    ) {
        $aspectRatio = intval($params['width']) . '/' . intval($params['height']);
    }

    // If user provided only width or height, prefer to respect explicit sizing (non responsive container)
    if (! empty($params['width']) && is_numeric($params['width']) && $params['width'] > 0 &&
        (empty($params['height']) || ! is_numeric($params['height']) || $params['height'] <= 0)) {
        $useResponsive = false;
        $containerStyles['width'] = intval($params['width']) . 'px';
        $containerStyles['margin'] = '0 auto';
        // if height present and numeric, set it
        if (! empty($params['height']) && is_numeric($params['height']) && $params['height'] > 0) {
            $containerStyles['height'] = intval($params['height']) . 'px';
        } elseif ($aspectRatio) {
            $containerStyles['aspect-ratio'] = $aspectRatio;
        }
    } elseif (! empty($params['height']) && is_numeric($params['height']) && $params['height'] > 0 &&
        (empty($params['width']) || ! is_numeric($params['width']) || $params['width'] <= 0)) {
        $useResponsive = false;
        $containerStyles['height'] = intval($params['height']) . 'px';
        if ($aspectRatio) {
            $containerStyles['width'] = (int)(intval($params['height']) * (intval($params['width']) / intval($params['height']))) . 'px';
            $containerStyles['margin'] = '0 auto';
        }
    }

    if ($useResponsive) {
        $containerStyles['width'] = '100%';
        if ($aspectRatio) {
            $containerStyles['aspect-ratio'] = $aspectRatio;
        } else {
            $containerStyles['aspect-ratio'] = '16/9'; // default fallback to 16:9 for YouTube videos
        }
    }

    if (isset($params['borderRadius']) && $params['borderRadius'] === 'y') {
        $containerStyles['border-radius'] = '8px';
    }

    if (! empty($params['background'])) {
        // allow either hex without # or with # — normalize to use as CSS value
        $bg = $params['background'];
        if (! preg_match('/^\s*#/', $bg)) {
            // if user passed 'ffffff' convert to #ffffff for CSS
            if (preg_match('/^[0-9a-fA-F]{3,6}$/', $bg)) {
                $bg = '#' . $bg;
            }
        }
        $containerStyles['background-color'] = $bg;
    }

    if (! empty($params['border'])) {
        $borderVal = $params['border'];
        if (! preg_match('/^\s*#/', $borderVal) && preg_match('/^[0-9a-fA-F]{3,6}$/', $borderVal)) {
            $borderVal = '#' . $borderVal;
        }
        $containerStyles['border'] = '1px solid ' . $borderVal;
    }

    $iframeStyles = $useResponsive
        ? ['position' => 'absolute', 'top' => '0', 'left' => '0', 'width' => '100%', 'height' => '100%']
        : ['width' => '100%', 'height' => '100%'];

    // Build inline style strings
    $containerStyle = implode(';', array_map(function ($key, $value) {
        return "$key:$value";
    }, array_keys($containerStyles), $containerStyles));

    $iframeStyle = implode(';', array_map(function ($key, $value) {
        return "$key:$value";
    }, array_keys($iframeStyles), $iframeStyles));

    // --- Build DOM using DOMDocument (same pattern as wikiplugin_oembed)
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    $div = $dom->createElement('div');
    $div->setAttribute('style', $containerStyle);

    $newIframe = $dom->createElement('iframe');
    $newIframe->setAttribute('src', $src);
    $newIframe->setAttribute('frameborder', '0');
    $newIframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
    $newIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');
    $newIframe->setAttribute('title', tra('Embedded media content'));

    if (! empty($params['allowFullScreen']) && $params['allowFullScreen'] === 'true') {
        $newIframe->setAttribute('allowfullscreen', '');
    }

    $newIframe->setAttribute('style', $iframeStyle);

    $div->appendChild($newIframe);
    $dom->appendChild($div);

    $html = $dom->saveHTML($div);
    libxml_clear_errors();

    return '~np~' . $html . '~/np~';
}

function getYoutubeId($sYoutubeUrl)
{
    $aParsedUrl = parse_url($sYoutubeUrl);
    if ($aParsedUrl !== false && ! empty($aParsedUrl['host'])) {
        if (
            $aParsedUrl['host'] !== 'youtube.com'
            && $aParsedUrl['host'] !== 'www.youtube.com'
            && $aParsedUrl['host'] !== 'youtu.be'
            && $aParsedUrl['host'] !== 'www.youtu.be'
        ) {
            return false;
        }
        if ($aParsedUrl['host'] === 'youtu.be') {
            $sYoutubeId = str_replace('/', '', $aParsedUrl['path']);
            return $sYoutubeId;
        }
        if ($aParsedUrl['host'] === 'youtube.com' || $aParsedUrl['host'] === 'www.youtube.com') {
            parse_str(parse_url($sYoutubeUrl, PHP_URL_QUERY), $aQueryString);
            return $aQueryString["v"];
        }
    } elseif (preg_match('/^([\w\-_]+)$/', $sYoutubeUrl, $matches)) {
        $sYoutubeId = $sYoutubeUrl;
    } else {
        return false;
    }
    return $sYoutubeId;
}
