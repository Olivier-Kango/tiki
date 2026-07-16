<?php

class ConverseJS
{
    // Bump this whenever converse.js itself is upgraded (its own version number is a good
    // value to use). It's folded into the client-side cache-identity string in render() below,
    // to force a one-time wipe of every returning user's cached localStorage/sessionStorage/
    // IndexedDB converse state - old data can have a shape the new converse.js version doesn't
    // expect (e.g. a null-attribute crash restoring a stale IndexedDB-cached collection), and
    // the existing per-PHP-session identity string alone doesn't change across a same-session
    // upgrade, so it wouldn't otherwise catch this.
    const CACHE_EPOCH = '14.0.0';

    private $options;
    private $tiki_root;

    public function __construct($options = [])
    {
        $this->options = [];
        $this->set_options(array_merge(
            [
                'auto_reconnect' => true,
                'debug' => false,
                'show_controlbox_by_default' => true,
                'show_occupants_by_default' => true,
                'use_emojione' => false,
                'view_mode' => 'overlayed',
                'assets_path'   => CONVERSEJS_DIST_PATH . '/',
                'whitelisted_plugins' => ['tiki', 'tiki-oauth'],
                // OMEMO isn't configured/used anywhere in this integration (converse-omemo is a
                // CORE_PLUGINS entry, so whitelisted_plugins above can't exclude it) - blacklist
                // it explicitly to skip that unused work.
                'blacklisted_plugins' => ['converse-omemo'],
                // Tiki always resolves and passes the exact transport (bosh_service_url XOR
                // websocket_url) it wants for every connection it sets up (see
                // getTransportOptions()), so converse.js's own XEP-0156/host-meta discovery is
                // never actually needed here - keep it off to avoid depending on external servers'
                // discovery records being correct/compatible.
                'discover_connection_methods' => false,
            ],
            $options
        ));
        $this->tiki_root = dirname(dirname(__DIR__));
        $this->load_prefs();
    }

    public function load_prefs()
    {
        global $prefs;
        $this->set_option('debug', $prefs['xmpp_conversejs_debug'] === 'y');

        if (! empty($prefs['xmpp_conversejs_init_json'])) {
            $extraOptions = json_decode($prefs['xmpp_conversejs_init_json'], true);
            if ($extraOptions) {
                $this->set_options($extraOptions);
            }
        }

        if (! empty($prefs['xmpp_muc_component_domain'])) {
            $this->set_option('muc_domain', $prefs['xmpp_muc_component_domain']);
        }

        if (! empty($prefs['xmpp_ws_url'])) {
            $this->set_option('websocket_url', $prefs['xmpp_ws_url']);
        }
    }

    public function get_oauth_parameters()
    {
        $client_id = 'org.tiki.rtc.internal-conversejs-id';
        $oauthserverlib = TikiLib::lib('oauthserver');
        $accesslib = TikiLib::lib('access');

        $client = $oauthserverlib->getClient($client_id)
            ?: $oauthserverlib->createClient([
                'client_id' => $client_id,
                'name' => 'ConverseJS OAuth Client',
                'redirect_uri' => $accesslib->absoluteUrl('lib/xmpp/html/redirect.html')
            ]);

        return [
            'client_id' => $client->getClientId(),
            'name' => $client->getName(),
            'authorize_url' => TikiLib::lib('service')->getUrl([
                'action' => 'authorize',
                'controller' => 'oauthserver',
                'client_id' => $client_id,
                'response_type' => 'token',
                'skip_keypair' => 1,
            ])
        ];
    }

