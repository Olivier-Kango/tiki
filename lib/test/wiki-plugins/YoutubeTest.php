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
        $this->assertStringContainsString($expectedSubstring, $result);
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
            // All possible YouTube URL formats
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtu.be/j4dMnAPZu70' ]],
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube.com/v/j4dMnAPZu70' ]],
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube.com/e/j4dMnAPZu70' ]],
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube.com/watch?v=j4dMnAPZu70' ]],
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube.com/shorts/j4dMnAPZu70' ]],
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube.com/embed/j4dMnAPZu70' ]],
            ['', 'youtube.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube.com/live/j4dMnAPZu70' ]],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', [ 'movie' => 'https://youtube-nocookie.com/embed/j4dMnAPZu70' ]],
        ];
    }

    /**
     * @dataProvider youtubeIdProvider
     */
    public function testGetYoutubeId(string $url, string $expectedId): void
    {
        $result = getYoutubeId($url);
        $this->assertIsArray($result);
        $this->assertSame($expectedId, $result['id']);
    }

    public static function youtubeIdProvider(): array
    {
        return [
            'watch url' => ['https://www.youtube.com/watch?v=j4dMnAPZu70', 'j4dMnAPZu70'],
            'shorts url' => ['https://www.youtube.com/shorts/j4dMnAPZu70', 'j4dMnAPZu70'],
            'live url' => ['https://www.youtube.com/live/j4dMnAPZu70', 'j4dMnAPZu70'],
            'embed url' => ['https://www.youtube.com/embed/j4dMnAPZu70', 'j4dMnAPZu70'],
            'short url' => ['https://youtu.be/j4dMnAPZu70', 'j4dMnAPZu70'],
            'short url with www' => ['https://www.youtu.be/j4dMnAPZu70', 'j4dMnAPZu70'],
            'nocookie embed' => ['https://youtube-nocookie.com/embed/j4dMnAPZu70', 'j4dMnAPZu70'],
            'v url' => ['https://youtube.com/v/j4dMnAPZu70', 'j4dMnAPZu70'],
            'e url' => ['https://youtube.com/e/j4dMnAPZu70', 'j4dMnAPZu70'],
            'raw id' => ['j4dMnAPZu70', 'j4dMnAPZu70'],
        ];
    }
}
