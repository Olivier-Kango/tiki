<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

class SlideshowHelper
{
    protected const REVEAL_PARAMS = [
        'parallaxBackgroundImage',
        'parallaxBackgroundSize',
        'parallaxBackgroundHorizontal',
        'parallaxBackgroundVertical',
        'slideSeconds',
        'transition',
        'transitionSpeed',
        'backgroundTransition',
        'controls',
        'controlsLayout',
        'controlsBackArrows',
        'progress',
        'slideNumber',
        'autoSlide',
        'autoSlideStoppable'
    ];

    protected const REVEAL_STATIC_SETTINGS = [
        "viewDistance"  => 3,
        "display"       => "block",
        "hash"          => "true"
    ];

    public static function getRevealSettingsAsString($params): string
    {
        $revealSettings = '';
        $pluginInfo = wikiplugin_slideshow_info();

        self::addStaticParameters($params);
        // Parse all parameters that are not digits based on plugin parameter definition
        foreach (self::REVEAL_PARAMS as $revealParam) {
            if (! isset($params[$revealParam])) {
                continue;
            }

            $params[$revealParam] = (string) $params[$revealParam];

            if (self::isDigitParameter($pluginInfo, $revealParam)) {
                $params[$revealParam] = (int) $params[$revealParam];
            }
        }

        foreach ($params as $parameterKey => $parameterValue) {
            if (is_null($parameterValue) || ! strlen($parameterValue)) {
                continue;
            }

            if (strlen($revealSettings) > 0) {
                $revealSettings .= ', ';
            }

            $value = $parameterValue;

            if (is_string($value)) {
                $value = "'$value'";
            }

            $revealSettings .= "$parameterKey:$value";
        }

        self::parseRevealSettings($revealSettings);

        return $revealSettings;
    }

    public static function getDefaultPluginValues(): array
    {
        $defaultParameters = [];
        $pluginInfo = wikiplugin_slideshow_info();

        foreach ($pluginInfo['params'] as $parameterName => $parameterOptions) {
            $defaultParameters[$parameterName] = $parameterOptions['default'];
        }

        return $defaultParameters;
    }

    private static function isDigitParameter($pluginInfo, $parameterName): bool
    {
        return ! empty($pluginInfo['params'][$parameterName]['filter'])
            && $pluginInfo['params'][$parameterName]['filter'] === 'digits';
    }

    /**
     * Parse plugin values into reveal related values
     * @param $settings
     * @return void
     */
    private static function parseRevealSettings(&$settings)
    {
        if (! empty($settings)) {
            $settings = str_replace(["'y'", "'n'"], ["true", "false"], $settings);
        }
    }

    /**
     * Add static setting values to the
     * @param $params
     */
    private static function addStaticParameters(&$params)
    {
        foreach (self::REVEAL_STATIC_SETTINGS as $key => $value) {
            $params[$key] = $value;
        }
    }
}
