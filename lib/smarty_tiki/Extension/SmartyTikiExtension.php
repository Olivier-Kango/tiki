<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Extension;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\FunctionHandler\FunctionHandlerInterface;
use Smarty\Compile\CompilerInterface;
use Tiki\Composer\SmartyExtensionMapper;
use TikiLib;

class SmartyTikiExtension extends \Smarty\Extension\Base
{
    private $blockHandlers = [];
    private $functionHandlers = [];
    private $outputFilters = [];
    private $preFilters = [];
    private $tags = [];
    private array $extensionMap = [];

    public function __construct()
    {
        $mapFile = SmartyExtensionMapper::SMARTY_MAP_FILE;
        if (file_exists($mapFile)) {
            $map = require $mapFile;
            if (is_array($map)) {
                $this->extensionMap = $map;
            }
        }
    }

    public function getTagCompiler(string $tag): ?CompilerInterface
    {
        if (isset($this->tags[$tag])) {
            return $this->tags[$tag];
        }

        // Mapping lookup
        if (isset($this->extensionMap['tag_compilers'][$tag])) {
            $class = $this->extensionMap['tag_compilers'][$tag];
            $this->tags[$tag] = new $class();
            return $this->tags[$tag];
        }

        // Fallback: switch-case
        switch ($tag) {
            case 'assign_content':
                $this->tags[$tag] = new \SmartyTiki\Compile\Tag\AssignContent();
                break;
        }

        return $this->tags[$tag] ?? null;
    }

    public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface
    {
        // Mapping lookup
        if (isset($this->extensionMap['modifier_compilers'][$modifier])) {
            $class = $this->extensionMap['modifier_compilers'][$modifier];
            return new $class();
        }

        // Fallback: hardcoded
        if ($modifier === 'escape') {
            return new \SmartyTiki\Compile\Modifier\EscapeModifierCompiler();
        } else {
            // let DefaultExtension handle the rest
            return null;
        }
    }

