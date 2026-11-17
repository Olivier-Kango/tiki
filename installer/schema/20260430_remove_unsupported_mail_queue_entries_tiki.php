<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Remove unsupported mail queue entries that are serialized in old Laminas/Zend format.
 * These entries cannot be processed by the current Symfony Email-based system and should be cleaned up.
 *
 * The mail queue system migrated from Laminas\Mail\Message to Symfony\Component\Mime\Email
 * as of Tiki 29. Users upgrading from Tiki < 29 may have old serialized messages left in the queue
 * that cannot be deserialized and processed.
 *
 * @param Installer $installer
 */
function upgrade_20260430_remove_unsupported_mail_queue_entries_tiki($installer)
{
    $allQueueEntries = $installer->fetchAll('SELECT messageId, message FROM tiki_mail_queue');

    $incompatibleIds = [];

    foreach ($allQueueEntries as $entry) {
        $previousErrorReporting = error_reporting(0);
        try {
            $mail = unserialize($entry['message']);
        } finally {
            error_reporting($previousErrorReporting);
        }

        if (! $mail instanceof \Symfony\Component\Mime\Email) {
            $incompatibleIds[] = $entry['messageId'];
        }
    }

    // This will bubble up to the console and stop the migration
    if (! empty($incompatibleIds) && ! $installer->deleteUnsupportedMailQueue) {
        throw new Exception(
            sprintf(
                'Found %d unsupported mail queue entries (serialized in old Laminas/Zend format). These entries cannot be processed. '
                    . 'They can be removed with <info>php console.php mail-queue:delete-unsupported</info>. This will permanently delete their queued email data. '
                    . 'Then retry the database update.',
                count($incompatibleIds)
            )
        );
    }

    // The web installer sets this only after the administrator confirms deletion.
    if (! empty($incompatibleIds) && $installer->deleteUnsupportedMailQueue) {
        foreach ($incompatibleIds as $messageId) {
            $installer->query('DELETE FROM tiki_mail_queue WHERE messageId = ?', [$messageId]);
        }

        error_log(sprintf( // @phpstan-ignore disallowedFunctions.errorLog (upgrade script — logs removed entry count for traceability)
            'Tiki upgrade: Removed %d unsupported mail queue entries. These were serialized in old Laminas/Zend format.',
            count($incompatibleIds)
        ));
    }

    return true;
}
