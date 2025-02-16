<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.


class Rating_RegisterVoteTest extends TikiTestCase
{
    protected $ratingDefaultOptions;
    protected $ratingAllowMultipleVotes;

    protected function setUp(): void
    {
        global $user, $prefs;
        $user = null;
        parent::setUp();
        TikiDb::get()->query('DELETE FROM `tiki_user_votings` WHERE `id` LIKE ?', ['test.%']);

        $this->ratingDefaultOptions = $prefs['rating_default_options'];
        $prefs['rating_default_options'] = '0,1,2,3,4,5';
        $this->ratingAllowMultipleVotes = $prefs['rating_allow_multi_votes'];
        $prefs['rating_allow_multi_votes'] = 'y';
    }

    protected function tearDown(): void
    {
        global $user, $prefs;
        $user = null;
        parent::tearDown();
        TikiDb::get()->query('DELETE FROM `tiki_user_votings` WHERE `id` LIKE ?', ['test.%']);

        $prefs['rating_default_options'] = $this->ratingDefaultOptions;
        $prefs['rating_allow_multi_votes'] = $this->ratingAllowMultipleVotes;
    }

    public static function tokenFormats(): array
    {
        return [
            'unknown' => ['foobar', 233, null],
            'comment' => ['comment', 123, 'comment123'],
            'article' => ['article', 123, 'article123'],
            'wiki' => ['wiki page', 123, 'wiki123'],
            'wikiAsString' => ['wiki page', '123', 'wiki123'],
            'wikiPageName' => ['wiki page', 'HomePage', null],
            'test' => ['test', 111, 'test.111'],
        ];
    }

    /**
     * @dataProvider tokenFormats
     * @param $type
     * @param $object
     * @param $token
     */
    public function testGetToken($type, $object, $token): void
    {
        $lib = new RatingLib();

        $this->assertEquals($token, $lib->get_token($type, $object));
    }