    public function getModifierCallback(string $modifierName)
    {
        // No TikiLib outside a full Tiki bootstrap, see SecurityPolicy::isTrustedModifier()
        if (class_exists('TikiLib') && ($tikilib = TikiLib::lib('tiki'))) {
            $allowed_builtin_php_functions = array_filter($tikilib->get_preference('smarty_security_allowed_builtin_php_functions', [], true));
            if (in_array($modifierName, $allowed_builtin_php_functions) && is_callable($modifierName)) {
                return function (...$args) use ($modifierName) {
                    return call_user_func_array($modifierName, $args);
                };
            }
        }

        // Mapping lookup for modifier classes
        if (isset($this->extensionMap['modifiers'][$modifierName])) {
            $class = $this->extensionMap['modifiers'][$modifierName];
            return [new $class(), 'handle'];
        }

        // Fallback: switch-case
        switch ($modifierName) {
            case 'a_or_an':
                return [new \SmartyTiki\Modifier\AorAn(), 'handle'];
            case 'addslashes':
                return [new \SmartyTiki\Modifier\Addslashes(), 'handle'];
            case 'adjust':
                return [new \SmartyTiki\Modifier\Adjust(), 'handle'];
            case 'array_reverse':
                return [new \SmartyTiki\Modifier\ArrayReverse(), 'handle'];
            case 'array_key_exists':
                return [new \SmartyTiki\Modifier\ArrayKeyExists(), 'handle'];
            case 'avatarize':
                return [new \SmartyTiki\Modifier\Avatarize(), 'handle'];
            case 'breakline':
                return [new \SmartyTiki\Modifier\Breakline(), 'handle'];
            case 'categid':
                return [new \SmartyTiki\Modifier\CategId(), 'handle'];
            case 'compactisodate':
                return [new \SmartyTiki\Modifier\CompactIsoDate(), 'handle'];
            case 'countryflag':
                return [new \SmartyTiki\Modifier\CountryFlag(), 'handle'];
            case 'countryflagwithlabel':
                return [new \SmartyTiki\Modifier\CountryFlagWithLabel(), 'handle'];
            case 'countryflagemoji':
                return [new \SmartyTiki\Modifier\CountryFlagEmoji(), 'handle'];
            case 'count':
                return [new \SmartyTiki\Modifier\Count(), 'handle'];
            case 'd':
                return [new \SmartyTiki\Modifier\D(), 'handle'];
            case 'dbg':
                return [new \SmartyTiki\Modifier\Dbg(), 'handle'];
            case 'div':
                return [new \SmartyTiki\Modifier\Div(), 'handle'];
            case 'duration_short':
                return [new \SmartyTiki\Modifier\DurationShort(), 'handle'];
            case 'debug_print_tree':
                return [new \SmartyTiki\Modifier\DebugPrintTree(), 'handle'];
            case 'duration':
                return [new \SmartyTiki\Modifier\Duration(), 'handle'];
            case 'escape':
                return [new \SmartyTiki\Modifier\Escape(), 'handle'];
            case 'file_can_convert_to_pdf':
                return [new \SmartyTiki\Modifier\FileCanConvertToPdf(), 'handle'];
            case 'file_diagram':
                return [new \SmartyTiki\Modifier\FileDiagram(), 'handle'];
            case 'forummaskemail':
                return [new \SmartyTiki\Modifier\ForumMaskEmail(), 'handle'];
            case 'forumname':
                return [new \SmartyTiki\Modifier\ForumName(), 'handle'];
            case 'forumtopiccount':
                return [new \SmartyTiki\Modifier\ForumTopicCount(), 'handle'];
            case 'groupmembercount':
                return [new \SmartyTiki\Modifier\GroupMemberCount(), 'handle'];
            case 'how_many_user_inscriptions':
                return [new \SmartyTiki\Modifier\HowManyUserInscriptions(), 'handle'];
            case 'htmldecode':
                return [new \SmartyTiki\Modifier\HtmlDecode(), 'handle'];
            case 'iconify':
                return [new \SmartyTiki\Modifier\Iconify(), 'handle'];
            case 'in_group':
                return [new \SmartyTiki\Modifier\InGroup(), 'handle'];
            case 'is_array':
                return [new \SmartyTiki\Modifier\IsArray(), 'handle'];
            case 'is_numeric':
                return [new \SmartyTiki\Modifier\IsNumeric(), 'handle'];
            case 'isodate':
                return [new \SmartyTiki\Modifier\IsoDate(), 'handle'];
            case 'json_decode':
                return [new \SmartyTiki\Modifier\JsonDecode(), 'handle'];
            case 'kbsize':
                return [new \SmartyTiki\Modifier\KbSize(), 'handle'];
            case 'langname':
                return [new \SmartyTiki\Modifier\LangName(), 'handle'];
            case 'lcfirst':
                return [new \SmartyTiki\Modifier\Lcfirst(), 'handle'];
            case 'max':
                return [new \SmartyTiki\Modifier\Max(), 'handle'];
            case 'max_user_inscriptions':
                return [new \SmartyTiki\Modifier\MaxUserInscriptions(), 'handle'];
            case 'md5':
                return [new \SmartyTiki\Modifier\Md5(), 'handle'];
            case 'money_format':
                return [new \SmartyTiki\Modifier\MoneyFormat(), 'handle'];
            case 'namespace':
                return [new \SmartyTiki\Modifier\NamespaceModifier(), 'handle'];
            case 'nonamespace':
                return [new \SmartyTiki\Modifier\NoNamespace(), 'handle'];
            case 'nonp':
                return [new \SmartyTiki\Modifier\Nonp(), 'handle'];
            case 'number_format':
                return [new \SmartyTiki\Modifier\NumberFormat(), 'handle'];
            case 'numStyle':
                return [new \SmartyTiki\Modifier\NumStyle(), 'handle'];
            case 'output':
                return [new \SmartyTiki\Modifier\Output(), 'handle'];
            case 'packageitemid':
                return [new \SmartyTiki\Modifier\PackageItemId(), 'handle'];
            case 'pagename':
                return [new \SmartyTiki\Modifier\PageName(), 'handle'];
            case 'parse':
                return [new \SmartyTiki\Modifier\Parse(), 'handle'];
            case 'percent':
                return [new \SmartyTiki\Modifier\Percent(), 'handle'];
            case 'preg_match':
                return [new \SmartyTiki\Modifier\PregMatch(), 'handle'];
            case 'preg_match_all':
                return [new \SmartyTiki\Modifier\PregMatchAll(), 'handle'];
            case 'quoted':
                return [new \SmartyTiki\Modifier\Quoted(), 'handle'];
            case 'replacei':
                return [new \SmartyTiki\Modifier\Replacei(), 'handle'];
            case 'reverse_array':
                return [new \SmartyTiki\Modifier\ReverseArray(), 'handle'];
            case 'sefurl':
                return [new \SmartyTiki\Modifier\Sefurl(), 'handle'];
            case 'sizeof':
                return [new \SmartyTiki\Modifier\Sizeof(), 'handle'];
            case 'slug':
                return [new \SmartyTiki\Modifier\Slug(), 'handle'];
            case 'star':
                return [new \SmartyTiki\Modifier\Star(), 'handle'];
            case 'stringfix':
                return [new \SmartyTiki\Modifier\StringFix(), 'handle'];
            case 'stristr':
                return [new \SmartyTiki\Modifier\Stristr(), 'handle'];
            case 'strstr':
                return [new \SmartyTiki\Modifier\Strstr(), 'handle'];
            case 'strpos':
                return [new \SmartyTiki\Modifier\Strpos(), 'handle'];
            case 'strtolower':
                return [new \SmartyTiki\Modifier\Strtolower(), 'handle'];
            case 'substring':
                return [new \SmartyTiki\Modifier\Substring(), 'handle'];
            case 'tasklink':
                return [new \SmartyTiki\Modifier\TaskLink(), 'handle'];
            case 'template':
                return [new \SmartyTiki\Modifier\Template(), 'handle'];
            case 'ternary':
                return [new \SmartyTiki\Modifier\Ternary(), 'handle'];
            case 'tiki_date_format':
                return [new \SmartyTiki\Modifier\TikiDateFormat(), 'handle'];
            case 'tiki_date_timezone_from_unix':
                return [new \SmartyTiki\Modifier\TikiDateTimezoneFromUnix(), 'handle'];
            case 'strtotime':
                return [new \SmartyTiki\Modifier\Strtotime(), 'handle'];
            case 'tiki_long_date':
                return [new \SmartyTiki\Modifier\TikiLongDate(), 'handle'];
            case 'tiki_long_datetime':
                return [new \SmartyTiki\Modifier\TikiLongDateTime(), 'handle'];
            case 'tiki_long_time':
                return [new \SmartyTiki\Modifier\TikiLongTime(), 'handle'];
            case 'tiki_remaining_days_from_now':
                return [new \SmartyTiki\Modifier\TikiRemainingDaysFromNow(), 'handle'];
            case 'tiki_short_date':
                return [new \SmartyTiki\Modifier\TikiShortDate(), 'handle'];
            case 'tiki_short_datetime':
                return [new \SmartyTiki\Modifier\TikiShortDateTime(), 'handle'];
            case 'tiki_short_time':
                return [new \SmartyTiki\Modifier\TikiShortTime(), 'handle'];
            case 'times':
                return [new \SmartyTiki\Modifier\Times(), 'handle'];
            case 'trim':
                return [new \SmartyTiki\Modifier\Trim(), 'handle'];
            case 'tra':
                return [new \SmartyTiki\Modifier\Tra(), 'handle'];
            case 'truncate':
                return [new \SmartyTiki\Modifier\Truncate(), 'handle'];
            case 'truex':
                return [new \SmartyTiki\Modifier\Truex(), 'handle'];
            case 'tr_if':
                return [new \SmartyTiki\Modifier\TrIf(), 'handle'];
            case 'ucfirst':
                return [new \SmartyTiki\Modifier\Ucfirst(), 'handle'];
            case 'ucwords':
                return [new \SmartyTiki\Modifier\Ucwords(), 'handle'];
            case 'urlencode':
                return [new \SmartyTiki\Modifier\Urlencode(), 'handle'];
            case 'userlink':
                return [new \SmartyTiki\Modifier\UserLink(), 'handle'];
            case 'username':
                return [new \SmartyTiki\Modifier\Username(), 'handle'];
            case 'utf8unicode':
                return [new \SmartyTiki\Modifier\Utf8Unicode(), 'handle'];
            case 'var_dump':
                return [new \SmartyTiki\Modifier\VarDump(), 'handle'];
            case 'virtual_path':
                return [new \SmartyTiki\Modifier\VirtualPath(), 'handle'];
            case 'yesno':
                return [new \SmartyTiki\Modifier\YesNo(), 'handle'];
            case 'zone_is_empty':
                return [new \SmartyTiki\Modifier\ZoneIsEmpty(), 'handle'];
            case 'safe_html':
                return [new \SmartyTiki\Modifier\SafeHtml(), 'handle'];
        }
        return null;
    }

