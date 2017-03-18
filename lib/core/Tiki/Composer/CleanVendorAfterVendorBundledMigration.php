<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Composer;

use Composer\Script\Event;
use Composer\Util\FileSystem;

/**
 * After Migrate the vendors to vendors_bundled, we should clean the vendor folder
 * We don't want to that by deleting all files in the vendor folder, instead we will try
 * to do sensitive decisions about what to delete
 *
 * All the process is skipped exists a file called "do_not_clean.txt" in the vendor folder
 *
 * Class CleanVendorAfterVendorBundledMigration
 * @package Tiki\Composer
 */
class CleanVendorAfterVendorBundledMigration
{
	/**
	 * @param Event $event
	 */
	public static function clean(Event $event)
	{
		/*
		 * 1) If a file called do_not_clean.txt exists in the vendor folder stop
		 * 2) If there is a composer.json file, warn the user that they might need to clean the folder by themselves
		 * 3) If there is no composer.json in the root, clean all folders and autoload.php in the vendor folder
		 */

		$io = $event->getIO();

		$rootFolder = realpath(__DIR__.'/../../../../');
		$oldVendorFolder = realpath($rootFolder.'/vendor');

		if ($rootFolder === false || $oldVendorFolder === false || !is_dir($oldVendorFolder)) {
			return;
		}

		// 1) If a file called do_not_clean.txt exists in the vendor folder stop
		if (file_exists($oldVendorFolder.'/do_not_clean.txt')) {
			$io->write('');
			$io->write('File vendor/do_not_clean.txt is present, no attempt to clean the vendor folder will be done!');
			$io->write('');

			return;
		}

		// 2) If there is a composer.json file, warn the user that they might need to clean the folder themselves
		if (file_exists($rootFolder.'/composer.json')) {
			$io->write('');
			$io->write(
				'Since the is a composer.json file in the root of the site, we will not try to clean your vendor folder'
			);
			$io->write('as part of the migration from vendor to vendor_bundled/vendor, you need to review that yourself!');
			$io->write('');

			return;
		}

		// 3) If there is no composer.json in the root, clean all folders and autoload.php in the vendor folder
		$fs = new FileSystem();

		$fs->remove($oldVendorFolder.'/autoload.php');

		$vendorDirs = glob($oldVendorFolder.'/*', GLOB_ONLYDIR);
		foreach ($vendorDirs as $dir) {
			if (is_dir($dir)) {
				$fs->remove($dir);
			}
		}
	}
}

