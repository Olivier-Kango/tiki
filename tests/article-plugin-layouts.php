<?php
// Exercise the actual ARTICLES plugin without a database or full Tiki bootstrap.
function tra($text) { return $text; }
function tr($text, ...$args) { return $text; }
class ArticlePluginSmarty
{
    public function assign($name, $value) {}
    public function assign_by_ref($name, &$value) {}
    public function fetch($name) { return $name === 'tiki-view_articles-titleonly.tpl' ? 'titles' : '<article>Native output</article>'; }
}
class ArticlePluginLibrary
{
    public $now = 2000;
    public $modules = [];
    public function list_topics() { return []; }
    public function list_types_byname() { return []; }
    public function list_articles(...$args) { return ['data' => [], 'count' => 0]; }
    public function getDefaultAdminLogin() { return 'admin'; }
    public function add_js_module($script) { $this->modules[] = $script; }
}
class TikiLib
{
    public static $smarty;
    public static $library;
    public static function lib($name) { return $name === 'smarty' ? self::$smarty : self::$library; }
}
class WikiPlugin_Helper { public static function applySeparators($params, $info) { return $params; } }
require dirname(__DIR__) . '/lib/wiki-plugins/wikiplugin_articles.php';
TikiLib::$smarty = new ArticlePluginSmarty();
TikiLib::$library = new ArticlePluginLibrary();
$prefs = ['feature_articles' => 'y', 'feature_multilingual' => 'n', 'maxRecords' => 10];
$pageLang = 'de';
$info = wikiplugin_articles_info();
$values = array_column($info['params']['layout']['options'], 'value');
if ($values !== ['classic', 'tiles', 'list', 'overlay']) throw new RuntimeException('Four editor choices missing');
$params = [];
foreach ($info['params'] as $name => $definition) $params[$name] = $definition['default'] ?? null;
$params['dateStart'] = $params['dateEnd'] = '';
foreach (['classic', 'tiles', 'list', 'overlay', 'invalid', null] as $layout) {
    TikiLib::$library->modules = [];
    $result = wikiplugin_articles('', array_replace($params, ['layout' => $layout]));
    $enhanced = in_array($layout, ['tiles', 'list', 'overlay'], true);
    if (!str_contains($result, '<article>Native output</article>')) throw new RuntimeException('Native template output lost');
    if (str_contains($result, 'articles-layout') !== $enhanced) throw new RuntimeException('Incorrect layout wrapper');
    if ($enhanced && !str_contains($result, 'articles-layout-' . $layout)) throw new RuntimeException('Wrong selected layout');
    if (TikiLib::$library->modules !== []) throw new RuntimeException('Layouts must not load additional JavaScript');
}
if (!str_contains(wikiplugin_articles('', array_replace($params, ['layout' => 'overlay', 'titleonly' => 'y'])), 'titles')) throw new RuntimeException('Title-only compatibility lost');
$prefs['feature_articles'] = 'n';
if (wikiplugin_articles('', $params) !== '') throw new RuntimeException('Feature-disabled behavior changed');
echo "PASS: four plugin choices, selection, default/invalid fallback, native rendering, title-only and disabled feature\n";
