<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_youtube.php');

class WikiPlugin_YoutubeTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider
     */
    public function testWikiPluginCode($data, $expectedSubstring, $params = []): void
    {
        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);

        if ($result instanceof WikiParser_PluginOutput) {
            $result = $result->toWiki();
        }

        $this->assertIsString($result);
        $this->assertStringContainsString('~np~', $result);
    }

    public function testWikiPluginCodeWithMissingMovieParam(): void
    {
        $data = '';
        $params = [];

        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);

        if ($result instanceof WikiParser_PluginOutput) {
            $result = $result->toWiki();
        }

        $this->assertStringContainsString('movie', $result);
    }

    public static function provider(): array
    {
        return [
            ['', 'youtube.com/embed/bPHuY7QL568', [
                'movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568'
            ]],

            ['', 'youtube.com/embed/NdPpffwYGoM', [
                'movie' => 'https://www.youtube.com/watch?v=NdPpffwYGoM'
            ]],

            ['', 'youtube.com/embed/WbTkF-N-lO0', [
                'movie' => 'https://www.youtube.com/watch?v=WbTkF-N-lO0'
            ]],

            ['', 'youtube-nocookie.com/embed/4AcGoG9PChs', [
                'movie' => 'https://www.youtube.com/watch?v=4AcGoG9PChs',
                'privacyEnhanced' => 'y'
            ]],

            ['', 'youtube.com/embed/wfVJBWHtqXM', [
                'movie' => 'https://www.youtube.com/live/wfVJBWHtqXM'
            ]],
        ];
    }

    /**
     * @dataProvider youtubeIdProvider
     */
    public function testGetYoutubeId(string $url, string $expectedId): void
    {
        $this->assertSame($expectedId, getYoutubeId($url));
    }

    public static function youtubeIdProvider(): array
    {
        return [
            'watch url' => ['https://www.youtube.com/watch?v=NdPpffwYGoM', 'NdPpffwYGoM'],
            'shorts url' => ['https://www.youtube.com/shorts/4AcGoG9PChs', '4AcGoG9PChs'],
            'live url' => ['https://www.youtube.com/live/wfVJBWHtqXM', 'wfVJBWHtqXM'],
            'embed url' => ['https://www.youtube.com/embed/WbTkF-N-lO0', 'WbTkF-N-lO0'],
            'short url' => ['https://youtu.be/bPHuY7QL568', 'bPHuY7QL568'],
            'short url with www' => ['https://www.youtu.be/bPHuY7QL568', 'bPHuY7QL568'],
            'raw id' => ['bPHuY7QL568', 'bPHuY7QL568'],
        ];
    }
}