    public function set_auth($params)
    {
        global $user;
        global $prefs;
        $authMethod = TikiLib::lib('tiki')->get_preference('xmpp_auth_method');
        $this->set_option('on_xmpp_page', $params['on_xmpp_page'] ?? 'n');

        $currentRoom = trim($params['room'] ?? '');
        if ($currentRoom !== '') {
            $currentRoom = TikiLib::lib('xmpp')->buildRoomJid($currentRoom);
        }
        $this->set_option('current_room', $currentRoom);

        if (empty($user) && isset($params['anonymous']) && $params['anonymous'] === 'y') {
            $autoOpen = $params['auto_open'] ?? 'n';
            $onXmppPage = ($params['on_xmpp_page'] ?? 'n') === 'y';
            $anonymousMode = $prefs['xmpp_anonymous_mode'] ?? 'community';
            $anonRoom = '';
            $dmTarget = '';

            if ($onXmppPage) {
                $anonRoom = trim($prefs['xmpp_anonymous_room'] ?? '');
            } elseif ($anonymousMode === 'support') {
                $dmTarget = trim($params['dm_target'] ?? ($prefs['xmpp_admin_jid'] ?? ''));
            } else {
                $anonRoom = trim($prefs['xmpp_anonymous_room'] ?? '');
            }

            $this->set_options([
                'authentication'      => 'anonymous',
                'auto_login'          => true,
                'jid'                 => $prefs['xmpp_domain_guest'] ?? null,
                'bosh_service_url'    => $prefs['xmpp_server_http_bind'] ?? null,
                'websocket_url'       => $prefs['xmpp_ws_url'] ?? null,
                'dm_target'           => $dmTarget,
                'anon_room'           => $anonRoom,
                'tiki_anonymous_nick' => ($prefs['xmpp_anonymous_allow_custom_nickname'] ?? 'n') === 'y' ? 'custom' : 'visitor',
                'auto_open'           => $autoOpen,
            ]);

        } elseif ($authMethod === 'tikitoken') {
            $this->set_options([
                'auto_login' => true,
                'authentication'   => 'prebind',
                'prebind_url'      => TikiLib::lib('service')->getUrl([
                    'action' => 'prebind',
                    'controller' => 'xmpp',
                ]),
            ]);
        } elseif ($authMethod === 'oauth') {
            $this->set_options([
                'authentication'   => 'login',
                'oauth_providers' => [
                    'tiki' => $this->get_oauth_parameters(),
                ]]);
        } elseif ($authMethod === 'http') {
            if (! empty($user)) {
                $autoOpen = $params['auto_open'] ?? 'n';
                $this->set_options($this->getUserAuthOptions($user, $autoOpen));
            }
        } else {
            global $user, $prefs;
            if (! empty($user)) {
                $autoOpen = $params['auto_open'] ?? 'n';
                $this->set_options($this->getUserAuthOptions($user, $autoOpen));
            }
        }
    }

    private function getUserAuthOptions(string $user, string $auto_open = 'n'): array
    {
        $xmpplib = TikiLib::lib('xmpp');
        $xmpp = $xmpplib->get_user_connection_info($user);
        $jidInfo = $xmpplib->getJidInfoForUser($user);
        $externalAvailable = $jidInfo['isExternal'] && ! empty($xmpp['custom_endpoint']);
        $onXmppPage = $this->get_option('on_xmpp_page') === 'y';
        $useExternalNow = $onXmppPage && $externalAvailable && ($_COOKIE['tiki_xmpp_external'] ?? '') === '1';

        if ($useExternalNow) {
            $transportOptions = $this->getTransportOptions($xmpp, true);
            $externalPassword = $xmpplib->getExternalPassword($user);

            if ($externalPassword !== null) {
                return array_merge([
                    'authentication' => 'login',
                    'auto_login'     => true,
                    'jid'            => $jidInfo['jid'],
                    'password'       => $externalPassword,
                    'auto_open'      => $auto_open,
                    'external_jid'   => $jidInfo['jid'],
                    'using_external' => 'y',
                ], $transportOptions);
            }

            return array_merge([
                'authentication' => 'login',
                'auto_login'     => false,
                'jid'            => $jidInfo['jid'],
                'auto_open'      => $auto_open,
                'external_jid'   => $jidInfo['jid'],
                'using_external' => 'y',
            ], $transportOptions);
        }

        $transportOptions = $this->getTransportOptions($xmpp, false);
        return array_merge([
            'authentication' => 'login',
            'auto_login'     => true,
            'jid'            => $jidInfo['localJid'] ?? '',
            'password'       => $xmpplib->getXmppSessionToken($user),
            'auto_open'      => $auto_open,
            'external_jid'   => ($onXmppPage && $externalAvailable) ? $jidInfo['jid'] : '',
            'using_external' => 'n',
        ], $transportOptions);
    }

    private function getTransportOptions(array $xmpp, bool $usesExternalJid): array
    {
        global $prefs;

        if (! $usesExternalJid) {
            return [
                'websocket_url'    => $prefs['xmpp_ws_url'] ?? '',
                'bosh_service_url' => $prefs['xmpp_server_http_bind'] ?? '',
            ];
        }

        if (empty($xmpp['custom_endpoint'])) {
            return [
                'websocket_url'    => '',
                'bosh_service_url' => '',
            ];
        }

        return [
            'websocket_url'    => $xmpp['websocket_url'] ?? '',
            'bosh_service_url' => $xmpp['http_bind'] ?? '',
        ];
    }

    public function set_options($options)
    {
        foreach ($options as $name => $value) {
            $this->set_option($name, $value);
        }
    }

