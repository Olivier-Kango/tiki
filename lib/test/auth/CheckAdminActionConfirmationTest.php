<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Auth;

use TikiTestCase;
use UsersLib;

/**
 * @group integration
 */
class CheckAdminActionConfirmationTest extends TikiTestCase
{
    private const PASSWORD = 'Correct-Horse-Battery-42';

    /** @var UsersLib */
    protected $userlib;
    private $login;
    private $originalPref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userlib = new UsersLib();
        $this->login = uniqid('checkadminconfirm_');
        $this->assertNotFalse($this->userlib->add_user($this->login, self::PASSWORD, $this->login . '@example.test'));

        global $prefs;
        $this->originalPref = $prefs['users_admin_actions_require_validation'] ?? 'n';
        $prefs['users_admin_actions_require_validation'] = 'y';

        unset($_SESSION['autologin_base_url']);
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = $this->originalPref;
        unset($_SESSION['autologin_base_url']);

        $this->userlib->remove_user($this->login);
        parent::tearDown();
    }

    public function testAllowedWhenPasswordCorrect(): void
    {
        $this->assertSame('', $this->userlib->checkAdminActionConfirmation($this->login, self::PASSWORD));
    }

    public function testRejectedWhenPasswordIncorrect(): void
    {
        $this->assertNotSame('', $this->userlib->checkAdminActionConfirmation($this->login, 'wrong-password'));
    }

    public function testRejectedWhenPasswordMissing(): void
    {
        $this->assertNotSame('', $this->userlib->checkAdminActionConfirmation($this->login, ''));
    }

    public function testAllowedWhenPreferenceDisabled(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'n';

        $this->assertSame('', $this->userlib->checkAdminActionConfirmation($this->login, 'wrong-password'));
    }

    public function testRejectedWithAutologinSessionEvenIfPasswordCorrect(): void
    {
        $_SESSION['autologin_base_url'] = 'https://example.test/';

        $error = $this->userlib->checkAdminActionConfirmation($this->login, self::PASSWORD);

        $this->assertSame($this->userlib->getAutologinAdminActionError(), $error);
    }
}
