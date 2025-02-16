<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\ComposerManager;

class PreferencesLib
{
    private const DEFAULT_HIDDEN_PREFERENCES = [
        'feature_editcss',
        'feature_edit_templates',
        'feature_purifier',
        'smarty_security_dirs',
        'tiki_allow_trust_input',
        'feature_create_webhelp',
    ];

    private $data = [];
    private $usageArray;
    private $file = '';
    private $files = [];
    // Fake preferences controlled by the system
    private $system_modified = [ 'tiki_release', 'tiki_version_last_check'];
    // prefs with system info etc
    private $system_info = [ 'fgal_use_dir', 'sender_email' ];

    /**
     * Returns a list of preferences that can be translated
     *
     * @return array List of preferences
     */
    public function getTranslatablePreferences()
    {
        global $prefs;

        // Due to performance reasons and the small list of preferences to be translated, returned the current
        // list of translatable preferences as hardcoded list instead of dynamically searching all preferences
        /*
        $translatablePreferences = [];
        foreach ($prefs as $key => $val) {
            $definition = $this->getPreference($key);
            if ($definition['translatable'] === true) {
                $translatablePreferences[] = $key;
            }
        }
        */

        $translatablePreferences = [
            'browsertitle',
            'metatag_keywords',
            'metatag_description',
        ];

        return $translatablePreferences;
    }

    /**
     * Set the translated value for a given preference
     *
     * @param string $pref preference to translate
     * @param string $lang target language
     * @param string $val value for the preference
     * @param string $defaultLanguage the default language
     */
    public function setTranslatedPreference($pref, $lang, $val, $defaultLanguage)
    {
        $tikiLib = TikiLib::lib('tiki');
        if ($lang != $defaultLanguage) {
            $pref .= "_" . $lang;
        }

        if (empty($val)) {
            $tikiLib->delete_preference($pref);
        } else {
            $tikiLib->set_preference($pref, $val);
        }
    }

    /**
     * Retrieve a translated preference, in a given language, or the default if not set
     *
     * @param string $name preference name
     * @param string $lang language to retrieve
     * @return string translated preference with fallback for the default preference or empty string
     * @throws Exception
     */
    public function getTranslatedPreference($name, $lang)
    {
        global $prefs;

        $translatedPreference = $name;
        if ($prefs['site_language'] != $lang) {
            $translatedPreference .= '_' . $lang;
        }

        if (isset($prefs[$translatedPreference])) {
            return $prefs[$translatedPreference];
        }

        return '';
    }