    public function getBlockHandler(string $blockTagName): ?BlockHandlerInterface
    {
        if (isset($this->blockHandlers[$blockTagName])) {
            return $this->blockHandlers[$blockTagName];
        }

        // Mapping lookup
        if (isset($this->extensionMap['block_handlers'][$blockTagName])) {
            $class = $this->extensionMap['block_handlers'][$blockTagName];
            $this->blockHandlers[$blockTagName] = new $class();
            return $this->blockHandlers[$blockTagName];
        }

        // Fallback: switch-case
        switch ($blockTagName) {
            case 'accordion_group':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AccordionGroup();
                break;
            case 'accordion':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Accordion();
                break;
            case 'actions':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Actions();
                break;
            case 'activityframe':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ActivityFrame();
                break;
            case 'ajax_href':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AjaxHref();
                break;
            case 'compact':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Compact();
                break;
            case 'display':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Display();
                break;
            case 'filter':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Filter();
                break;
            case 'ifsearchexists':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\IfSearchExists();
                break;
            case 'ifsearchnotexists':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\IfSearchNotExists();
                break;
            case 'itemfield':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ItemField();
                break;
            case 'jq':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Jq();
                break;
            case 'mailurl':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\MailUrl();
                break;
            case 'modules_list':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ModulesList();
                break;
            case 'packageplugin':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PackagePlugin();
                break;
            case 'pagination_links':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PaginationLinks();
                break;
            case 'permission':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Permission();
                break;
            case 'popup_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PopupLink();
                break;
            case 'repeat':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Repeat();
                break;
            case 'sortlinks':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SortLinks();
                break;
            case 'self_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SelfLink();
                break;
            case 'remarksbox':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Remarksbox();
                break;
            case 'tab':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tab();
                break;
            case 'tabset':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tabset();
                break;
            case 'textarea':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TextArea();
                break;
            case 'tikimodule':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TikiModule();
                break;
            case 'title':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Title();
                break;
            case 'tr':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tr();
                break;
            case 'trackeritemcheck':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TrackerItemCheck();
                break;
            case 'translation':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Translation();
                break;
            case 'vue':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Vue();
                break;
            case 'wiki':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Wiki();
                break;
            case 'wikiplugin':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Wikiplugin();
                break;
        }

        return $this->blockHandlers[$blockTagName] ?? null;
    }

