<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Composer;

use Composer\Script\Event;
use Composer\Util\FileSystem;
use Exception;

class CleanVendors
{
    /**
     * @var array Files or directories to remove anywhere in vendor files. Case-insensitive. Must specify as lower case.
     */
    private static array $standardFiles = [
        '.coveralls.yml',
        '.editorconfig',
        '.empty',
        '.eslintignore',
        '.eslintrc',
        '.eslintrc.json',
        '.gitattributes',
        '.github',
        '.github/workflows',
        '.gitignore',
        '.gitmodules',
        '.hound.yml',
        '.jscsrc',
        '.jshintignore',
        '.jshintrc',
        '.mailmap',
        '.npmignore',
        '.php_cs',
        '.php_cs.dist',
        '.scrutinizer.yml',
        '.styleci.yml',
        '.travis.install.sh',
        '.travis.yml',
        '_translationstatus.txt',
        'appveyor.yml',
        'authors',
        'authors.txt',
        'bower.json',
        'building.md',
        'changelog',
        'changelog.md',
        'changelog.txt',
        'changes.md',
        'changes.md~',
        'changes.txt',
        'cname',
        'code_of_conduct.md',
        'composer.json',
        'composer.lock',
        'conduct.md',
        'contributing.md',
        'credits.md',
        'demo',
        'demo.html',
        'demo.js',
        'demo1',
        'demo2',
        'demos',
        'demos.html',
        'development',
        'devtools',
        'doc',
        'docs',
        'documentation',
        'docker-compose.yml',
        'example',
        'example.html',
        'example.md',
        'examples',
        'gemfile',
        'gemfile.lock',
        'gruntfile.coffee',
        'gruntfile.js',
        'history.md',
        'index.html',
        'info.txt',
        'install',
        'makefile',
        'news',
        'notice',
        'package.json',
        'phpunit.xml.dist',
        'psalm.xml',
        'readme',
        'readme.md',
        'readme.markdown',
        'readme.mdown',
        'readme.php',
        'readme.rst',
        'readme.textile',
        'readme.txt',
        'robots.txt',
        'sample',
        'samples',
        'security.md',
        'support.md',
        'test',
        'test.html',
        'tests',
        'todo',
        'todo.md',
        'upgrading.md',
        'www',
    ];

    public static function clean(Event $event): void
    {
        $vendors = rtrim($event->getComposer()->getConfig()->get('vendor-dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        self::remove($vendors . 'ezyang/htmlpurifier', [
            'INSTALL.fr.utf8',
            'release1-update.php',
            'release2-tag.php',
            'test-settings.sample.php',
            'test-settings.travis.php',
            'VERSION',
            'WHATSNEW',
            'WYSIWYG',
        ]);

        self::remove($vendors . 'jason-munro/cypht', 'hm3.sample.ini');
        self::remove($vendors . 'studio-42/elfinder', ['files', 'elfinder.html']);

        self::remove($vendors . 'laminas/', [
            'laminas-feed/docs',
            'laminas-feed/test',
            'laminas-filter/docs',
            'laminas-filter/test',
            'laminas-i18n/docs',
            'laminas-i18n/test',
            'laminas-ldap/docs',
            'laminas-ldap/test',
            'laminas-servicemanager/docs',
            'laminas-servicemanager/test',
            'laminas-validator/docs',
            'laminas-validator/test',
        ]);

        self::remove($vendors . 'league/commonmark', 'CHANGELOG-0.x.md');
        self::remove($vendors . 'pear/pear/', ['docs', 'tests']);

        self::remove($vendors . 'smarty/smarty', [
            'change_log.txt',
            'INHERITANCE_RELEASE_NOTES.txt',
            'SMARTY_2_BC_NOTES.txt',
            'SMARTY_3.0_BC_NOTES.txt',
            'SMARTY_3.1_NOTES.txt',
        ]);

        self::remove($vendors . 'symfony/', [
            'config/Tests',
            'console/Tests',
            'dependency-injection/Tests',
            'http-client/Test',
            'http-foundation/Test',
            'http-foundation/Tests',
            'mime/Test',
            'mime/Tests',
            'routing/Tests',
        ]);

        self::remove($vendors . 'wamania/php-stemmer', 'test');

        self::removeStandard($vendors);
        self::addIndexFiles($vendors);
    }

    private static function addIndexFiles(string $path): void
    {
        $excludeDirs = [
            'phpseclib/phpseclib/phpseclib/Crypt',
            'phpunit/phpunit/schema',
            'rector/rector',
        ];

        $path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (empty(glob($path . '*', GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE))) {
            $indexPath = $path . 'index.php';
            if (! file_exists($indexPath)) {
                file_put_contents($indexPath, "<?php\nexit;\n");
            }
        }

        $dirs = glob($path . '{,.}*[!.]', GLOB_MARK | GLOB_BRACE | GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $dir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (! array_filter($excludeDirs, fn($item) => str_contains($dir, $item))) {
                self::addIndexFiles($dir);
            }
        }
    }

    private static function removeStandard(string $base): void
    {
        $fs = new FileSystem();
        $files = glob($base . '{,.}*[!.]', GLOB_MARK | GLOB_BRACE);
        foreach ($files as $file) {
            $file = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
            $filename = strtolower(basename($file));
            if (in_array($filename, self::$standardFiles, true)) {
                $fs->remove($file);
            } elseif (is_dir($file)) {
                self::removeStandard($file);
            }
        }
    }

    private static function remove(string $base, array|string $files): void
    {
        $files = (array) $files;
        $base = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $base), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $fs = new FileSystem();

        if (! is_dir($base)) {
            echo "Error: directory $base is not found\n";
            return;
        }

        foreach ($files as $file) {
            $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
            $path = $base . $file;

            if (! file_exists($path)) {
                echo "Warning : '$path' does not exist .\n";
                continue;
            }

            try {
                if (is_link($path) || is_file($path)) {
                    $fs->remove($path);
                } elseif (is_dir($path)) {
                    $fs->removeDirectory($path);
                }

                if (file_exists($path)) {
                    echo "Error: Unable to delete '$path'\n";
                }
            } catch (Exception $e) {
                echo "Error : {$e->getMessage()}\n";
            }
        }
    }
}
