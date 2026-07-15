<?php

class ConverseJS
{
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

        // The current page's own room (e.g. a specific PluginXMPP instance) - kept
        // separate from group-mapped rooms so a page always controls its own room
        // regardless of Tiki group membership, and works the same for anonymous and
        // authenticated users.
        $currentRoom = trim($params['room'] ?? '');
        if ($currentRoom !== '') {
            $currentRoom = TikiLib::lib('xmpp')->buildRoomJid($currentRoom);
        }
        $this->set_option('current_room', $currentRoom);

        // Anonymous login
        if (empty($user) && isset($params['anonymous']) && $params['anonymous'] === 'y') {
            $autoOpen = $params['auto_open'] ?? 'n';
            $onXmppPage = ($params['on_xmpp_page'] ?? 'n') === 'y';
            $anonymousMode = $prefs['xmpp_anonymous_mode'] ?? 'community';
            $anonRoom = '';
            $dmTarget = '';

            if ($onXmppPage) {
                // Any page embedding the XMPP plugin sends anonymous visitors into the
                // sitewide community room specifically - never into whatever room that
                // particular page/plugin instance declares (that room is for registered
                // users), so anonymous visitors can never end up in a
                // Registered/Translators-only room via a page's own room= parameter.
                $anonRoom = trim($prefs['xmpp_anonymous_room'] ?? '');
            } elseif ($anonymousMode === 'support') {
                // Site-wide floating button, "Personalized support" mode: a direct 1-1
                // with a person, not a room.
                $dmTarget = trim($params['dm_target'] ?? ($prefs['xmpp_admin_jid'] ?? ''));
            } else {
                // Site-wide floating button, "Community room" mode.
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

        // Prebind (tikitoken)
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
        // A preferred external JID is only usable for real when a reachable
        // connection endpoint is also configured for it - with just the JID and no
        // endpoint, there's no way to reach that server at all.
        $externalAvailable = $jidInfo['isExternal'] && ! empty($xmpp['custom_endpoint']);
        // The external identity is scoped to the page that actually declares an XMPP
        // plugin instance (on_xmpp_page='y', e.g. the community room) - the cookie set
        // by opting in on that page has path=/ and is therefore sent on every page, but
        // the sitewide floating Chat button (mod-func-xmpp.php) never sets on_xmpp_page
        // and must always fall back to the local Tiki identity. Without this check, a
        // single opt-in would silently take over the floating button everywhere else on
        // the site with no password to auto-login it, leaving Chat dead site-wide.
        $onXmppPage = $this->get_option('on_xmpp_page') === 'y';
        // The automatic page-load connection only ever uses the external identity
        // once the visitor has explicitly opted in (via the Chat button, which sets
        // this cookie and reloads) - attempting it automatically on every page load
        // used to break the entire widget outright whenever that external server
        // wasn't actually reachable as a BOSH/WebSocket endpoint from the browser.
        // Tiki still can't supply a password for a genuinely external account
        // (it only manages credentials for local ones), so auto_login stays off in
        // that case and converse.js shows its own login form for this JID.
        $useExternalNow = $onXmppPage && $externalAvailable && ($_COOKIE['tiki_xmpp_external'] ?? '') === '1';

        if ($useExternalNow) {
            $transportOptions = $this->getTransportOptions($xmpp, true);
            $externalPassword = $xmpplib->getExternalPassword($user);

            // A saved, encrypted password (see XMPPLib::saveExternalPassword()) is
            // handed straight to converse.js the same way the local branch below
            // already hands it the local session token - 'authentication: login' +
            // 'password' set, letting converse.js's own SASL login negotiate the
            // connection directly. An earlier version of this routed through XMPP
            // "prebind" (server-side pre-authentication) instead, for a marginally
            // better security property (password never touches the browser at all) -
            // but that path depends on a whole separate, previously-unused service
            // controller (auth_token_access preference, Services_Xmpp_Controller,
            // XmppPrebind's own BOSH-only support) that turned out fragile and hard to
            // debug end-to-end. This is simpler, reuses the exact mechanism already
            // proven to work for the local identity, and needs nothing extra enabled.
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
            // Only advertised to the JS click-handler on the page that can actually use
            // it - elsewhere it's treated as if no external JID were configured at all,
            // so the Chat button always just uses the local identity, as before.
            'external_jid'   => ($onXmppPage && $externalAvailable) ? $jidInfo['jid'] : '',
            'using_external' => 'n',
        ], $transportOptions);
    }

