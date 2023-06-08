<?php

declare(strict_types=1);

/*

To run rector (https://getrector.com/documentation) on a file or directory, run:

php vendor_bundled/vendor/rector/rector/bin/rector --memory-limit=4G --dry-run process -- lib

Obviously, always run --dry-run first.

Normally, you should commit changes to this file only if you apply a rector globally.

You should never remove rectors definitions from this file in normal circumstances.  Eventually, the CI will run this file and error out if there would be changes.

If you apply something with rector, you commits should be something like:

For global commits (in this case the changes to rector.php would be commited):

[REF] Rector:  Update rules to apply SymfonyLevelSetList::UP_TO_SYMFONY_54 globally

One shot, or partial application (in which case your changes in rector.php should not be commited, or commited commented-out):

[REF] Rector:  Apply ReturnTypeFromStrictNativeCallRector::class to path lib/core

*/

use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\CodeQuality\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SymfonyLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/admin',
        __DIR__ . '/db',
        __DIR__ . '/doc',
        __DIR__ . '/installer',
        //__DIR__ . '/lang',
        __DIR__ . '/lib',
        __DIR__ . '/lists',
        __DIR__ . '/modules',
        __DIR__ . '/permissioncheck',
        __DIR__ . '/profiles',
        __DIR__ . '/themes',
    ]);
    $rectorConfig->skip([
        __DIR__ . '/src/SingleFile.php',
        __DIR__ . '/src/WholeDirectory',

        // or use fnmatch
        __DIR__ . '*/vendor/*',
    ]);

    /* Register sets of rules.

    They are not documented in a single place in rector doc unfortunately.
    Some can be found in
    https://github.com/rectorphp/rector/blob/main/packages/Set/ValueObject/LevelSetList.php
    https://github.com/rectorphp/rector/blob/main/packages/Set/ValueObject/SetList.php
    */
    $rectorConfig->sets([
        //LevelSetList::UP_TO_PHP_81,
        //PHPUnitSetList::PHPUNIT_100,
        SymfonyLevelSetList::UP_TO_SYMFONY_54, //Applied globally starting 2023-05-05
    ]);

    /* Register individial rules.

    Available rules for rector are found and documented here: https://getrector.com/documentation/rules-overview
    */
    $rectorConfig->rules([
        Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class,
        //CompleteDynamicPropertiesRector,
        //Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector::class,
        //ReturnTypeFromStrictNativeCallRector::class,
        //ReturnTypeFromStrictScalarReturnExprRector::class,
    ]);
};
