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
     * @param string $data
     * @param string $expectedSubstring
     * @param array  $params
     */
    public function testWikiPluginCode($data, $expectedSubstring, $params = []): void
    {
        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);

        // The plugin returns raw HTML, so we simply verify that the essential YouTube embed URL is present.
        $this->assertIsString($result);
        $this->assertStringContainsString($expectedSubstring, $result);
    }

    public function testWikiPluginCodeWithMissingMovieParam(): void
    {
        $data = '';
        $params = []; // simulate missing "movie"

        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);

        // When movie is missing, plugin returns an HTML <div> alert with the error text.
        $this->assertIsString($result);
        $this->assertStringContainsString(
            'Plugin YouTube error: the movie parameter is empty.',
            $result
        );
    }

    public static function provider(): array
    {
        return [
            [
                '',
                'youtube.com/embed/bPHuY7QL568',
                ['movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568']
            ],
            [
                '',
                'youtube.com/embed/deby_Yb1-ac',
                ['movie' => 'https://www.youtube.com/watch?v=4AcGoG9PChs']
            ],
            [
                '',
                'youtube.com/embed/deby_Yb1-ac',
                ['movie' => 'https://www.youtube.com/watch?v=WbTkF-N-lO0']
            ],
            [
                '',
                'youtube-nocookie.com/embed/deby_Yb1-ac',
                ['movie' => 'https://www.youtube.com/watch?v=h80QuuYxbhk', 'privacyEnhanced' => 'y']
            ],
        ];
    }
}