    public function getPreference($name, $deps = true, $source = null, $get_pages = false)
    {
        global $prefs, $systemConfiguration;
        static $id = 0;
        $data = $this->loadData($name);

        if (! isset($data[$name])) {
            return false;
        }
        $defaults = [
            'type' => '',
            'helpurl' => '',
            'help' => '',
            'adminurl' => 'tiki-admin.php?lm_criteria=' . urlencode($name) . '&amp;exact',
            'dependencies' => [],
            'conflicts' => [],
            'packages_required' => [],
            'extensions' => [],
            'dbfeatures' => [],
            'options' => [],
            'description' => '',
            'size' => 40,
            'detail' => '',
            'warning' => '',
            'hint' => '',
            'shorthint' => '',
            'perspective' => true,
            'parameters' => [],
            'admin' => '',
            'module' => '',
            'permission' => '',
            'plugin' => '',
            'view' => '',
            'public' => false,
            'translatable' => false,
        ];
        if (isset($data[$name]['type']) && $data[$name]['type'] === 'textarea') {
            $defaults['size'] = 10;
        }

        $info = array_merge($defaults, $data[$name]);

        if ($source == null) {
            $source = $prefs;
        }

        $value = isset($source[$name]) ? $source[$name] : null;
        if (
            ! empty($value) &&
            is_string($value) &&
            (strlen($value) > 1 && $value[1] == ':' && strpos($value, '{') !== false) &&
            false !== $unserialized = @unserialize($value)
        ) {
            $value = $unserialized;
        }

        $info['preference'] = $name;
        if (isset($info['serialize'])) {
            $fnc = $info['serialize'];
            $info['value'] = $fnc($value);
        } else {
            $info['value'] = $value;
        }

        if (! isset($info['tags'])) {
            $info['tags'] = ['advanced'];
        }

        $info['tags'][] = $name;
        $info['tags'][] = 'all';

        if ($this->checkPreferenceState($info['tags'], 'hide')) {
            return ['hide' => true];
        }

        $info['notes'] = [];

        $info['raw'] = isset($source[$name]) ? $source[$name] : null;
        $info['id'] = 'pref-' . ++$id;

        if (! empty($info['help']) && isset($prefs['feature_help']) && $prefs['feature_help'] == 'y') {
            if (preg_match('/^https?:/i', $info['help'])) {
                $info['helpurl'] = $info['help'];
            } else {
                $info['helpurl'] = $prefs['helpurl'] . $info['help'];
            }
        }

        $info['available'] = true;

        /* FIXME: Dependencies are not enforced currently. TODO: Activate disabled code below to enforce dependencies
        // The value element is deprecated. Use either "configuredValue" or "effectiveValue"  instead.
        $info['configuredValue'] = $info['effectiveValue'] = $info['value'];
        */
        if ($deps) {
            if (isset($info['dependencies'])) {
                $info['dependencies'] = $this->getDependencies($info['dependencies']);
            }
            if (isset($info['conflicts'])) {
                $info['conflicts'] = $this->getConflicts($info['conflicts']);
                if (count($info['conflicts']['active']) && $info['value'] != 'y') {
                    $info['available'] = false;
                }
            }
            /* TODO: test
            if ($info['type'] == 'flag' &&
                $info['effectiveValue'] = 'y' && // Optimization
                    array_filter(array_column($info['dependencies'], 'met'), function($boolean) {
                        return ! $boolean;
                    })) {
                $info['effectiveValue'] = 'n';
            }
            */
        }

        if ($deps && isset($info['packages_required']) && ! empty($info['packages_required'])) {
            $info['packages_required'] = $this->getPackagesRequired($info['packages_required']);
        }

        if (! $this->checkExtensions($info['extensions'])) {
            $info['available'] = false;
            $info['notes'][] = tr('Unmatched system requirement. Missing PHP extension among %0', implode(', ', $info['extensions']));
        }

        if (! $this->checkDatabaseFeatures($info['dbfeatures'])) {
            $info['available'] = false;
            $info['notes'][] = tr('Unmatched system requirement. The database you are using does not support this feature.');
        }

        if (! isset($info['default'])) {    // missing default in prefs definition file?
            $info['modified'] = false;
            trigger_error(tr('Missing default for preference "%0"', $name), E_USER_WARNING);
        } else {
            if (is_string($info['default'])) {
                $info['modified'] = str_replace("\r\n", "\n", $info['value'] ?? '') != $info['default'];
            } else {
                $info['modified'] = false;
            }
        }

        if ($get_pages) {
            $info['pages'] = $this->getPreferenceLocations($name);
        }

        if (isset($systemConfiguration->preference->$name)) {
            $info['available'] = false;
            $info['notes'][] = tr('Configuration forced by host.');
        }

        if ($this->checkPreferenceState($info['tags'], 'deny')) {
            $info['available'] = false;
            $info['notes'][] = tr('Disabled by host.');
        }

        if (! $info['available']) {
            $info['tags'][] = 'unavailable';
        }

        if ($info['modified'] && $info['available']) {
            $info['tags'][] = 'modified';
        }

        $info['tagstring'] = implode(' ', $info['tags']);

        $info = array_merge($defaults, $info);

        if (! empty($info['permission'])) {
            if (! isset($info['permission']['show_disabled_features'])) {
                $info['permission']['show_disabled_features'] = 'y';
            }
            $info['permission'] = 'tiki-objectpermissions.php?' . http_build_query($info['permission'], '', '&');
        }

        if (! empty($info['admin'])) {
            if (preg_match('/^\w+$/', $info['admin'])) {
                $info['admin'] = 'tiki-admin.php?page=' . urlencode($info['admin']);
            }
        }

        if (! empty($info['module'])) {
            $info['module'] = 'tiki-admin_modules.php?cookietab=3&textFilter=' . urlencode($info['module']);
        }

        if (! empty($info['plugin'])) {
            $info['plugin'] = 'tiki-admin.php?page=textarea&amp;cookietab=2&textFilter=' . urlencode($info['plugin']);
        }

        $smarty = TikiLib::lib('smarty');

        if (! empty($info['admin']) || ! empty($info['permission']) || ! empty($info['view']) || ! empty($info['module']) || ! empty($info['plugin'])) {
            $info['popup_html'] = '<ul class="list-unstyled">';

            if (! empty($info['admin'])) {
                $icon = smarty_function_icon([ 'name' => 'settings'], $smarty->getEmptyInternalTemplate());
                $info['popup_html'] .= '<li><a class="icon" href="' . $info['admin'] . '">' . $icon . ' ' . tra('Settings') . '</a></li>';
            }
            if (! empty($info['permission'])) {
                $icon = smarty_function_icon([ 'name' => 'permission'], $smarty->getEmptyInternalTemplate());
                $info['popup_html'] .= '<li><a class="icon" href="' . $info['permission'] . '">' . $icon . ' ' . tra('Permissions') . '</a></li>';
            }
            if (! empty($info['view'])) {
                $icon = smarty_function_icon([ 'name' => 'view'], $smarty->getEmptyInternalTemplate());
                $info['popup_html'] .= '<li><a class="icon" href="' . $info['view'] . '">' . $icon . ' ' . tra('View') . '</a></li>';
            }
            if (! empty($info['module'])) {
                $icon = smarty_function_icon([ 'name' => 'module'], $smarty->getEmptyInternalTemplate());
                $info['popup_html'] .= '<li><a class="icon" href="' . $info['module'] . '">' . $icon . ' ' . tra('Modules') . '</a></li>';
            }
            if (! empty($info['plugin'])) {
                $icon = smarty_function_icon([ 'name' => 'plugin'], $smarty->getEmptyInternalTemplate());
                $info['popup_html'] .= '<li><a class="icon" href="' . $info['plugin'] . '">' . $icon . ' ' . tra('Plugins') . '</a></li>';
            }
            $info['popup_html'] .= '</ul>';
        }

        if (isset($prefs['connect_feature']) && $prefs['connect_feature'] === 'y') {
            $connectlib = TikiLib::lib('connect');
            $currentVote = $connectlib->getVote($info['preference']);

            $info['voting_html'] = '';

            if (! in_array('like', $currentVote)) {
                $info['voting_html'] .= smarty_function_icon($this->getVoteIconParams($info['preference'], 'like', tra('Like')), $smarty->getEmptyInternalTemplate());
            } else {
                $info['voting_html'] .= smarty_function_icon($this->getVoteIconParams($info['preference'], 'unlike', tra("Don't like")), $smarty->getEmptyInternalTemplate());
            }
//              if (!in_array('fix', $currentVote)) {
//                  $info['voting_html'] .= smarty_function_icon($this->getVoteIconParams($info['preference'], 'fix', tra('Fix me')), $smarty);
//              } else {
//                  $info['voting_html'] .= smarty_function_icon($this->getVoteIconParams($info['preference'], 'unfix', tra("Don't fix me")), $smarty);
//              }
//              if (!in_array('wtf', $currentVote)) {
//                  $info['voting_html'] .= smarty_function_icon($this->getVoteIconParams($info['preference'], 'wtf', tra("What's this for?")), $smarty);
//              } else {
//                  $info['voting_html'] .= smarty_function_icon($this->getVoteIconParams($info['preference'], 'unwtf', tra("What's this for?")), $smarty);
//              }
        }

        if (! $info['available']) {
            $info['parameters']['disabled'] = 'disabled';
        }

        $info['params'] = '';
        if (! empty($info['parameters'])) {
            foreach ($info['parameters'] as $param => $value) {
                $info['params'] .= ' ' . $param . '="' . $value . '"';
            }
        }

        /**
         * If the unified index is enabled, replace simple object selection preferences with object selectors
         */
        if (! empty($info['profile_reference']) && $prefs['feature_search'] == 'y') {
            $objectlib = TikiLib::lib('object');
            $type = $objectlib->getSelectorType($info['profile_reference']);

            if ($type) {
                $info['selector_type'] = $type;

                if (empty($info['separator'])) {
                    $info['type'] = 'selector';
                } else {
                    $info['type'] = 'multiselector';
                }
            }
        }

        foreach (['name', 'preference'] as $key) {
            if (empty($info[$key])) {
                trigger_error(tr('Missing preference "%0" for "%1"', $key, $name));
            }
        }

        return $info;
    }

