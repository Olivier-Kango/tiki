<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
    die("Please run from a shell");
}

// defines

$tikiIgnoreFolder = dirname(__FILE__);
$tikiRoot = dirname($tikiIgnoreFolder, 5);

$fileStart = strlen($tikiRoot) + 1;

$ignoreFile = 'ignore_list.json';
$ignoreFileOriginal = $tikiIgnoreFolder . DIRECTORY_SEPARATOR . $ignoreFile;
$ignoreFileBackup = $tikiIgnoreFolder . DIRECTORY_SEPARATOR . 'back_' . $ignoreFile;
$ignoreFileNew = $tikiIgnoreFolder . DIRECTORY_SEPARATOR . 'new_' . $ignoreFile;

$ignoreExists = file_exists($ignoreFileOriginal);

$jsonReport = 'phpcs.json';

// change to tiki root
chdir($tikiRoot);

echo "# Running PHPCS ...." . PHP_EOL;

// run PHPCS (we need to remove the ignore file while we execute phpcs, so we can generate all entries)
if ($ignoreExists) {
    if (file_exists($ignoreFileBackup)) {
        unlink($ignoreFileBackup);
    }
    rename($ignoreFileOriginal, $ignoreFileBackup);
}

system(
    'php vendor_bundled/vendor/squizlabs/php_codesniffer/bin/phpcs'
    . ' -s --runtime-set ignore_warnings_on_exit true --parallel=32'
    . ' --report=json --report-file=' . $jsonReport
);

if ($ignoreExists) {
    rename($ignoreFileBackup, $ignoreFileOriginal);
}

// Processors (we need one for each sniff we want to process)
$process = [
    'PSR1.Methods.CamelCapsMethodName.NotCamelCaps' => function ($file, $message) {
        $parts = explode('"', $message['message']);
        return $parts[1];
    },
    'PSR1.Classes.ClassDeclaration.MultipleClasses' => function ($file, $message) {
        $lines = file($file);
        preg_match(
            '/(interface|class|trait|enum)[[:space:]]*([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*).*/mi',
            $lines[$message['line'] - 1],
            $matches
        );

        return $matches[1] . ':' . $matches[2];
    },
    'PSR1.Classes.ClassDeclaration.MissingNamespace' => function ($file, $message) {
        $lines = file($file);
        preg_match(
            '/(interface|class|trait|enum)[[:space:]]*([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*).*/mi',
            $lines[$message['line'] - 1],
            $matches
        );

        return $matches[1] . ':' . $matches[2];
    },
    'Squiz.Classes.ValidClassName.NotCamelCaps' => function ($file, $message) {
        $parts = explode('"', $message['message']);
        return $parts[1];
    },
    'PSR12.Properties.ConstantVisibility.NotFound' => function ($file, $message) {
        $lines = file($file);
        preg_match(
            '/const[[:space:]]*([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*).*/mi',
            $lines[$message['line'] - 1],
            $matches
        );

        return $matches[1];
    },
    'PSR2.Methods.MethodDeclaration.Underscore' => function ($file, $message) {
        $parts = explode('"', $message['message']);
        return $parts[1];
    },
    'Squiz.Scope.MethodScope.Missing' => function ($file, $message) {
        $parts = explode('"', $message['message']);
        return $parts[1];
    },
    'PSR2.Classes.PropertyDeclaration.Underscore' => function ($file, $message) {
        $parts = explode('"', $message['message']);
        return $parts[1];
    },
    'PSR2.Classes.PropertyDeclaration.ScopeMissing' => function ($file, $message) {
        $parts = explode('"', $message['message']);
        return $parts[1];
    },
    'PSR2.Classes.PropertyDeclaration.VarUsed' => function ($file, $message) {
        $lines = file($file);
        preg_match(
            '/var[[:space:]]*(\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*).*/mi',
            $lines[$message['line'] - 1],
            $matches
        );

        return $matches[1];
    },

];

echo "# Processing results ...." . PHP_EOL;

$json = json_decode(file_get_contents($jsonReport), true);

$results = [];
foreach ($json['files'] as $fileFullPath => $info) {
    if (empty($info['messages'])) { // no errors
        continue;
    }

    $file = substr($fileFullPath, $fileStart); // remove full path from file

    foreach ($info['messages'] as $message) {
        $sniff = $message['source'];

        if (empty($process[$sniff])) { // we do not have a processor, so ignoring
            continue;
        }

        if (! isset($results[$sniff])) {
            $results[$sniff] = [];
        }
        if (! isset($results[$sniff][$file])) {
            $results[$sniff][$file] = [];
        }

        $ignoreValue = $process[$sniff]($fileFullPath, $message); // generate the value for the ignore list

        if (empty($ignoreValue)) {
            continue;
        }

        $results[$sniff][$file][$ignoreValue] = true;
    }
}

file_put_contents($ignoreFileNew, json_encode($results, JSON_PRETTY_PRINT));

echo "# New ignore list available in " . $ignoreFileNew . PHP_EOL;
