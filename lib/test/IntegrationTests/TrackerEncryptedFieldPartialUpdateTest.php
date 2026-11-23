<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\IntegrationTests;

use JitFilter;
use Perms;
use Perms_Resolver_Default;
use Perms_ResolverFactory_StaticFactory;
use Services_Encryption_Controller;
use Services_Tracker_Utilities;
use Tiki\Encryption\Key;
use TikiLib;
use TikiTestCase;
use Tracker_Definition;

/**
 * An update that submits only some fields of an item (inline edit in a tracker
 * list, a partial API update) must leave the other encrypted fields as they are.
 * Their stored value is ciphertext, and encrypting it again on save makes it
 * unreadable.
 *
 * @group integration
 */
class TrackerEncryptedFieldPartialUpdateTest extends TikiTestCase
{
    private static $oldPrefs;
    private static $oldSession;
    private static $trklib;
    private static $trackerId;
    private static $keyId;

    public static function setUpBeforeClass(): void
    {
        global $prefs, $user;

        self::$oldPrefs = $prefs;
        self::$oldSession = $_SESSION['encryption_shared_keys'] ?? null;
        $prefs['feature_trackers'] = 'y';
        $prefs['feature_user_encryption'] = 'y';
        $prefs['trackerfield_secret'] = 'y';
        $user = '';

        parent::setUpBeforeClass();

        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(true)),
        ]);
        Perms::set($perms);

        // The key gets a holder, so the test keeps working where a key no Tiki user holds is refused. The holder is
        // the current user while the key is saved, the case where save_key hands the share back.
        $encryption = new Services_Encryption_Controller();
        $encryption->setUp();
        $user = 'admin';
        try {
            $key = $encryption->action_save_key(new JitFilter([
                'name' => 'Partial update test key ' . uniqid(),
                'shares' => 1,
                'algo' => 'aes-256-ctr',
                'users' => 'admin',
            ]));
        } finally {
            $user = '';
        }
        self::$keyId = (int) $key['keyId'];
        $_SESSION['encryption_shared_keys'][self::$keyId] = $key['shares'][0];

        self::$trklib = TikiLib::lib('trk');
        self::$trackerId = self::$trklib->replace_tracker(null, 'Encrypted partial update test ' . uniqid(), '', [], 'n');

        $fields = [
            'first_secret' => 't',
            'second_secret' => 't',
            'first_sec' => 'SEC',
            'second_sec' => 'SEC',
        ];
        foreach (array_keys($fields) as $i => $permName) {
            self::$trklib->replace_tracker_field(
                trackerId: self::$trackerId,
                fieldId: 0,
                name: $permName,
                type: $fields[$permName],
                isMain: $i === 0 ? 'y' : 'n',
                isSearchable: 'n',
                isTblVisible: 'y',
                isPublic: 'y',
                isHidden: 'n',
                isMandatory: 'n',
                position: ($i + 1) * 10,
                options: '',
                permName: $permName,
                encryptionKeyId: self::$keyId,
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;

        if (! empty(self::$trackerId)) {
            self::$trklib->remove_tracker(self::$trackerId);
        }
        if (! empty(self::$keyId)) {
            TikiLib::lib('tiki')->table('tiki_encryption_keys')->delete(['keyId' => self::$keyId]);
            $userPrefs = TikiLib::lib('tiki')->table('tiki_user_preferences');
            $userPrefs->deleteMultiple(['prefName' => $userPrefs->expr('$$ LIKE ?', ['%.sk.' . self::$keyId])]);
        }
        if (self::$oldSession === null) {
            unset($_SESSION['encryption_shared_keys']);
        } else {
            $_SESSION['encryption_shared_keys'] = self::$oldSession;
        }

        $prefs = self::$oldPrefs;
        parent::tearDownAfterClass();
    }

    public function testUpdatingOneEncryptedFieldKeepsTheOtherReadable(): void
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $utilities = new Services_Tracker_Utilities();

        $itemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => [
                'first_secret' => 'First',
                'second_secret' => 'Second',
            ],
        ]);
        $this->assertNotFalse($itemId);
        $this->assertSame('First', $this->readDecrypted($definition, $itemId, 'first_secret'));
        $this->assertSame('Second', $this->readDecrypted($definition, $itemId, 'second_secret'));

        $result = $utilities->updateItem($definition, [
            'itemId' => $itemId,
            'status' => 'o',
            'fields' => [
                'first_secret' => 'First, edited',
            ],
        ]);
        $this->assertNotFalse($result);

        $this->assertSame('First, edited', $this->readDecrypted($definition, $itemId, 'first_secret'));
        $this->assertSame('Second', $this->readDecrypted($definition, $itemId, 'second_secret'));
    }

    public function testInlineEditOfOneSecretFieldKeepsTheOtherSecretFieldReadable(): void
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $utilities = new Services_Tracker_Utilities();

        $itemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => [
                'first_sec' => 'First',
                'second_sec' => 'Super Secret',
            ],
        ]);
        $this->assertNotFalse($itemId);

        $result = $utilities->updateItem($definition, [
            'itemId' => $itemId,
            'status' => 'o',
            'fields' => [
                'second_sec' => 'Huh?',
            ],
        ]);
        $this->assertNotFalse($result);

        $this->assertSame('First', $this->readDecrypted($definition, $itemId, 'first_sec'));
        $this->assertSame('Huh?', $this->readDecrypted($definition, $itemId, 'second_sec'));
    }

    public function testAnUpdateSubmittingNoFieldKeepsEncryptedFieldsReadable(): void
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $utilities = new Services_Tracker_Utilities();

        $itemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => [
                'first_secret' => 'Kept',
                'second_secret' => 'Also kept',
            ],
        ]);
        $this->assertNotFalse($itemId);

        $result = $utilities->updateItem($definition, [
            'itemId' => $itemId,
            'status' => 'o',
            'fields' => [],
        ]);
        $this->assertNotFalse($result);

        $this->assertSame('Kept', $this->readDecrypted($definition, $itemId, 'first_secret'));
        $this->assertSame('Also kept', $this->readDecrypted($definition, $itemId, 'second_secret'));
    }

    private function readDecrypted(Tracker_Definition $definition, int $itemId, string $permName): string
    {
        $field = $definition->getFieldFromPermName($permName);
        $stored = TikiLib::lib('tiki')->table('tiki_tracker_item_fields')->fetchOne('value', [
            'itemId' => $itemId,
            'fieldId' => (int) $field['fieldId'],
        ]);
        return (string) (new Key(self::$keyId))->decryptData($stored);
    }
}
