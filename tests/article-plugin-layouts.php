<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Tests\ArticleLayouts;

use LogicException;
use RuntimeException;

/** Keep test doubles out of Tiki's global class and function names. */
final class PluginFixture
{
    public static array $libraries = [];

    public static function lib(string $name): object
    {
        return self::$libraries[$name];
    }

    public static function applySeparators(array $params, array $info): array
    {
        return $params;
    }
}

function tra(string $text): string
{
    return $text;
}

function tr(string $text, ...$args): string
{
    return $text;
}

class_alias(PluginFixture::class, __NAMESPACE__ . '\\TikiLib');
class_alias(PluginFixture::class, __NAMESPACE__ . '\\WikiPlugin_Helper');

$smarty = new class {
    public function __call(string $name, array $arguments)
    {
        return match ($name) {
            'assign', 'assign_by_ref' => null,
            'fetch' => $arguments[0] === 'tiki-view_articles-titleonly.tpl'
                ? 'titles' : '<article>Native output</article>',
            default => throw new LogicException('Unexpected Smarty method: ' . $name),
        };
    }
};
$library = new class {
    public int $now = 2000;

    public function __call(string $name, array $arguments)
    {
        return match ($name) {
            'list_topics', 'list_types_byname' => [],
            'list_articles' => ['data' => [], 'count' => 0],
            'getDefaultAdminLogin' => 'admin',
            default => throw new LogicException('Unexpected library method: ' . $name),
        };
    }
};
PluginFixture::$libraries = ['smarty' => $smarty, 'tiki' => $library, 'art' => $library, 'user' => $library];

// Load the real plugin into the fixture namespace, where only its dependencies are substituted.
// The source is a fixed repository file, never user input. No global Tiki classes are redeclared.
$source = file_get_contents(dirname(__DIR__) . '/lib/wiki-plugins/wikiplugin_articles.php');
if ($source === false || ! str_starts_with($source, '<?php')) {
    throw new RuntimeException('Cannot load the ARTICLES plugin');
}
eval('namespace ' . __NAMESPACE__ . ';' . substr($source, 5));

$prefs = ['feature_articles' => 'y', 'feature_multilingual' => 'n', 'maxRecords' => 10];
$pageLang = 'de';
$info = wikiplugin_articles_info();
$values = array_column($info['params']['layout']['options'], 'value');
if ($values !== ['classic', 'tiles', 'list', 'overlay']) {
    throw new RuntimeException('Four editor choices missing');
}
$params = [];
foreach ($info['params'] as $name => $definition) {
    $params[$name] = $definition['default'] ?? null;
}
$params['dateStart'] = $params['dateEnd'] = '';
foreach (['classic', 'tiles', 'list', 'overlay', 'invalid', null] as $layout) {
    $result = wikiplugin_articles('', array_replace($params, ['layout' => $layout]));
    $enhanced = in_array($layout, ['tiles', 'list', 'overlay'], true);
    if (! str_contains($result, '<article>Native output</article>')) {
        throw new RuntimeException('Native template output lost');
    }
    if (str_contains($result, 'articles-layout') !== $enhanced) {
        throw new RuntimeException('Incorrect layout wrapper');
    }
    if ($enhanced && ! str_contains($result, 'articles-layout-' . $layout)) {
        throw new RuntimeException('Wrong selected layout');
    }
}
if (! str_contains(wikiplugin_articles('', array_replace($params, ['layout' => 'overlay', 'titleonly' => 'y'])), 'titles')) {
    throw new RuntimeException('Title-only compatibility lost');
}
$prefs['feature_articles'] = 'n';
if (wikiplugin_articles('', $params) !== '') {
    throw new RuntimeException('Feature-disabled behavior changed');
}
if (class_exists('TikiLib', false) || class_exists('WikiPlugin_Helper', false)) {
    throw new RuntimeException('The fixture must not introduce global Tiki classes');
}
echo "PASS: four plugin choices, selection, default/invalid fallback, native rendering, title-only and disabled feature\n";
