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
    public static array $templates = [];
    public static array $paginationParams = [];
    public static array $listedOffsets = [];

    public static function lib(string $name): object
    {
        return self::$libraries[$name];
    }

    public static function createTemplate(): object
    {
        return new class {
            private array $values = [];

            public function assign(string $name, $value): void
            {
                $this->values[$name] = $value;
            }

            public function fetch(): string
            {
                PluginFixture::$templates[] = $this->values;
                return '<article>Native output</article>';
            }
        };
    }

    public static function renderQuery(array $params, $template): string
    {
        foreach ($params as $value) {
            if (is_array($value)) {
                throw new RuntimeException('Pagination URL values must be strings');
            }
        }
        self::$paginationParams = $params;
        return '/tiki/Demo-Articles-Layouts?';
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
    public array $parentValues = ['article_list_layout' => 'overlay', 'article_list_columns' => '4'];

    public function __call(string $name, array $arguments)
    {
        if ($name === 'assign' && array_key_exists($arguments[0], $this->parentValues)) {
            $this->parentValues[$arguments[0]] = $arguments[1];
        }
        return match ($name) {
            'assign', 'assign_by_ref', 'getEmptyInternalTemplate' => null,
            'createTemplate' => PluginFixture::createTemplate(),
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
        if ($name === 'list_articles') {
            PluginFixture::$listedOffsets[] = $arguments[0];
        }
        return match ($name) {
            'list_topics', 'list_types_byname', 'getExtraSortColumns' => [],
            'list_articles' => ['data' => [], 'count' => 0],
            'getDefaultAdminLogin' => 'admin',
            default => throw new LogicException('Unexpected library method: ' . $name),
        };
    }
};
$header = new class {
    public array $files = [];

    public function __call(string $name, array $arguments)
    {
        if ($name !== 'add_jsfile') {
            throw new LogicException('Unexpected header method: ' . $name);
        }
        $this->files[] = $arguments[0];
    }
};
PluginFixture::$libraries = ['header' => $header, 'smarty' => $smarty, 'tiki' => $library, 'art' => $library, 'user' => $library, 'prefs' => $library];

// Load the real plugin into the fixture namespace, where only its dependencies are substituted.
// The source is a fixed repository file, never user input. No global Tiki classes are redeclared.
$source = file_get_contents(dirname(__DIR__) . '/lib/wiki-plugins/wikiplugin_articles.php');
if ($source === false || ! str_starts_with($source, '<?php')) {
    throw new RuntimeException('Cannot load the ARTICLES plugin');
}
$source = str_replace(
    '\\SmartyTiki\\FunctionHandler\\Query::render',
    '\\' . __NAMESPACE__ . '\\PluginFixture::renderQuery',
    $source
);
eval('namespace ' . __NAMESPACE__ . ';' . substr($source, 5));

$prefs = ['feature_articles' => 'y', 'feature_multilingual' => 'n', 'maxRecords' => 10];
$pageLang = 'de';
$info = wikiplugin_articles_info();
$layouts = ['classic', 'tiles', 'list', 'overlay', 'magazine', 'compact'];
if ($info['params']['layout']['default'] !== 'tiles') {
    throw new RuntimeException('Tiles must be the default plugin layout');
}
$values = array_column($info['params']['layout']['options'], 'value');
if ($values !== $layouts) {
    throw new RuntimeException('Editor layout choices are incomplete');
}
$params = [];
foreach ($info['params'] as $name => $definition) {
    $params[$name] = $definition['default'] ?? null;
}
$params['dateStart'] = $params['dateEnd'] = '';
foreach (array_merge($layouts, ['invalid', 'legacy', null]) as $layout) {
    foreach (['auto', '2', '3', '4', 'invalid', '" onclick="bad'] as $columns) {
        $header->files = [];
        $result = wikiplugin_articles('', array_replace($params, ['layout' => $layout, 'columns' => $columns]));
        if (! str_contains($result, '<article>Native output</article>')) {
            throw new RuntimeException('Native template output lost');
        }
        $scoped = end(PluginFixture::$templates);
        $expectedLayout = in_array($layout, $layouts, true) ? $layout : 'tiles';
        $expectedFiles = in_array($expectedLayout, ['tiles', 'overlay', 'magazine'], true)
            ? ['lib/jquery_tiki/article-cards.js'] : [];
        if ($header->files !== $expectedFiles) {
            throw new RuntimeException('Card enhancement must load only for visual layouts');
        }
        $expectedColumns = in_array($columns, ['auto', '2', '3', '4'], true) ? $columns : 'auto';
        if ($scoped !== ['article_list_layout' => $expectedLayout, 'article_list_columns' => $expectedColumns]) {
            throw new RuntimeException('Invalid or leaking child-template layout values');
        }
    }
}
if ($smarty->parentValues !== ['article_list_layout' => 'overlay', 'article_list_columns' => '4']) {
    throw new RuntimeException('Plugin instance changed parent template values');
}
$source = file_get_contents(dirname(__DIR__) . '/lib/prefs/article.php');
eval('namespace ' . __NAMESPACE__ . ';' . substr($source, 5));
$preferences = prefs_article_list();
if ($preferences['article_frontpage_layout']['default'] !== 'tiles') {
    throw new RuntimeException('Tiles must be the default native layout');
}
if (array_keys($preferences['article_frontpage_layout']['options']) !== $layouts) {
    throw new RuntimeException('Administration and plugin layout choices differ');
}
if (! str_contains(wikiplugin_articles('', array_replace($params, ['layout' => 'overlay', 'titleonly' => 'y'])), 'titles')) {
    throw new RuntimeException('Title-only compatibility lost');
}
foreach ([3 => 3, -3 => 0, 'invalid' => 0] as $requestedOffset => $expectedOffset) {
    $_REQUEST['offset'] = $requestedOffset;
    wikiplugin_articles('', array_replace($params, [
        'usePagination' => 'y', 'max' => 3, 'topicId' => ['1', '2'],
        'type' => ['Article', 'Review'], 'categId' => ['4', '9'],
    ]));
    if (end(PluginFixture::$listedOffsets) !== $expectedOffset) {
        throw new RuntimeException('Pagination offset must be a non-negative integer');
    }
    foreach (['topicId' => '1+2', 'type' => 'Article+Review', 'categId' => '4|9'] as $key => $value) {
        if (PluginFixture::$paginationParams[$key] !== $value) {
            throw new RuntimeException('Multi-value pagination filter was not serialized correctly');
        }
    }
}
unset($_REQUEST['offset']);
$prefs['feature_articles'] = 'n';
if (wikiplugin_articles('', $params) !== '') {
    throw new RuntimeException('Feature-disabled behavior changed');
}
if (class_exists('TikiLib', false) || class_exists('WikiPlugin_Helper', false)) {
    throw new RuntimeException('The fixture must not introduce global Tiki classes');
}


require dirname(__DIR__) . '/lib/core/Tiki/Articles/ArticleNavigation.php';
$navigation = new class {
    public array $batches = [];
    public array $calls = [];
    public array $visible = [];

    public function __call(string $name, array $args): array
    {
        if ($name === 'fetchAll') {
            $this->calls[] = $args;
            if ($args[2] !== 50) {
                throw new RuntimeException('Navigation must use bounded batches');
            }
            return array_shift($this->batches) ?? [];
        }
        if ($name === 'list_articles') {
            if ($args['maxRecords'] !== 50 || $args['lang'] !== 'de' || $args['ispublished'] !== 'y') {
                throw new RuntimeException('Native navigation filters missing');
            }
            return ['data' => array_values(array_filter(
                $this->visible,
                static fn($row) => in_array($row['articleId'], $args['filter']['articleId'])
            ))];
        }
        throw new LogicException('Unexpected navigation dependency');
    }
};
$article = ['articleId' => 100, 'publishDate' => 1000, 'lang' => 'de', 'ispublished' => 'y'];
$navigation->batches = [
    [['articleId' => 101, 'publishDate' => 1000], ['articleId' => 102, 'publishDate' => 1100]],
    [['articleId' => 99, 'publishDate' => 1000], ['articleId' => 98, 'publishDate' => 900]],
];
$navigation->visible = [
    ['articleId' => 101, 'title' => 'Restricted', 'disp_article' => 'y', 'perms' => ['tiki_p_read_article' => 'n']],
    ['articleId' => 102, 'title' => 'Newer', 'disp_article' => 'y', 'perms' => ['tiki_p_read_article' => 'y']],
    ['articleId' => 99, 'title' => 'Hidden', 'disp_article' => 'n', 'perms' => ['tiki_p_read_article' => 'y']],
    ['articleId' => 98, 'title' => 'Older', 'disp_article' => 'y', 'perms' => ['tiki_p_read_article' => 'y']],
];
$result = \Tiki\Articles\ArticleNavigation::forArticle($article, $navigation, 2000);
if ($result !== ['previous' => ['articleId' => 102, 'title' => 'Newer'], 'next' => ['articleId' => 98, 'title' => 'Older']]) {
    throw new RuntimeException('Navigation exposed restricted titles or selected the wrong neighbour');
}
if ($navigation->calls[0][1] !== ['de', 2000, 1000, 1000, 100]) {
    throw new RuntimeException('Navigation must preserve language and date/id cursor');
}
$navigation->calls = [];
$navigation->visible = [];
$navigation->batches = [array_fill(0, 50, ['articleId' => 101, 'publishDate' => 1100]), [], []];
if (
    \Tiki\Articles\ArticleNavigation::forArticle($article, $navigation, 2000) !== []
    || count($navigation->calls) !== 3 || $navigation->calls[1][1] !== ['de', 2000, 1100, 1100, 101]
) {
    throw new RuntimeException('Navigation did not advance past a restricted batch');
}
$navigation->calls = [];
foreach ([['ispublished' => 'n'], ['publishDate' => 3000]] as $change) {
    if (\Tiki\Articles\ArticleNavigation::forArticle(array_replace($article, $change), $navigation, 2000) !== []) {
        throw new RuntimeException('Draft or future article received navigation');
    }
}
if ($navigation->calls !== []) {
    throw new RuntimeException('Draft or future article caused a navigation query');
}
echo "PASS: six layouts, columns, isolated templates, admin choices, native compatibility, readable navigation and multi-filter pagination\n";
