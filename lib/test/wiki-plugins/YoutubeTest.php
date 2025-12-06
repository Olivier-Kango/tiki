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
    public function testWikiPluginCode($data, $params = []): void
    {
        $result = wikiplugin_youtube($data, $params);

        $this->assertIsString($result);
        $this->assertStringContainsString('~np~', $result);
    }

    public function testWikiPluginCodeWithMissingMovieParam(): void
    {
        $data = '';
        $params = [];

        $result = wikiplugin_youtube($data, $params);

        $this->assertStringContainsString('movie', $result);
    }

    public static function provider(): array
    {
        return [
            ['', ['movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568']],
            ['', ['movie' => 'https://www.youtube.com/watch?v=NdPpffwYGoM']],
            ['', ['movie' => 'https://www.youtube.com/watch?v=WbTkF-N-lO0']],
            ['', ['movie' => 'https://www.youtube.com/watch?v=4AcGoG9PChs', 'privacyEnhanced' => 'y']],
        ];
    }
}