    private function getTransportOptions(array $xmpp, bool $usesExternalJid): array
    {
        global $prefs;

        // Both keys are ALWAYS returned explicitly (even as '' ) rather than merely
        // omitted when unavailable. This class's constructor (load_prefs(), via the
        // xmpp_conversejs_init_json site preference) sets 'websocket_url' to the local
        // Tiki server unconditionally at construction time, before this ever runs - and
        // converse.js prefers websocket_url over bosh_service_url whenever ANY
        // websocket_url is present, stale or not. Omitting the key here (the previous
        // behaviour) left that stale local websocket_url in place, so a connection meant
        // to use an external, BOSH-only endpoint silently connected to the local
        // websocket instead while merely claiming the external JID - explicitly clearing
        // it is the only way to actually route the connection where intended.
        if (! $usesExternalJid) {
            return [
                'websocket_url'    => $prefs['xmpp_ws_url'] ?? '',
                'bosh_service_url' => $prefs['xmpp_server_http_bind'] ?? '',
            ];
        }

        // $xmpp['websocket_url']/['http_bind'] reflect the user's *preferred* JID's
        // endpoint, which get_user_connection_info() overrides to the external server's
        // the moment a custom endpoint is configured for it.
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
            CONVERSEJS_DIST_PATH . '/converse.min.js',
            'lib/xmpp/js/conversejs-tiki.js',
            'lib/xmpp/js/conversejs-tiki-oauth.js',
        ];
        return array_map([$this, 'append_mtime'], $deps);
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

        // Everything this method emits is concatenated with other, unrelated modules'
        // onready scripts into one shared function body (see headerlib::add_jq_onready).
        // Wrapping the whole thing means an XMPP-side runtime error can only ever stop
        // XMPP's own setup, never anything queued after it in that shared script.
        $output = ';(function () {' . PHP_EOL . 'try {' . PHP_EOL;

        if ($prefs['xmpp_conversejs_always_load'] !== 'y') {
            array_map([TikiLib::lib('header'), 'add_jsfile'], $this->get_js_dependencies());

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
        // Including the active jid (not just the Tiki $user) is what makes switching
        // between the local and an external identity - the whole point of the feature
        // above - actually work: converse.js persists its BOSH/session state
        // (persistent_store: localStorage) keyed by nothing more specific than this
        // marker, so without the jid in it, switching identity left the *previous*
        // identity's stale RID/SID/bound-connection state in localStorage for the new
        // page to try to resume - surfacing as "SASL authentication succeeded" followed
        // immediately by a bind that never completes ("do_bind is false") and the widget
        // stuck showing Disconnected, since that old session was for a different account
        // entirely (and likely already expired server-side).
        $chat_session_init = ($user ?: 'anonymous') . '|' . ($options['jid'] ?? '') . '|' . $_SESSION['chat-session-init'];
        $chat_session_init_js = json_encode($chat_session_init);

        $output .= PHP_EOL . ';(function(){';
        $output .= PHP_EOL . "  if (localStorage['chat-session-init'] !== {$chat_session_init_js}) {";
        $output .= PHP_EOL . '      console.warn("Cleaning environment for ' . 'converse session change");';
        $output .= PHP_EOL . "      localStorage['chat-session-init'] = {$chat_session_init_js};";
        $output .= PHP_EOL . '      for (var key in localStorage) { key.match(/(converse|strophe)/) && localStorage.removeItem(key); }';
        $output .= PHP_EOL . '      for (var key in sessionStorage) { key.match(/(converse|strophe)/) && sessionStorage.removeItem(key); }';
        $output .= PHP_EOL . "  }";
        $output .= PHP_EOL . '})();';
        $output .= PHP_EOL;

        if ($this->get_option('view_mode') === 'embedded') {
            // Note: this used to also delete the room's sessionStorage state on every
            // single render, which forced a visible reconnect/redraw flash every time a
            // page with an embedded room was visited (most noticeable on pages people
            // revisit often). Removed - converse.js's own session persistence already
            // keeps the embedded room's state consistent across page loads.
            $options['singleton'] = true;
        }

        $options['auto_open'] = $this->get_option('auto_open', 'n');
        $options['anonymous_auto_open'] = $this->get_option('anonymous_auto_open', 'n');
        // Tiki manages the local identity's room list itself (Tiki group -> room
        // mapping, synced in conversejs-tiki.js's 'connected' handler), so native XMPP
        // bookmarks stay off there to avoid two competing room lists. The external
        // identity has no such Tiki-side mapping - its rooms are whatever it bookmarked
        // on its own server, so native bookmarks/auto-join are the only way to ever see
        // them and are left on for that identity.
        $usingExternal = $this->get_option('using_external') === 'y';
        $options['bookmarks'] = $usingExternal;
        $options['auto_join_bookmarks'] = $usingExternal;

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
            } catch (e) {
                // Storage unavailable - identity won't persist, that's ok.
            }
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

        // Do not use JSON_UNESCAPED_SLASHES to avoid </script> injection.
        $optionString = json_encode($options, JSON_PRETTY_PRINT);
        $output .= ';(function () {' . PHP_EOL;
        $output .= '    try {' . PHP_EOL;
        $output .= '        converse.initialize(' . $optionString . ');' . PHP_EOL;
        $output .= '    } catch (e) {' . PHP_EOL;
        $output .= "        console.error('[tiki-xmpp] FATAL - converse.initialize failed:', e);" . PHP_EOL;
        $output .= '    }' . PHP_EOL;
        $output .= '})();' . PHP_EOL;
        return TikiLib::lib('header')->add_jq_onready($output, 10);
    }
}
