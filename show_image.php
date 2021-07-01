<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (! isset($_REQUEST["nocache"])) {
	session_cache_limiter('private_no_expire');
}

include_once("tiki-setup.php");

if ($prefs['feature_file_galleries'] == 'y' && $prefs['file_galleries_redirect_from_image_gallery'] == 'y') {
	$fileGalleryInfo = $tikilib->table('tiki_object_attributes')->fetchRow([], ['value' => $_REQUEST["id"], 'attribute' => 'tiki.file.imageid']);
	if ($fileGalleryInfo) {
		include_once($tikipath . 'tiki-sefurl.php');
		TikiLib::lib('access')->redirect(filter_out_sefurl('tiki-download_file.php?fileId=' . $fileGalleryInfo['itemId'] . '&display'));
	}
}

// TODO ImageGalleryRemoval23.x remove this eventually?
// Kept redirect to filegal

Feedback::error(tr('Image Galleries have been removed. Run the migration script: ` php console.php gallery:migrate`'));