    public function get_options()
    {
        return $this->options ?: [];
    }

    public function set_option($name, $value)
    {
        $this->options[ $name ] = $value;
    }

    public function get_option($name, $fallback = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $fallback;
    }

    public function set_auto_join_rooms($room)
    {
        if (! is_string($room) || empty($room)) {
            return;
        }

        $marker = strrpos($room, '@');
        $domain = $this->get_option('muc_domain');

        if (! $marker && $domain) {
            $room = $room . '@' . $domain;
        }

        $this->options['auto_join_rooms'] = [ $room ];
    }

    public function append_mtime($file, $argname = '_')
    {
        $mtime = '';
        $file_path = $this->tiki_root . '/' . $file;

        if (! file_exists($file_path)) {
            return $file;
        }
        $mtime = stat($file_path)['mtime'];
        return "{$file}?{$argname}={$mtime}";
    }

    public function get_css_dependencies()
    {
        $deps = [
            CONVERSEJS_DIST_PATH . '/converse.min.css',
            'lib/xmpp/css/conversejs.css',
        ];
        return array_map([$this, 'append_mtime'], $deps);
    }

    public function get_js_dependencies()
    {
        $deps = [
            'lib/xmpp/js/conversejs-tiki.js',
            'lib/xmpp/js/conversejs-tiki-oauth.js',
        ];
        return array_map([$this, 'append_mtime'], $deps);
    }

    /**
     * Registers converse.js's own script plus the tiki plugin files with HeaderLib, for the two
     * places that need them (tiki-setup.php's "always load" case, and render() below).
     *
     * converse.js 14+ ships ESM-only (no UMD/global build, no `window.converse` side effect) -
     * see the sitewide importmap entry in path_js_importmap_generator.php. The two plugin files
     * below are plain classic scripts that expect a bare `converse`/`window.converse` global (as
     * does render()'s own inline `converse.initialize(...)` call further down), so an inline
     * bootstrap module runs first to import the bare specifier and assign it to that global -
     * non-async module scripts (inline or external) execute in strict document order relative to
     * each other, same guarantee as classic `defer` scripts, so this ordering is reliable without
     * needing to modify the two plugin files themselves at all.
     */
    public function register_js_dependencies()
    {
        $header = TikiLib::lib('header');
        $header->add_js_module("import converse from 'converse.js'; window.converse = converse;");
        array_map([$header, 'add_jsfile_module'], $this->get_js_dependencies());
    }

