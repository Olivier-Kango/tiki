<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\TikiLib;

use PHPUnit\Framework\TestCase;
use TikiLib;

/**
 * @group integration
 */
class PluginPermissionTest extends TestCase
{
    private $originalUser;

    protected function setUp(): void
    {
        global $user;
        $this->originalUser = $user;
    }

    protected function tearDown(): void
    {
        global $user;
        $user = $this->originalUser;
    }

    public function testAnonymousGroupPermissions(): void
    {
        global $user;
        $user = null;
        $parser = TikiLib::lib('parser');

        $this->assertSame('n', $parser->check_permission_from_plugin_params(['editable_by_groups' => 'Registered']));
        $this->assertSame('y', $parser->check_permission_from_plugin_params(['editable_by_groups' => 'Anonymous']));
        $this->assertSame('n', $parser->check_permission_from_plugin_params([
            'editable_by_user' => '',
            'editable_by_groups' => 'Registered',
        ]));
        $this->assertSame('n', $parser->check_permission_from_plugin_params([]));

        $user = 'Anonymous';
        $this->assertSame('y', $parser->check_permission_from_plugin_params(['editable_by_groups' => 'Anonymous']));
    }

    public function testRegisteredUserPermissions(): void
    {
        global $user;
        $userlib = TikiLib::lib('user');
        $user = $userlib->getDefaultAdminLogin();
        $parser = TikiLib::lib('parser');

        $this->assertSame('y', $parser->check_permission_from_plugin_params(['editable_by_groups' => 'admins']));
        $this->assertSame(
            'n',
            $parser->check_permission_from_plugin_params(['editable_by_groups' => 'group_that_does_not_exist'])
        );
        $this->assertSame('y', $parser->check_permission_from_plugin_params(['editable_by_user' => strtoupper($user)]));
    }

    public function testUnknownUserHasNoGroups(): void
    {
        global $user;
        $user = 'plugin_permission_nonexistent_' . uniqid();

        $this->assertSame(
            'n',
            TikiLib::lib('parser')->check_permission_from_plugin_params(['editable_by_groups' => 'Anonymous'])
        );
    }
}