    /**
     * retrieve all orphan preferences
     *
     * @return array
     */
    public function getOrphanPrefs()
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $langLib = TikiLib::lib('language');
        $data = $tikilib->table('tiki_preferences');
        $langMap = $langLib->get_language_map();
        $preferences = $data->fetchAll();
        $orphanPrefs = [];

        $specialPrefs = $this->getSpecialPrefs();
        foreach ($preferences as $pref) {
            $definition = $this->getPreference($pref['name'], true, $prefs);

            // Check if it is a translated preference
            if (! $definition) {
                $parts = explode("_", $pref['name']);
                if (! empty($parts)) {
                    $last = array_pop($parts);
                    if (isset($langMap[$last])) {
                        $prefName = implode("_", $parts);
                        $definition = $this->getPreference($prefName, true, $prefs);
                    }
                }
            }

            if (! $definition && ! $this->isSpecialPref($pref['name'], $specialPrefs)) {
                $orphanPrefs[] = $pref;
            }
        }
        return $orphanPrefs;
    }

    /**
     * Special preferences are prefs that are declared and used directly in the code
     * while they are not found anywhere in the admin panel
     *
     * This function retrieves all special prefs
     *
     * @return array
     */
    public function getSpecialPrefs()
    {
        global $prefs;
        $specialPrefs = [
            'webcron_last_run',
            'pass_auto_blacklist',
            'feature_contribution_mandatory',
            'feature_contribution_mandatory_forum',
            'feature_contribution_mandatory_comment',
            'feature_contribution_mandatory_blog',
            'feature_contribution_display_in_commen',
            'feature_contributor_wiki',
            'mailin_autocheck',
            'mailin_autocheckFreq',
            'shoutbox_autolink',
            'display_timezone',
            'internal_site_hash',
            'notified_tiki_version',
            'unified_field_mapping',
            'unified_date_fields',
            'h5p_cron_last_run',
            'toolbar_custom_list',
            'pluginaliaslist',
            'unified_manticore_index_rebuilding',
            'unified_date_fields',
            'unified_total_fields',
            'unified_field_count',
            'unified_last_rebuild',
            'mailin_autocheckLast',
            'toolbar_admin',
            'toolbar_adminmodified',
            'toolbar_admin_comments',
            'toolbar_admin_commentsmodified',
            'toolbar_global',
            'toolbar_globalmodified',
            'toolbar_global_comments',
            'toolbar_global_commentsmodified',
            'toolbar_wiki page',
            'toolbar_wiki pagemodified',
            'toolbar_wiki page_comments',
            'toolbar_wiki page_commentsmodified',
            'tiki_check_status',
        ];

        // there are default preferences that don't appear in the admin panel, So we get them too
        $defaultPrefs = get_default_prefs();
        foreach ($defaultPrefs as $p => $v) {
            $definition = $this->getPreference($p, true, $prefs);
            if (! $definition) {
                $specialPrefs[] = $p;
            }
        }
        return $specialPrefs;
    }

    /**
     * checks if a preference is special
     *
     * @param string $pref preference name
     * @param array $specialPrefs list of special prefs
     * @return bool
     */
    public function isSpecialPref($pref, $specialPrefs)
    {
        // We also get some preferences created by concatenation of parameters (eg : "pluginalias_" . $name)
        $pluginalias = "pluginalias_";
        $oauth = "oauth_token_";
        $h5p = "h5p_";

        $is_special_pref = false;

        if (
            in_array($pref, $specialPrefs)
            or substr($pref, 0, strlen($pluginalias)) === $pluginalias
            or substr($pref, 0, strlen($oauth)) === $oauth
            or substr($pref, 0, strlen($h5p)) === $h5p
        ) {
            $is_special_pref = true;
        }
        return $is_special_pref;
    }

    private function getVoteIconParams($pref, $vote, $label)
    {
        $iconname = [
            'like' => 'thumbs-up',
            'unlike' => 'thumbs-down'
        ];
        return [
            'name' => $iconname[$vote],
            'title' => $label,
            'href' => '#', 'onclick' => 'connectVote(\'' . $pref . '\', \'' . $vote . '\', this);return false;',
            'class' => '',
            'iclass' => 'icon connectVoter',
            'istyle' => 'display:none',
        ];
    }

    /**
     * Check preference state
     * @param $tags
     * @param $state
     * @return bool
     */
    private function checkPreferenceState($tags, $state)
    {
        static $rules = null;

        if (is_null($rules)) {
            global $systemConfiguration;
            $rules = array_merge($this->getDefaultSystemPreferences(), $systemConfiguration->rules->toArray());
            krsort($rules, SORT_NUMERIC);

            foreach ($rules as &$rule) {
                $parts = explode(' ', $rule);
                $type = array_shift($parts);
                $rule = [$type, $parts];
            }

            unset($rule);
        }


        foreach ($rules as $rule) {
            $intersect = array_intersect($rule[1], $tags);

            if (count($intersect)) {
                return $rule[0] == $state;
            }
        }

        return false;
    }

    private function checkExtensions($extensions)
    {
        if (count($extensions) == 0) {
            return true;
        }

        $installed = get_loaded_extensions();

        foreach ($extensions as $ext) {
            if (! in_array($ext, $installed)) {
                return false;
            }
        }

        return true;
    }

    private function checkDatabaseFeatures($features)
    {
        if (in_array('mysql_fulltext', $features)) {
            return TikiDb::get()->isMySQLFulltextSearchSupported();
        }

        return true;
    }

    /**
     * Unset hidden preferences based on the configuration file settings
     * @param $preferences
     * @return array
     */
    public function unsetHiddenPreferences($preferences)
    {
        if (empty($preferences)) {
            return [];
        }

        foreach ($preferences as $key => $preference) {
            $preferenceInfo = $this->getPreference($preference);

            if (isset($preferenceInfo['hide']) && $preferenceInfo['hide'] === true) {
                unset($preferences[$key]);
            }
        }

        return $preferences;
    }

    public function getMatchingPreferences($criteria, $filters = null, $maxRecords = 50, $sort = '')
    {
        $index = $this->getIndex();

        $query = new Search_Query($criteria);
        $query->setCount($maxRecords);

        if ($sort) {
            $query->setOrder($sort);
        }
        if ($filters) {
            $this->buildPreferenceFilter($query, $filters);
        }
        $results = $query->search($index);

        $prefs = [];
        foreach ($results as $hit) {
            $prefs[] = $hit['object_id'];
        }

        return $prefs;
    }

    /**
     * @param      $handled
     * @param      $data
     * @param null $limitation
     *
     * @return array
     */

    public function applyChanges($handled, $data, $limitation = null)
    {
        global $user_overrider_prefs;
        $tikilib = TikiLib::lib('tiki');

        if (is_array($limitation)) {
            $handled = array_intersect($handled, $limitation);
        }

        $resets = isset($data['lm_reset']) ? (array) $data['lm_reset'] : [];

        $changes = [];
        foreach ($handled as $pref) {
            if (in_array($pref, $resets)) {
                $tikilib->delete_preference($pref);
                $changes[$pref] = ['type' => 'reset'];
            } else {
                $value = $this->formatPreference($pref, $data);
                $realPref = in_array($pref, $user_overrider_prefs) ? "site_$pref" : $pref;
                $old = $this->formatPreference($pref, [$pref => $tikilib->get_preference($realPref)]);
                if ($old != $value) {
                    if ($tikilib->set_preference($pref, $value)) {
                        $changes[$pref] = ['type' => 'changed', 'new' => $value, 'old' => $old];
                    }
                }
            }
        }

        return $changes;
    }

    public function formatPreference($pref, $data)
    {
        $info = $this->getPreference($pref);

        if (false !== $info) {
            if (empty($info['type'])) {
                $info['type'] = 'text';
                Feedback::error(tr('Preference %0 has no type set', $pref));
            }
            $function = '_get' . ucfirst($info['type']) . 'Value';
            $value = $this->$function($info, $data);
            return $value;
        } else {
            if (isset($data[$pref])) {
                return $data[$pref];
            }
            return null;
        }
    }

    public function getInput(JitFilter $filter, array $preferences = [], $environment = '')
    {
        $out = [];

        foreach ($preferences as $name) {
            $info = $this->getPreference($name);
            if ($environment == 'perspective' && isset($info['perspective']) && $info['perspective'] === false) {
                continue;
            }
            if (isset($info['filter'])) {
                $filter->replaceFilter($name, $info['filter']);
            }
            if (isset($info['separator'])) {
                $out[ $name ] = $filter->asArray($name, $info['separator']);
            } else {
                $out[ $name ] = $filter[$name];
            }
        }
        return $out;
    }

    public function getExtraSortColumns()
    {
        global $prefs;
        if (isset($prefs['rating_advanced']) && $prefs['rating_advanced'] == 'y') {
            return TikiDb::get()->fetchMap("SELECT CONCAT('adv_rating_', ratingConfigId), name FROM tiki_rating_configs");
        } else {
            return [];
        }
    }

    private function loadData($name)
    {
        if (in_array($name, $this->system_modified)) {
            return null;
        }
        if (substr($name, 0, 3) == 'tp_') {
            $midpos = strpos($name, '__', 3);
            $pos = strpos($name, '__', $midpos + 2);
            $file = substr($name, 0, $pos);
        } elseif (substr($name, 0, 7) == 'themes_') {
            $pos = strpos($name, '_', 7 + 1);
            $file = substr($name, 0, $pos);
        } elseif (false !== $pos = strpos($name, '_')) {
            $file = substr($name, 0, $pos);
        } elseif (file_exists(__DIR__ . "/prefs/{$name}.php")) {
            $file = $name;
        } else {
            $file = 'global';
        }

        return $this->getFileData($file);
    }

    private function getFileData($file, $partial = false)
    {
        if (! isset($this->files[$file])) {
               $this->realLoad($file, $partial);
        }

        $ret = [];
        if (isset($this->files[$file])) {
            $ret = $this->files[$file];
        }

        if ($partial) {
            unset($this->files[$file]);
        }

        return $ret;
    }

    private function realLoad($file, $partial)
    {
        $inc_file = __DIR__ . "/prefs/{$file}.php";
        if (substr($file, 0, 3) == "tp_") {
            $paths = \Tiki\Package\ExtensionManager::getPaths();
            $package = str_replace('__', '/', substr($file, 3));
            $inc_file = $paths[$package] . "/prefs/{$file}.php";
        }
        if (preg_match('/^themes_(.*)$/', $file, $matches)) {
            $themeName = $matches[1];
            $themePath = TikiLib::lib('theme')->get_theme_path($themeName);
            $inc_file = $themePath . "prefs/{$file}.php";
        }
        if (file_exists($inc_file) && $file !== "index") {
            require_once $inc_file;
            $function = "prefs_{$file}_list";
            if (function_exists($function)) {
                $this->files[$file] = $function($partial);
            } else {
                $this->files[$file] = [];
            }
        }
    }

    private function getDependencies($dependencies)
    {
        $out = [];
        $installer = new Tiki_Profile_Installer();

        foreach ((array) $dependencies as $key => $dep) {
            $info = $this->getPreference($dep, false);
            if ($info) {
                $name = isset($info['name']) ? $info['name'] : '';
                $type = isset($info['type']) ? $info['type'] : '';
                $link = isset($info['adminurl']) ? $info['adminurl'] : '';
                $out[] = [
                    'name' => $dep,
                    'label' => $name,
                    'type' => $type,
                    'link' => $link,
                    'met' =>
                        ( $type == 'flag' && $info['value'] == 'y' )
                        || ( $type != 'flag' && ! empty($info['value']) )
                ];
            } elseif ($key === 'profiles') {
                foreach ((array) $dep as $profile) {
                    $out[] = [
                        'name' => $profile,
                        'label' => $profile,
                        'type' => 'profile',
                        'link' => 'tiki-admin.php?page=profiles&list=List&profile=' . urlencode($profile),
                        'met' => $installer->isInstalled($profile) ? true : false
                    ];
                }
            }
        }

        return $out;
    }

    private function getConflicts($conflicts)
    {
        $active = [];
        $inactive = [];

        foreach ((array) $conflicts as $pref) {
            $info = $this->getPreference($pref, false);
            if (! $info) {
                continue;
            }
            $name = isset($info['name']) ? $info['name'] : '';
            $link = isset($info['adminurl']) ? $info['adminurl'] : '';
            if ($info['value'] == 'y') {
                $active[] = [
                    'name' => $pref,
                    'label' => $name,
                    'link' => $link,
                ];
            } else {
                $inactive[] = [
                    'name' => $pref,
                    'label' => $name,
                    'link' => $link,
                ];
            }
        }

        return [
            'active' => $active,
            'inactive' => $inactive,
        ];
    }

    private function getPackagesRequired($packages)
    {
        $out = [];

        foreach ((array) $packages as $key => $dep) {
            $met = class_exists($dep) || file_exists($dep);

            $package = [
                'name' => $key,
                'label' => $key,
                'type' => 'composer',
                'link' => 'tiki-admin.php?page=packages',
                'met' => $met
            ];

            if ($packageInfo = ComposerManager::getPackageInfo($key)) {
                $package['name'] = $packageInfo['name'];
                $package['label'] = $packageInfo['name'];

                if (! empty($packageInfo['link'])) {
                    $package['link'] = $packageInfo['link'];
                }
            }

            $out[] = $package;
        }

        return $out;
    }

    /**
     * @param bool $fallback Rebuild fallback search index
     * @return Search_Index_Interface|null
     * @throws Exception
     */
    public function rebuildIndex($fallback = false)
    {
        global $prefs;

        $index = TikiLib::lib('unifiedsearch')->getIndex('preference', ! $fallback);
        $index->destroy();

        $typeFactory = $index->getTypeFactory();

        $indexed = [];

        foreach ($this->getAvailableFiles() as $file) {
            $data = $this->getFileData($file);

            foreach ($data as $pref => $info) {
                $prefInfo = $this->getPreference($pref);
                if (! empty($prefInfo['hide'])) {
                    continue;   // hidden prefs have had their info removed, so no point indexing them
                }
                if ($prefInfo) {
                    $info = $prefInfo;
                } else {
                    $info['preference'] = $pref;
                    if (empty($info['tags'])) {
                        $info['tags'] = ['missing'];
                    }
                }
                $doc = $this->indexPreference($typeFactory, $pref, $info);
                $index->addDocument($doc);

                $indexed[] = $pref;
            }
        }

        // Rebuild fallback index
        list($fallbackEngine) = TikiLib::lib('unifiedsearch')->getFallbackEngineDetails();
        if (! $fallback && $fallbackEngine) {
            $defaultEngine = $prefs['unified_engine'];
            $prefs['unified_engine'] = $fallbackEngine;
            $this->rebuildIndex(true);
            $prefs['unified_engine'] = $defaultEngine;
        }

        return $index;
    }

    private function getIndex()
    {
        $index = TikiLib::lib('unifiedsearch')->getIndex('preference');

        if (! $index->exists()) {
            $index = null;
            return $this->rebuildIndex();
        }

        return $index;
    }

    public function indexNeedsRebuilding()
    {
        $index = TikiLib::lib('unifiedsearch')->getIndex('preference');
        return ! $index->exists();
    }

    public function getPreferenceLocations($name)
    {
        if (! $this->usageArray) {
            $this->loadPreferenceLocations();
        }

        $pages = [];
        foreach ($this->usageArray as $pg => $pfs) {
            foreach ($pfs as $pf) {
                if ($pf[0] == $name) {
                    $pages[] = [$pg, $pf[1]];
                }
            }
        }

        if (strpos($name, 'wikiplugin_') === 0 || strpos($name, 'wikiplugininline_') === 0) {
            $pages[] = ['textarea', 2]; // plugins are included in textarea admin dynamically
        }
        if (strpos($name, 'trackerfield_') === 0) {
            $pages[] = ['trackers', 3]; // trackerfields are also included in tracker admin dynamically
        }

        return $pages;
    }

    private function loadPreferenceLocations()
    {
        global $prefs;

        // check for or create array of where each pref is used
        $file = TEMP_CACHE_PATH . '/preference-usage-index';
        if (! file_exists($file)) {
            $prefs_usage_array = [];
            $fp = opendir(TEMPLATES_ADMIN_PATH . '/');

            while (false !== ($f = readdir($fp))) {
                preg_match('/^include_(.*)\.tpl$/', $f, $m);
                if (count($m) > 0) {
                    $page = $m[1];
                    $c = file_get_contents(TEMPLATES_ADMIN_PATH . '/' . $f);
                    preg_match_all('/{preference.*name=[\'"]?(\w*)[\'"]?.*}/i', $c, $m2, PREG_OFFSET_CAPTURE);
                    if (count($m2[1]) > 0) {
                        // count number of tabs in front of each found pref
                        foreach ($m2[1] as & $found) {
                            $tabs = preg_match_all('/{\/tab}/i', substr($c, 0, $found[1]), $m3);
                            if ($tabs === false) {
                                $tabs = 0;
                            } else {
                                $tabs++;
                            }
                            if ($prefs['site_layout'] !== 'classic' && $page === 'look' && $tabs > 2) {
                                $tabs--;    // hidden tab #3 for shadow layers
                            }
                            $found[1] = $tabs;  // replace char offset with tab number
                        }
                        $prefs_usage_array[$page] = $m2[1];
                    }
                }
            }
            file_put_contents($file, serialize($prefs_usage_array));
        } else {
            $prefs_usage_array = unserialize(file_get_contents($file));
        }

        $this->usageArray = $prefs_usage_array;
    }

    private function indexPreference($typeFactory, $pref, $info)
    {
        $contents = [
            $info['preference'],
            // also index the parts of the pref name individually, e.g. wikiplugin_plugin_name as wikiplugin plugin name
            str_replace('_', ' ', $info['preference']),
            $info['name'],
            isset($info['description']) ? $info['description'] : '',
            isset($info['keywords']) ? $info['keywords'] : '',
        ];

        if (isset($info['options'])) {
            $contents = array_merge($contents, $info['options']);
        }

        return [
            'object_type' => $typeFactory->identifier('preference'),
            'object_id' => $typeFactory->identifier($pref),
            'contents' => $typeFactory->plaintext(implode(' ', $contents)),
            'tags' => $typeFactory->plaintext(implode(' ', $info['tags'])),
        ];
    }

    private function _getFlagValue($info, $data)
    {
        $name = $info['preference'];
        if (isset($data[$name]) && ! empty($data[$name]) && $data[$name] != 'n') {
            $ret = 'y';
        } else {
            $ret = 'n';
        }

        return $ret;
    }

    private function _getSelectorValue($info, $data)
    {
        $name = $info['preference'];
        if (! empty($data[$name])) {
            $value = $data[$name];

            if (isset($info['filter']) && $filter = TikiFilter::get($info['filter'])) {
                return $filter->filter($value);
            } else {
                return $value;
            }
        }
    }

    private function _getMultiselectorValue($info, $data)
    {
        $name = $info['preference'];

        if (isset($data[$name]) && ! empty($data[$name])) {
            if (! is_array($data[$name])) {
                $value = explode($info['separator'], $data[$name]);
            } else {
                $value = $data[$name];
            }
        } else {
            $value = [];
        }

        if (isset($info['filter']) && $filter = TikiFilter::get($info['filter'])) {
            return array_map([ $filter, 'filter' ], $value);
        } else {
            return $value;
        }
    }

    private function _getTextValue($info, $data)
    {
        $name = $info['preference'];

        if (isset($info['separator']) && is_string($data[$name])) {
            if (! empty($data[$name])) {
                $value = explode($info['separator'], $data[$name]);
            } else {
                $value = [];
            }
        } else {
            $value = $data[$name] ?? null;
        }

        if (isset($info['filter']) && $filter = TikiFilter::get($info['filter'])) {
            if (is_array($value)) {
                $value = array_map([ $filter, 'filter' ], $value);
            } else {
                $value = $filter->filter($value);
            }
        }
        return $this->applyConstraints($info, $value);
    }

    private function _getPasswordValue($info, $data)
    {
        $name = $info['preference'];

        if (isset($info['filter']) && $filter = TikiFilter::get($info['filter'])) {
            return $filter->filter($data[$name]);
        } else {
            return $data[$name];
        }
    }

    private function _getTextareaValue($info, $data)
    {
        $name = $info['preference'];
        $value = $data[$name] ?? null;

        if (is_null($value)) {
            return null;
        }

        if (isset($info['filter']) && $filter = TikiFilter::get($info['filter'])) {
            $value = $filter->filter($data[$name]);
        }

        if (is_array($value)) {
            return $value;
        }

        $value = str_replace("\r", "", $value);
        if (isset($info['unserialize'])) {
            $fnc = $info['unserialize'];

            return $fnc($value);
        } else {
            return $value;
        }
    }

    private function _getListValue($info, $data)
    {
        $name = $info['preference'];
        $value = isset($data[$name]) ? $data[$name] : null;

        $options = $info['options'];

        if (isset($options[$value])) {
            return $value;
        } else {
            $keys = array_keys($options);
            return reset($keys);
        }
    }

    private function _getMultilistValue($info, $data)
    {
        $name = $info['preference'];
        $value = isset($data[$name]) ? (array) $data[$name] : [];

        $options = $info['options'];
        $options = array_keys($options);

        return array_intersect($value, $options);
    }

    private function _getRadioValue($info, $data)
    {
        $name = $info['preference'];
        $value = isset($data[$name]) ? $data[$name] : null;

        $options = $info['options'];
        $options = array_keys($options);

        if (in_array($value, $options)) {
            return $value;
        } else {
            return '';
        }
    }

    private function _getMulticheckboxValue($info, $data)
    {
        return $this->_getMultilistValue($info, $data);
    }

    /**
     * Apply constraints (e.g., min or max) defined in the preference info. Currently only used in text type preference.
     *
     * @param $info     array   preference info from definition
     * @param $value    mixed   value submitted for the preference to be changed to
     * @return          mixed   value preference will be changed to after applying constraints
     */
    private function applyConstraints($info, $value)
    {
        if (isset($info['constraints'])) {
            $original = $value;
            foreach ($info['constraints'] as $type => $constraint) {
                switch ($type) {
                    case 'min':
                        if ($value < $constraint) {
                            $value = $constraint;
                            Feedback::warning(tr(
                                '%0 set to minimum of %1 instead of submitted value of %2',
                                $info['preference'],
                                $constraint,
                                $original
                            ));
                        }
                        break;
                    case 'max':
                        if ($value > $constraint) {
                            $value = $constraint;
                            Feedback::warning(tr(
                                '%0 set to maximum of %1 instead of submitted value of %2',
                                $info['preference'],
                                $constraint,
                                $original
                            ));
                        }
                        break;
                }
            }
        }
        return $value;
    }

    // for export as yaml

    /**
     * @global TikiLib $tikilib
     * @param bool $added shows current prefs not in defaults
     * @return array (prefname => array( 'current' => current value, 'default' => default value ))
     */
    // NOTE: tikilib contains a similar method called getModifiedPreferences
    public function getModifiedPrefsForExport($added = false)
    {
        $tikilib = TikiLib::lib('tiki');

        $prefs = $tikilib->getModifiedPreferences();

        $defaults = get_default_prefs();
        $modified = [];

        foreach ($prefs as $pref => $value) {
            if (( $added && ! isset($defaults[$pref])) || (isset($defaults[$pref]) && $value !== $defaults[$pref] )) {
                if (! in_array($pref, $this->system_modified) && ! in_array($pref, $this->system_info)) {   // prefs modified by the system and with system info etc
                    $preferenceInformation = $this->getPreference($pref);
                    $modified[$pref] = [
                        'current' => ['serial' => $value, 'expanded' => $preferenceInformation['value'] ?? ''],
                    ];
                    if (isset($defaults[$pref])) {
                        $modified[$pref]['default'] = $defaults[$pref];
                    }
                }
            }
        }
        ksort($modified);

        return $modified;
    }

    public function getDefaults()
    {
        $defaults = [];

        foreach ($this->getAvailableFiles() as $file) {
            $data = $this->getFileData($file, true);

            foreach ($data as $name => $info) {
                if (isset($info['default'])) {
                    $defaults[$name] = $info['default'];
                } else {
                    $defaults[$name] = '';
                }
            }
        }

        return $defaults;
    }

    private function getAvailableFiles()
    {
        $files = [];
        foreach (glob(__DIR__ . '/prefs/*.php') as $file) {
            if (basename($file) === "index.php") {
                continue;
            }
            $files[] = substr(basename($file), 0, -4);
        }
        foreach (TikiLib::lib('theme')->get_available_themes() as $theme => $label) {
            $themePath = TikiLib::lib('theme')->get_theme_path($theme);
            foreach (glob($themePath . 'prefs/*.php') as $file) {
                if (basename($file) === "index.php") {
                    continue;
                }
                $files[] = substr(basename($file), 0, -4);
            }
        }
        foreach (\Tiki\Package\ExtensionManager::getPaths() as $path) {
            foreach (glob($path . '/prefs/*.php') as $file) {
                if (basename($file) === "index.php") {
                    continue;
                }
                $files[] = substr(basename($file), 0, -4);
            }
        }
        return $files;
    }

    public function setFilters($tags)
    {
        global $user;

        if (! in_array('basic', $tags)) {
            $tags[] = 'basic';
        }
        TikiLib::lib('tiki')->set_user_preference($user, 'pref_filters', implode(',', $tags));
    }

    public function getEnabledFilters()
    {
        global $user;
        $tikilib = TikiLib::lib('tiki');
        $filters = $tikilib->get_user_preference($user, 'pref_filters', 'basic');
        $filters = explode(',', $filters);
        return $filters;
    }

    public function getFilters($filters = null)
    {
        if (! $filters) {
            $filters = $this->getEnabledFilters();
        }

        $out = [
            'basic' => [
                'label' => tra('Basic'),
                'type' => 'positive',
            ],
            'advanced' => [
                'label' => tra('Advanced'),
                'type' => 'positive',
            ],
            'experimental' => [
                'label' => tra('Experimental'),
                'type' => 'negative',
            ],
            'unavailable' => [
                'label' => tra('Unavailable'),
                'type' => 'negative',
            ],
            'deprecated' => [
                'label' => tra('Deprecated'),
                'type' => 'negative',
            ],
        ];

        foreach ($out as $key => & $info) {
            $info['selected'] = in_array($key, $filters);
        }

        return $out;
    }

    private function buildPreferenceFilter($query, $input = null)
    {
        $filters = $this->getFilters($input);

        foreach ($filters as $tag => $info) {
            if ($info['selected']) {
                $positive[] = $tag;
            } elseif ($info['type'] == 'negative') {
                $query->filterContent("NOT $tag", 'tags');
            }
        }

        if (count($positive)) {
            $query->filterContent(implode(' OR ', $positive), 'tags');
        }

        return $query;
    }

    /***
     * Store 10 most recently changed prefs for quickadmin module menu
     *
     * @param string $name        preference name
     * @param string $auser       optional user
     */

    public function addRecent($name, $auser = null)
    {
        global $user;

        if (! $auser) {
            $auser = $user;
        }

        $list = (array) $this->getRecent($auser);
        array_unshift($list, $name);
        $list = array_unique($list);
        $list = array_slice($list, 0, 10);

        TikiLib::lib('tiki')->set_user_preference($auser, 'admin_recent_prefs', serialize($list));
    }

    /***
     * Get recent prefs list
     *
     * @param null $auser   option user
     * @return array        array of pref names
     */

    public function getRecent($auser = null)
    {
        global $user;
        $tikilib = TikiLib::lib('tiki');

        if (! $auser) {
            $auser = $user;
        }

        $recent = $tikilib->get_user_preference($auser, 'admin_recent_prefs');

        if (empty($recent)) {
            return [];
        } else {
            return unserialize($recent);
        }
    }

    /**
     * Export preferences
     *
     * @param Tiki_Profile_Writer $writer
     * @param string $preferenceName
     * @param bool $all
     * @return bool
     */
    public function exportPreference(Tiki_Profile_Writer $writer, $preferenceName, $all = null)
    {
        if (isset($preferenceName) && ! $all) {
            $listPrefs = [];
            $listPrefs[$preferenceName] = true;
        } else {
            $listPrefs = $this->getModifiedPrefsForExport(true);
        }

        if (empty($listPrefs)) {
            return false;
        }

        foreach ($listPrefs as $preferenceName => $value) {
            if (is_string($preferenceName)) {
                if ($info = $this->getPreference($preferenceName)) {
                    if (isset($info['profile_reference'])) {
                        $writer->setPreference($preferenceName, $writer->getReference($info['profile_reference'], $info['value']));
                    } else {
                        $writer->setPreference($preferenceName, $info['value']);
                    }
                }
            }
        }

        return true;
    }

    public function getPackagePrefs()
    {
        global $prefs;
        $ret = [];
        foreach (array_keys($prefs) as $prefName) {
            if (substr($prefName, 0, 3) == 'tp_') {
                $ret[] = $prefName;
            }
        }
        return $ret;
    }

    /**
     * Get a list of preferences that belong to themes
     *
     * @return array
     * @throws Exception
     */
    public function getThemePrefs()
    {
        global $prefs;
        $ret = [];
        foreach (array_keys($prefs) as $prefName) {
            if (substr($prefName, 0, 7) == 'themes_') {
                $ret[] = $prefName;
            }
        }

        $themes = TikiLib::lib('theme')->get_available_themes();
        $preferences = [];
        foreach ($themes as $key => $theme) {
            $themePref = array_filter($ret, function ($pref) use ($key) {
                $pattern = '/^themes_' . $key . '_.*/';
                return preg_match($pattern, $pref);
            });

            if (! empty($themePref)) {
                $preferences[$theme] = $themePref;
            }
        }

        return $preferences;
    }

    /**
     * Filter hidden preferences using an array of preference names
     * @return array
     */
    public function filterHiddenPreferences($preferences)
    {
        $hiddenPreferences = [];
        if (! empty($preferences)) {
            foreach ($preferences as $preference) {
                $prefName = $preference['name'] ?? $preference['prefName'];
                $preferenceDetails = $this->getPreference($prefName);
                if (! empty($preferenceDetails['hide']) && $preferenceDetails['hide'] === true) {
                    $hiddenPreferences[] = $prefName;
                }
            }
        }

        return $hiddenPreferences;
    }

    /**
     * Function responsible for getting the default system preferences.
     * Logic related to getting default system preferences should be set here
     * @return array
     */
    private function getDefaultSystemPreferences()
    {
        $defaultSystemPreferences = [];

        foreach (self::DEFAULT_HIDDEN_PREFERENCES as $hiddenPreference) {
            $defaultSystemPreferences[] = 'hide ' . $hiddenPreference;
        }

        return $defaultSystemPreferences;
    }
}
