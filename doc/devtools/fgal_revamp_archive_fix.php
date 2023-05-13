<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\FileGallery\File;

if (PHP_SAPI !== 'cli') {
    die("Only available through command-line.\n");
}

require_once 'tiki-setup.php';

if ($prefs['fgal_use_db'] !== 'n') {
    die("File storage is not set to use filesystem directory, so nothing to do!\n");
}

$storage_dir = rtrim($prefs['fgal_use_dir'], DIRECTORY_SEPARATOR);
if (! is_writable($storage_dir)) {
    die("Storage directory is not writable: $storage_dir. Please run this script with a user that has permission to write files in that directory.\n");
}

$db = TikiDb::get();
$rows = $db->fetchAll('SELECT archives.* FROM `tiki_files` archives WHERE exists(select fileId from tiki_files where fileId != archives.fileId and path = archives.path and fileId = archives.archiveId)');
foreach ($rows as $row) {
    echo "Processing file {$row[fileId]}...\n";
    $file = new File($row);
    $path = $file->galleryDefinition()->uniquePath($file);
    $source = rtrim($prefs['fgal_use_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file->path;
    $dest = rtrim($prefs['fgal_use_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    if (! file_exists($source)) {
        echo "Source file doesn't exist: $source. Skipping...\n";
        continue;
    }
    if (! copy($source, $dest)) {
        echo "Failed copying $source to $dest. Skipping...\n";
        continue;
    }
    $db->query("UPDATE tiki_files SET path = ? WHERE fileId = ?", [$path, $file->fileId]);
}