    public function render()
    {
        global $user, $prefs;
        $options = $this->get_options();

        if (!isset($options['auto_open'])) {
            $options['auto_open'] = 'n';
        }

        if (!isset($options['anonymous_auto_open'])) {
            $options['anonymous_auto_open'] = 'n';
        }

        $output = ';(function () {' . PHP_EOL . 'try {' . PHP_EOL;

        if ($prefs['xmpp_conversejs_always_load'] !== 'y') {
            $this->register_js_dependencies();

            $output .= PHP_EOL . ';(function() {';
            $output .= PHP_EOL . '    var link;';
            foreach ($this->get_css_dependencies() as $file) {
                $output .= PHP_EOL . '    link = document.createElement("link");';
                $output .= PHP_EOL . '    link.rel = "stylesheet";';
                $output .= PHP_EOL . '    link.href = "' . $file . '";';
                $output .= PHP_EOL . '    document.body.appendChild(link);';
            }
            $output .= PHP_EOL . '})();';
        }

        if (empty($_SESSION['chat-session-init'])) {
            $_SESSION['chat-session-init'] = time();
        }
        $chat_session_init = ($user ?: 'anonymous') . '|' . ($options['jid'] ?? '') . '|' . $_SESSION['chat-session-init'] . '|' . self::CACHE_EPOCH;
        $chat_session_init_js = json_encode($chat_session_init);

        $output .= PHP_EOL . ';(function(){';
        $output .= PHP_EOL . "  if (localStorage['chat-session-init'] !== {$chat_session_init_js}) {";
        $output .= PHP_EOL . '      console.warn("Cleaning environment for ' . 'converse session change");';
        $output .= PHP_EOL . "      localStorage['chat-session-init'] = {$chat_session_init_js};";
        $output .= PHP_EOL . '      for (var key in localStorage) { key.match(/(converse|strophe)/) && localStorage.removeItem(key); }';
        $output .= PHP_EOL . '      for (var key in sessionStorage) { key.match(/(converse|strophe)/) && sessionStorage.removeItem(key); }';
        $output .= PHP_EOL . '      if (window.indexedDB && indexedDB.databases) {';
        $output .= PHP_EOL . '          indexedDB.databases().then(function (dbs) {';
        $output .= PHP_EOL . '              dbs.forEach(function (db) {';
        $output .= PHP_EOL . '                  if (db.name && /(converse|strophe)/i.test(db.name)) { indexedDB.deleteDatabase(db.name); }';
        $output .= PHP_EOL . '              });';
        $output .= PHP_EOL . '          }).catch(function (e) { console.warn("[tiki-xmpp] could not enumerate indexedDB databases for cleanup", e); });';
        $output .= PHP_EOL . '      }';
        $output .= PHP_EOL . "  }";
        $output .= PHP_EOL . '})();';
        $output .= PHP_EOL;

        if ($this->get_option('view_mode') === 'embedded') {
            // converse.js's singleton plugin throws synchronously during plugin init
            // (aborting the whole converse.initialize() call, i.e. no chat renders at all)
            // unless auto_join_rooms or auto_join_private_chats is already a non-empty
            // array. Tiki actually opens/joins the room dynamically later, client-side,
            // in conversejs-tiki.js's 'connected' handler (so bookmarks/permissions/etc.
            // can be checked first) - the plugin has no way to know that and needs
            // *something* here just to pass its own guard clause. If embedded was
            // requested without a room or DM target, singleton has nothing to lock onto
            // anyway, so skip it rather than let converse.js hard-crash.
            $currentRoom = $this->get_option('current_room');
            $dmTarget = $this->get_option('dm_target');
            if ($currentRoom) {
                $options['singleton'] = true;
                $options['auto_join_rooms'] = [$currentRoom];
            } elseif ($dmTarget) {
                $options['singleton'] = true;
                $options['auto_join_private_chats'] = [$dmTarget];
            }
        }

        $options['auto_open'] = $this->get_option('auto_open', 'n');
        $options['anonymous_auto_open'] = $this->get_option('anonymous_auto_open', 'n');
        $usingExternal = $this->get_option('using_external') === 'y';
        $options['bookmarks'] = $usingExternal;
        $options['auto_join_bookmarks'] = $usingExternal;
        $options['show_desktop_notifications'] = true;
        $options['show_tab_notifications'] = true;
        $options['play_sounds'] = true;

        if ($this->get_option('authentication') === 'anonymous') {
            $guestDomain = $this->get_option('jid') ?: ($prefs['xmpp_domain_guest'] ?? '');

            unset($options['authentication'], $options['jid'], $options['auto_login']);
            $options['authentication'] = 'login';
            $options['auto_login'] = true;

            ksort($options);
            $optionsWithoutCreds = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $guestDomainJs = json_encode($guestDomain);

$output .= <<<JS

;(function () {
    try {
        var STORAGE_KEY = 'tiki-xmpp-guest-identity';
        var identity = null;
        try {
            identity = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        } catch (e) {
            identity = null;
        }
        if (!identity || !identity.jid || !identity.password) {
            var cryptoObj = window.crypto || window.msCrypto;
            if (!cryptoObj || !cryptoObj.getRandomValues) {
                throw new Error('window.crypto unavailable - cannot generate guest identity');
            }
            var randHex = function (len) {
                var arr = new Uint8Array(len);
                cryptoObj.getRandomValues(arr);
                return Array.prototype.map.call(arr, function (b) {
                    return ('0' + b.toString(16)).slice(-2);
                }).join('');
            };
            identity = {
                jid: 'guest-' + randHex(10) + '@' + {$guestDomainJs},
                password: randHex(24)
            };
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(identity));
            } catch (e) {}
        }
        var opts = {$optionsWithoutCreds};
        opts.jid = identity.jid;
        opts.password = identity.password;
        console.log('[tiki-guest] initializing with', opts.jid);
        converse.initialize(opts);
    } catch (e) {
        console.error('[tiki-guest] FATAL - guest identity setup failed:', e);
    }
})();

JS;

            $output .= PHP_EOL . '} catch (e) { console.error(\'[tiki-xmpp] FATAL - setup failed:\', e); }' . PHP_EOL . '})();';
            return TikiLib::lib('header')->add_jq_onready($output, 10);
        }

        ksort($options);

        $optionString = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output .= 'converse.initialize(' . $optionString . ');' . PHP_EOL;
        $output .= '} catch (e) { console.error(\'[tiki-xmpp] FATAL - setup failed:\', e); }' . PHP_EOL . '})();';
        return TikiLib::lib('header')->add_jq_onready($output, 10);
    }
}