    public function testRecordUserVotes(): void
    {
        $lib = new RatingLib();
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 3));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 2));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 112, 4));
        $this->assertTrue($lib->record_user_vote('def', 'test', 111, 1));
        $this->assertTrue($lib->record_user_vote('def', 'test', 113, 1));
        $this->assertTrue($lib->record_user_vote('def', 'test', 112, 5));

        $this->assertEquals(
            [
                ['user' => 'abc', 'id' => 'test.111', 'optionId' => 2],
                ['user' => 'abc', 'id' => 'test.111', 'optionId' => 3],
                ['user' => 'abc', 'id' => 'test.112', 'optionId' => 4],
                ['user' => 'def', 'id' => 'test.111', 'optionId' => 1],
                ['user' => 'def', 'id' => 'test.112', 'optionId' => 5],
                ['user' => 'def', 'id' => 'test.113', 'optionId' => 1],
            ],
            $this->getTestData()
        );
    }

    public function testRecordUserVotesSingleVote(): void
    {
        global $prefs;
        $prefs['rating_allow_multi_votes'] = '';

        $lib = new RatingLib();
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 3));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 2));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 112, 4));
        $this->assertTrue($lib->record_user_vote('def', 'test', 111, 1));
        $this->assertTrue($lib->record_user_vote('def', 'test', 113, 1));
        $this->assertTrue($lib->record_user_vote('def', 'test', 112, 5));

        $this->assertEquals(
            [
                ['user' => 'abc', 'id' => 'test.111', 'optionId' => 2],
                ['user' => 'abc', 'id' => 'test.112', 'optionId' => 4],
                ['user' => 'def', 'id' => 'test.111', 'optionId' => 1],
                ['user' => 'def', 'id' => 'test.112', 'optionId' => 5],
                ['user' => 'def', 'id' => 'test.113', 'optionId' => 1],
            ],
            $this->getTestData()
        );
    }

    public function testAnonymousVotes(): void
    {
        $lib = new RatingLib();

        $key1 = 'deadbeef01234567';
        $key2 = 'deadbeef23456789';
        $this->assertTrue($lib->record_anonymous_vote($key1, 'test', 111, 3));
        $this->assertTrue($lib->record_anonymous_vote($key1, 'test', 111, 2));
        $this->assertTrue($lib->record_anonymous_vote($key1, 'test', 112, 4));
        $this->assertTrue($lib->record_anonymous_vote($key2, 'test', 111, 1));
        $this->assertTrue($lib->record_anonymous_vote($key2, 'test', 113, 1));
        $this->assertTrue($lib->record_anonymous_vote($key2, 'test', 112, 5));

        $this->assertEquals(
            [
                ['user' => "anonymous\0$key1", 'id' => 'test.111', 'optionId' => 2],
                ['user' => "anonymous\0$key1", 'id' => 'test.111', 'optionId' => 3],
                ['user' => "anonymous\0$key1", 'id' => 'test.112', 'optionId' => 4],
                ['user' => "anonymous\0$key2", 'id' => 'test.111', 'optionId' => 1],
                ['user' => "anonymous\0$key2", 'id' => 'test.112', 'optionId' => 5],
                ['user' => "anonymous\0$key2", 'id' => 'test.113', 'optionId' => 1],
            ],
            $this->getTestData()
        );
    }

    public function testAnonymousVotesSingleVote(): void
    {
        global $prefs;
        $prefs['rating_allow_multi_votes'] = '';

        $lib = new RatingLib();

        $key1 = 'deadbeef01234567';
        $key2 = 'deadbeef23456789';
        $this->assertTrue($lib->record_anonymous_vote($key1, 'test', 111, 3));
        $this->assertTrue($lib->record_anonymous_vote($key1, 'test', 111, 2));
        $this->assertTrue($lib->record_anonymous_vote($key1, 'test', 112, 4));
        $this->assertTrue($lib->record_anonymous_vote($key2, 'test', 111, 1));
        $this->assertTrue($lib->record_anonymous_vote($key2, 'test', 113, 1));
        $this->assertTrue($lib->record_anonymous_vote($key2, 'test', 112, 5));

        $this->assertEquals(
            [
                ['user' => "anonymous\0$key1", 'id' => 'test.111', 'optionId' => 2],
                ['user' => "anonymous\0$key1", 'id' => 'test.112', 'optionId' => 4],
                ['user' => "anonymous\0$key2", 'id' => 'test.111', 'optionId' => 1],
                ['user' => "anonymous\0$key2", 'id' => 'test.112', 'optionId' => 5],
                ['user' => "anonymous\0$key2", 'id' => 'test.113', 'optionId' => 1],
            ],
            $this->getTestData()
        );
    }

    public function testDiscardInvalidValue(): void
    {
        $lib = new RatingLib();
        $this->assertFalse($lib->record_user_vote('abc', 'test', '123', 6));

        $this->assertEquals(['0', '1', '2', '3', '4', '5'], $lib->get_options('test', '123'));
        $this->assertEquals(
            [],
            $this->getTestData()
        );
    }

    public function testGetWikiPageRange(): void
    {
        global $prefs;
        $prefs['wiki_simple_ratings_options'] = range(2, 8);

        $lib = new RatingLib();
        $this->assertEquals(range(2, 8), $lib->get_options('wiki page', 'HomePage'));
    }

    public function testGetArticleRange(): void
    {
        global $prefs;
        $prefs['article_user_rating_options'] = range(-2, 2);

        $lib = new RatingLib();
        $this->assertEquals(range(-2, 2), $lib->get_options('article', 1));
    }

    public function testGetUserVote(): void
    {
        $lib = new RatingLib();
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 4, time() - 3000));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 2, time() - 2000));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 112, 3, time() - 1000));
        $this->assertTrue($lib->record_user_vote('def', 'test', 111, 3, time() - 1000));

        $this->assertEquals(2.0, $lib->get_user_vote('abc', 'test', 111));
    }

    public function testGetMissingVote(): void
    {
        $lib = new RatingLib();
        $this->assertNull($lib->get_user_vote('abc', 'test', 111));
    }

    public function testGetAnonymousVote(): void
    {
        $lib = new RatingLib();
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 4, time() - 3000));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 111, 2, time() - 2000));
        $this->assertTrue($lib->record_user_vote('abc', 'test', 112, 3, time() - 1000));
        $this->assertTrue($lib->record_anonymous_vote('deadbeef12345678', 'test', 111, 3, time() - 1000));

        $this->assertEquals(3.0, $lib->get_anonymous_vote('deadbeef12345678', 'test', 111));
    }

    public function testEnvironmentUserLookup(): void
    {
        global $user;
        $user = 'foobar';

        $lib = new RatingLib();
        $this->assertTrue($lib->record_vote('test', '123', 2));

        $this->assertEquals(
            [
                ['user' => "foobar", 'id' => 'test.123', 'optionId' => 2],
            ],
            $this->getTestData()
        );

        $this->assertEquals(2.0, $lib->get_vote('test', '123'));
    }

    public function testEnvironmentAnonymousLookup(): void
    {
        $sessionId = session_id();

        $lib = new RatingLib();
        $this->assertTrue($lib->record_vote('test', '123', 2));

        $this->assertEquals(
            [
                ['user' => "anonymous\0" . $sessionId, 'id' => 'test.123', 'optionId' => 2],
            ],
            $this->getTestData()
        );

        $this->assertEquals(2.0, $lib->get_vote('test', '123'));
    }

    public function testCannotRecordOnUnknownObjectType(): void
    {
        $lib = new RatingLib();
        $this->assertFalse($lib->record_user_vote('abc', 'foobar', 111, 4));

        $this->assertEquals(
            [],
            $this->getTestData()
        );
    }

    private function getTestData(): array
    {
        return TikiDb::get()->fetchAll('SELECT `user`, `id`, `optionId` FROM `tiki_user_votings` WHERE `id` LIKE ? ORDER BY `user`, `id`, `optionId`', ['test.%']);
    }
}