    public function getFunctionHandler(string $functionName): ?FunctionHandlerInterface
    {
        if (isset($this->functionHandlers[$functionName])) {
            return $this->functionHandlers[$functionName];
        }

        // Mapping lookup
        if (isset($this->extensionMap['function_handlers'][$functionName])) {
            $class = $this->extensionMap['function_handlers'][$functionName];
            $this->functionHandlers[$functionName] = new $class();
            return $this->functionHandlers[$functionName];
        }

        // Fallback: switch-case
        switch ($functionName) {
            case 'activity':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Activity();
                break;
            case 'article':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Article();
                break;
            case 'attachments':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Attachments();
                break;
            case 'autocomplete':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Autocomplete();
                break;
            case 'banner':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Banner();
                break;
            case 'breadcrumbs':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Breadcrumbs();
                break;
            case 'bootstrap_modal':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\BootstrapModal();
                break;
            case 'button':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Button();
                break;
            case 'categoryName':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CategoryName();
                break;
            case 'categoryselector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CategorySelector();
                break;
            case 'content':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Content();
                break;
            case 'cookie_jar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CookieJar();
                break;
            case 'cookie':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Cookie();
                break;
            case 'count':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Count();
                break;
            case 'currency':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Currency();
                break;
            case 'custom_template':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CustomTemplate();
                break;
            case 'datetime_range':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DatetimeRange();
                break;
            case 'debugger':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Debugger();
                break;
            case 'defaultmapcenter':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DefaultMapCenter();
                break;
            case 'ed':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ed();
                break;
            case 'elapsed':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Elapsed();
                break;
            case 'favorite':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Favorite();
                break;
            case 'feedback':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Feedback();
                break;
            case 'queued_tasks_banner':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\QueuedTasksBanner();
                break;
            case 'fgal_browse':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FgalBrowse();
                break;
            case 'file_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileSelector();
                break;
            case 'filegal_manager_url':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileGalManagerUrl();
                break;
            case 'fileinfo':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileInfo();
                break;
            case 'formitem':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FormItem();
                break;
            case 'help':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Help();
                break;
            case 'html_body_attributes':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlBodyAttributes();
                break;
            case 'html_select_date':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectDate();
                break;
            case 'html_select_duration':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectDuration();
                break;
            case 'html_select_time':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectTime();
                break;
            case 'icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Icon();
                break;
            case 'inline_audio_player':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InlineAudioPlayer();
                break;
            case 'initials_filter_links':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InitialsFilterLinks();
                break;
            case 'interactivetranslation':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InteractiveTranslation();
                break;
            case 'js_insert_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsInsertIcon();
                break;
            case 'js_maxlength':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsMaxLength();
                break;
            case 'jscalendar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsCalendar();
                break;
            case 'jstransfer_list':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsTransferList();
                break;
            case 'jspopup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsPopup();
                break;
            case 'like':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Like();
                break;
            case 'listfilter':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ListFilter();
                break;
            case 'lock':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Lock();
                break;
            case 'memusage':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MemUsage();
                break;
            case 'menu':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Menu();
                break;
            case 'module':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Module();
                break;
            case 'modulelist':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ModuleList();
                break;
            case 'monitor_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MonitorLink();
                break;
            case 'multilike':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MultiLike();
                break;
            case 'norecords':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\NoRecords();
                break;
            case 'notification_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\NotificationLink();
                break;
            case 'obj_in_cat':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjInCat();
                break;
            case 'object_title':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectTitle();
                break;
            case 'object_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectLink();
                break;
            case 'object_score':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectScore();
                break;
            case 'object_selector_multi':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectSelectorMulti();
                break;
            case 'object_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectSelector();
                break;
            case 'object_type':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectType();
                break;
            case 'page_alias':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PageAlias();
                break;
            case 'page_in_structure':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PageInStructure();
                break;
            case 'payment':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Payment();
                break;
            case 'show_database_query_log':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DatabaseQueryLog();
                break;
            case 'permission_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PermissionLink();
                break;
            case 'pluralize':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Pluralize();
                break;
            case 'poll':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Poll();
                break;
            case 'popup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Popup();
                break;
            case 'preference':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Preference();
                break;
            case 'profilesymbolvalue':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ProfileSymbolValue();
                break;
            case 'query':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Query();
                break;
            case 'quotabar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Quotabar();
                break;
            case 'rating':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rating();
                break;
            case 'rating_choice':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingChoice();
                break;
            case 'rating_override_menu':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingOverrideMenu();
                break;
            case 'rating_result_avg':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingResultAvg();
                break;
            case 'rating_result':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingResult();
                break;
            case 'rcontent':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rcontent();
                break;
            case 'redirect':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Redirect();
                break;
            case 'reindex_file_pixel':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ReindexFilePixel();
                break;
            case 'router_params':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RouterParams();
                break;
            case 'rss':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rss();
                break;
            case 'sameurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SameUrl();
                break;
            case 'scheduler_params':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SchedulerParams();
                break;
            case 'sefurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Sefurl();
                break;
            case 'select_all':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SelectAll();
                break;
            case 'service_inline':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ServiceInline();
                break;
            case 'service':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Service();
                break;
            case 'set':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Set();
                break;
            case 'show_sort':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ShowSort();
                break;
            case 'syntax':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Syntax();
                break;
            case 'thumb':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Thumb();
                break;
            case 'ticket':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ticket();
                break;
            case 'toolbars':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Toolbars();
                break;
            case 'tracker_item_status_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerItemStatusIcon();
                break;
            case 'trackerfields':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerFields();
                break;
            case 'trackerheader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerHeader();
                break;
            case 'trackerinput':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerInput();
                break;
            case 'trackeroutput':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerOutput();
                break;
            case 'trackerrules':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerRules();
                break;
            case 'treetable':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TreeTable();
                break;
            case 'user_registration':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\UserRegistration();
                break;
            case 'user_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\UserSelector();
                break;
            case 'var_dump':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\VarDump();
                break;
                break;
            case 'wikidiff':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\WikiDiff();
                break;
            case 'wikistructure':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\WikiStructure();
                break;
        }
        return $this->functionHandlers[$functionName] ?? null;
    }

    public function getOutputFilters(): array
    {
        if (isset($_REQUEST['highlight']) || (isset($prefs['feature_referer_highlight']) && $prefs['feature_referer_highlight'] == 'y')) {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Highlight();
        }

        if (! empty($prefs['feature_sefurl_filter']) && $prefs['feature_sefurl_filter'] === 'y') {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Sefurl();
        }

        return $this->outputFilters;
    }

    public function getPreFilters(): array
    {
        global $prefs;

        $this->preFilters = [
            new \SmartyTiki\Filter\Pre\Tr(),
            new \SmartyTiki\Filter\Pre\Jq()
        ];

        if (! empty($prefs['log_tpl']) && $prefs['log_tpl'] === 'y') {
            $this->preFilters[] = new \SmartyTiki\Filter\Pre\LogTpl();
        }

        return $this->preFilters;
    }
}
