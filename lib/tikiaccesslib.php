<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Yaml\Yaml;

/**
 * TikiAccessLib
 *
 * @uses TikiLib
 *
 */
class TikiAccessLib extends TikiLib
{
    private $noRedirect = false;
    private $noDisplayError = false;
    //used in CSRF protection methods
    private $ticket;
    private $ticketMatch;
    private $originMatch;
    private $base;
    private $origin;
    private $originSource;
    private $logMsg = '';
    private $logInfo = [];
    private $userMsg = '';

    public function preventRedirect($prevent)
    {
        $this->noRedirect = (bool) $prevent;
    }

    /**
     * Prevent the display of errors
     * useful during plugin parsing to mute error redirects
     *
     * @param bool $prevent
     */
    public function preventDisplayError($prevent)
    {
        $this->noDisplayError = (bool) $prevent;
    }

    /**
     * check that the user is admin or has admin permissions
     *
     */
    public function check_admin($user, $feature_name = '')
    {
        global $tiki_p_admin, $prefs;
        require_once('tiki-setup.php');
        // first check that user is logged in
        $this->check_user($user);

        if (($user != 'admin') && ($tiki_p_admin != 'y')) {
            $msg = tra("You do not have the permission that is needed to use this feature");
            if ($feature_name) {
                $msg = $msg . ": " . $feature_name;
            }
            $this->display_error('', $msg, '403');
        }
    }

    /**
     * @param $user
     */
    public function check_user($user)
    {
        global $prefs;
        require_once('tiki-setup.php');

        if (! $user) {
            $title = tra("You are not logged in");
            $this->display_error('', $title, '403');
        }
    }

    /**
     * @param string $user
     * @param array $features
     * @param array $permissions
     * @param string $permission_name
     */
    public function check_page($user = 'y', $features = [], $permissions = [], $permission_name = '')
    {
        require_once('tiki-setup.php');

        if ($features) {
            $this->check_feature($features);
        }
        $this->check_user($user);

        if ($permissions) {
            $this->check_permission($permissions, $permission_name);
        }
    }

    /**
     * check_feature: Checks if a feature or a list of features are activated
     *
     * @param string or array $features If just a string, this method will only test that one. If an array, all features will be tested
     * @param string $feature_name Name that will be printed on the error screen
     * @param string $relevant_admin_panel Admin panel where the feature can be set to 'Y'. This link is provided on the error screen
     * @access public
     * @return void
     *
     */
    public function check_feature($features, $feature_name = '', $relevant_admin_panel = 'features', $either = false)
    {
        global $prefs, $base_uri;
        require_once('tiki-setup.php');

        $perms = Perms::get();

        if ($perms->admin && isset($_REQUEST['check_feature']) && isset($_REQUEST['lm_preference'])) {
            $prefslib = TikiLib::lib('prefs');
            $prefslib->applyChanges((array) $_REQUEST['lm_preference'], $_REQUEST);
        }

        if (! is_array($features)) {
            $features = [$features];
        }

        if ($either) {
            // if anyone will do, start assuming no go and test for feature
            $allowed = false;
        } else {
            // if all is needed, start assuming it's a go and test for feature not on
            $allowed = true;
        }

        foreach ($features as $feature) {
            if (! $either && $prefs[$feature] != 'y') {
                if ($feature_name != '') {
                    $feature = $feature_name;
                }
                $allowed = false;
                break;
            } elseif ($either && $prefs[$feature] == 'y') {
                // test for feature in "anyone will do" case
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            $smarty = TikiLib::lib('smarty');

            if ($perms->admin) {
                $smarty->assign('required_preferences', $features);
            }
            // go back to where we were after the preference change.
            $gobackto = $this->getOriginUrl();
            if (! empty($gobackto)) {
                $smarty->assign('gobackto', $gobackto);
            }

            $msg = tr(
                'Required features: <b>%0</b>. If you do not have permission to activate these features, ask the site administrator.',
                implode(', ', $features)
            );

            $this->display_error('', $msg, 'no_redirect_login');
        }
    }

    /**
     * getOriginUrl: Get the url of where we were
     *
     * @return string
     *
     */
    public function getOriginUrl()
    {
        global $base_uri;

        $gobackto = '';
        if (! empty($base_uri)) {
            $pathInfo = parse_url($base_uri);
            $pathData = explode('/', $pathInfo["path"]);
            $gobackto = end($pathData);

            if (! empty($pathInfo['query'])) {
                $gobackto .= '?' . $pathInfo['query'];
            }
        }
        return $gobackto;
    }

    /**
     * Check permissions for current user and display an error if not granted
     * Multiple perms can be checked at once using an array and all those perms need to be granted to continue
     *
     * @param string|array $permissions     permission name or names (can be old style e.g. 'tiki_p_view' or just 'view')
     * @param string $permission_name       text used in warning if perm not granted
     * @param bool|string $objectType       optional object type (e.g. 'wiki page')
     * @param bool|string $objectId         optional object id (e.g. 'HomePage' or '42' depending on object type)
     */
    public function check_permission($permissions, $permission_name = '', $objectType = false, $objectId = false)
    {
        require_once('tiki-setup.php');

        if (! is_array($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if (false !== $objectType) {
                $applicable = Perms::get($objectType, $objectId);
            } else {
                $applicable = Perms::get();
            }

            if ($applicable->$permission) {
                continue;
            }

            if ($permission_name) {
                $permission = $permission_name;
            }
            $this->display_error('', tra("You do not have the permission that is needed to use this feature:") . " " . $permission, '403', false);
            if (empty($GLOBALS['user']) && empty($_SESSION['loginfrom'])) {
                $_SESSION['loginfrom'] = $_SERVER['REQUEST_URI'];
            }
        }
    }

    /**
     * Check permissions for current user and display an error if not granted
     * Multiple perms can be checked at once using an array and ANY ONE OF those perms only needs to be granted to continue
     *
     * NOTE that you do NOT have to use this to include admin perms, as admin perms automatically inherit the perms they are admin of
     *
     * @param string|array $permissions     permission name or names (can be old style e.g. 'tiki_p_view' or just 'view')
     * @param string $permission_name       text used in warning if perm not granted
     * @param bool|string $objectType       optional object type (e.g. 'wiki page')
     * @param bool|string $objectId         optional object id (e.g. 'HomePage' or '42' depending on object type)
     */
    public function check_permission_either($permissions, $permission_name = '', $objectType = false, $objectId = false)
    {
        require_once('tiki-setup.php');
        $allowed = false;

        if (! is_array($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if (false !== $objectType) {
                $applicable = Perms::get($objectType, $objectId);
            } else {
                $applicable = Perms::get();
            }

            if ($applicable->$permission) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            if ($permission_name) {
                $permission = $permission_name;
            } else {
                $permission = implode(', ', $permissions);
            }

            $this->display_error('', tra("You do not have the permission that is needed to use this feature") . ": " . $permission, '403', false);
        }
    }

    /**
     * check permission, where the permission is normally unset
     *
     */
    public function check_permission_unset($permissions, $permission_name)
    {
        require_once('tiki-setup.php');

        foreach ($permissions as $permission) {
            if (isset($GLOBALS[$permission]) && $GLOBALS[$permission] == 'n') {
                if ($permission_name) {
                    $permission = $permission_name;
                }
                $this->display_error('', tra("You do not have the permission that is needed to use this feature") . ": " . $permission, '403', false);
            }
        }
    }

    /**
     * check page exists
     *
     */
    public function check_page_exists($page)
    {
        require_once('tiki-setup.php');
        if (! $this->page_exists($page)) {
            $this->display_error($page, tra("Page cannot be found"), '404');
        }
    }

    /**
     * Return default security timeout period in seconds.
     * Used in setting the global securityTimeout preference used to determine the expiry period for state-changing
     * forms and related CSRF ticket. Add the timeout class to the submit element of the form to subject a form to
     * the expiration period.
     * @return mixed
     */
    public function getDefaultTimeout()
    {
        global $prefs;
        $timeSetting = isset($prefs['session_lifetime']) && $prefs['session_lifetime'] > 0 ? $prefs['session_lifetime'] * 60
            : ini_get('session.gc_maxlifetime');
        return ! empty($timeSetting) ? min(4 * 60 * 60, $timeSetting) : 4 * 60 * 60;    //4 hours max
    }

    /**
     * CSRF protection - set the ticket on the server/cookie and as a smarty template variable
     *
     * Called by the smarty function {ticket}, which should be placed in all forms with actions that change the
     * database
     * @throws Exception
     */
    public function setTicket()
    {
        global $prefs;

        $setCookie = false;

        if (($prefs['site_short_lived_csrf_tokens'] ?? 'n') === 'y') {
            $this->ticket = TikiLib::lib('tiki')->generate_unique_sequence(32, true);
            $_SESSION['tickets'][$this->ticket] = time();
        } else {
            require_once('lib/setup/cookies.php');
            $this->ticket = $_SESSION['CSRF_TOKEN'] ?? null;
            if (! $this->ticket) { // Check the cookie
                $this->ticket = $_SESSION['CSRF_TOKEN'] = $this->retrieveTicketFromCookie(); // return ticket or null
            }
            if (! $this->ticket) {
                $this->ticket = $_SESSION['CSRF_TOKEN'] = TikiLib::lib('tiki')->generate_unique_sequence(32, true);
                $setCookie = true;
            }
        }

        if ($setCookie || ! getcookie(session_name() . '_CSRF')) {
            $this->setTicketInCookie();
        }

        TikiLib::lib('smarty')->assign('ticket', $this->ticket);
    }

    /**
     * Encrypt the CSRF ticket value
     *
     * @return string
     */
    protected function encryptCsrfTicket(string $value): string
    {
        $key = TikiLib::lib('tiki')->get_site_hash();
        $key = str_pad(substr($key, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES), SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        return base64_encode($nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($value, '', $nonce, $key));
    }

    /**
     * Decrypt the CSRF ticket value
     *
     * @return string|false
     */
    protected function decryptCsrfTicket(string $value)
    {
        $key = TikiLib::lib('tiki')->get_site_hash();
        $key = str_pad(substr($key, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES), SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
        $value = base64_decode($value);
        $nonce = substr($value, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipherText = substr($value, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipherText, '', $nonce, $key);
    }

    /**
     * @return false|string
     */
    protected function retrieveTicketFromCookie()
    {
        $cookie = getcookie(session_name() . '_CSRF');

        if (! $cookie) {
            return false;
        }

        return $this->decryptCsrfTicket($cookie);
    }

    /**
     * @return void
     */
    protected function setTicketInCookie()
    {
        if (php_sapi_name() == 'cli') {
            return;
        }

        // Encrypt ticket
        $cipherTicket = $this->encryptCsrfTicket($this->ticket);

        // you can set even if is the same value
        $session_params = session_get_cookie_params();
        setcookie(session_name() . '_CSRF', $cipherTicket, 0, $session_params['path'], $session_params['domain'], $session_params['secure'], true);
    }


    /**
     * @param bool|string $postConfirm    Whether a confirmation is needed before performing a POST action. Generally,
     *                                    confirms are needed POST requests that cannot be easily undone. Default is false.
     *                                    If a confirm is desired, can be set to true or a string that will be used as
     *                                    the confirmation text.
     * @param bool $getNoConfirm          Allow GET requests without a confirm. If true, then no confirmation is necessary
     *                                    (this should be rare and well controlled). Remember that a ticket should not be
     *                                    included in a GET request so it will need to be provided otherwise or the $checkWhat
     *                                    parameter could be impacted. If false (default), then the method will create a
     *                                    confirmation popup.
     * @param string $checkWhat           'hostTicket' to check origin host domain and ticket - this is recommended and the default
     *                                    'host' to check origin host domain only
     *                                    'ticket' to check ticket only
     * @param bool   $unsetTicket          By default a ticket on the server is unset once used (true) - in rare cases
     *                                     it may need to be reused by setting this parameter to false. Nevertheless, during
     *                                     a single page request the same ticket can be used for multiple uses of checkCsrf()
     *                                     since since a successful check result is maintained throught the request
     * @param string $ticket        Ticket may be provided, e.g., in cases where it is used more than once and is
     *                              stored in a session variable rather than being part of the $_POST. Only tickets
     *                              that originated in a $_POST should be used. Can be used in conjunction with
     *                              $unsetTicket
     * @param string $error         See csrfError for information on error types and uses
     *
     * @return bool                 True if CSRF check passed, false otherwise
     * @throws Services_Exception
     * @see csrfError() for further details on error types and uses.
     * https://dev.tiki.org/CSRF-Protection
     */
    public function checkCsrf(
        $postConfirm = false,
        $getNoConfirm = false,
        $checkWhat = 'hostTicket',
        $unsetTicket = true,
        $ticket = '',
        $error = 'session'
    ) {
        global $prefs;
        if ($prefs['pwa_feature'] == 'y') {
            return true;
        }
        if (TIKI_API) {
            return true;
        }

        if (empty($this->logInfo[__FUNCTION__])) {
            $this->logInfo[__FUNCTION__] = [];
        }

        // allow null value to equate to default for skipped parameters
        $confirmText = '';
        if ($postConfirm === null) {
            $postConfirm = false;
        } elseif ($postConfirm === true || (is_string($postConfirm) && ! empty($postConfirm))) {
            if (is_string($postConfirm) && ! empty($postConfirm)) {
                $confirmText = $postConfirm;
            } else {
                $confirmText = tr('Confirm action');
            }
            $postConfirm = true;
        }
        if ($getNoConfirm === null) {
            $getNoConfirm = false;
        }
        if ($checkWhat === null) {
            $checkWhat = 'hostTicket';
        }
        if ($unsetTicket === null) {
            $unsetTicket = true;
        }
        if ($ticket === null) {
            $ticket = '';
        }

        $this->logInfo[__FUNCTION__][] = [
            'step' => 'args',
            'postConfirm' => $postConfirm,
            'getNoConfirm' => $getNoConfirm,
            'checkWhat' => $checkWhat,
            'unsetTicket' => $unsetTicket,
            'ticket' => $ticket,
            'error' => $error,
        ];

        //send requests requiring confirmation to confirmation form
        if (
            ($getNoConfirm === false && ! $this->requestIsPost())
            || ($postConfirm && (empty($_POST['confirmForm']) || $_POST['confirmForm'] !== 'y'))
        ) {
            $this->logInfo[__FUNCTION__][] = [
                'step' => 'confirmRedirect',
            ];

            $this->confirmRedirect($confirmText, $error);
            //perform check if action post or required confirmation post
        } elseif (
            (! $postConfirm && ($this->isActionPost() || $getNoConfirm === true))
            || ($postConfirm && ! empty($_POST['confirmForm']) && $_POST['confirmForm'] === 'y')
        ) {
            $this->logInfo[__FUNCTION__][] = [
                'step' => 'confirmPost',
            ];

            //return true if check already performed - e.g., multiple checks on tiki-login.php for same request
            if ($this->csrfResult()) {
                return true;
            }
            $result = false;
            //check origin host
            if (in_array($checkWhat, ['hostTicket', 'host'])) {
                $this->originCheck();
                //note result if only checking host
                if ($checkWhat === 'host') {
                    $result = $this->originMatch();
                    $this->logInfo[__FUNCTION__][] = [
                        'step' => 'originMatch',
                        'result' => $result,
                    ];
                }
            }
            //check ticket
            if (in_array($checkWhat, ['hostTicket', 'ticket'])) {
                $this->ticketCheck($unsetTicket, $ticket);
                //note result if only checking ticket
                if ($checkWhat === 'ticket') {
                    $result = $this->ticketMatch();
                    $this->logInfo[__FUNCTION__][] = [
                        'step' => 'ticketMatch',
                        'result' => $result,
                    ];
                }
            }
            //check both host and ticket
            if ($checkWhat === 'hostTicket') {
                $result = $this->csrfResult();
                $this->logInfo[__FUNCTION__][] = [
                    'step' => 'csrfResult',
                    'result' => $result,
                ];
            }
            if ($result) {
                return true;
            } else {
                $this->csrfError($error);
                return false;
            }
        }
        if (! $this->requestIsPost()) {
            $msg = ' ' . tr('The request was not a POST request as required.');
        } else {
            $msg = ' ' . tr('There was no security ticket submitted with the request.');
        }
        $this->logMsg = $msg;
        $this->userMsg = ' ' . $this->logMsg;
        $this->csrfError($error);
        return false;
    }

    /**
     * CSRF protection - Perform origin check to ensure the requesting server matches this server
     *
     * Sets the originMatch property to true or false depending on the result of the check
     *
     * @return void
     */
    private function originCheck()
    {
        // $base_url is usually host + directory
        global $base_url;
        include_once('lib/setup/absolute_urls.php');
        $this->origin = '';
        $this->originSource = 'empty';
        //first check HTTP_ORIGIN
        if (! empty($_SERVER['HTTP_ORIGIN'])) {
            //HTTP_ORIGIN is usually host only without trailing slash
            $this->origin = $_SERVER['HTTP_ORIGIN'];
            $this->originSource = 'HTTP_ORIGIN';
        //then check HTTP_REFERER
        } elseif (! empty($_SERVER['HTTP_REFERER'])) {
            //HTTP_REFERER is usually the full path (host + directory + file + query)
            $this->origin = $_SERVER['HTTP_REFERER'];
            $this->originSource = 'HTTP_REFERER';
        }
        //identify server host + port
        $base = parse_url($base_url);
        $baseHost = isset($base['host']) ? $base['host'] : '';
        $basePort = isset($base['port']) ? ':' . $base['port'] : '';
        $this->base = $baseHost . $basePort;
        //identify requesting host + port
        $origin = parse_url($this->origin);
        $originHost = isset($origin['host']) ? $origin['host'] : '';
        $originPort = isset($origin['port']) ? ':' . $origin['port'] : '';
        $this->origin = $originHost . $originPort;
        //perform compare
        $this->originMatch = $this->base === $this->origin;
        //error message
        if (! $this->originMatch()) {
            if ($this->originSource === 'empty') {
                $this->logMsg .= tr(
                    'The requesting site could not be identified because %0 and %1 were empty.',
                    'HTTP_ORIGIN',
                    'HTTP_REFERER'
                );
                $this->userMsg .= ' ' . tr('The requesting site could not be identified.');
            } else {
                $this->logMsg .= tr(
                    'The %0 host (%1) does not match this server (%2).',
                    $this->originSource,
                    $this->origin,
                    $this->base
                );
                $this->userMsg .= ' ' . tr('The requesting site domain does not match this site\'s domain.');
            }
        }

        if (empty($this->logInfo[__FUNCTION__])) {
            $this->logInfo[__FUNCTION__] = [];
        }
        $this->logInfo[__FUNCTION__][] = [
            'originSource' => $this->originSource,
            'origin' => $this->origin,
            'originMatch' => $this->originMatch
        ];
    }

    /**
     * CSRF protection - Perform ticket check to ensure ticket in the $_POST variable matches the one stored on the
     * server and that the ticket has not expired.
     *
     * Sets the ticketMatch property to true or false depending on the result of the check
     *
     * @param bool   $unsetTicket   Whether to unset $_SESSION ticket after checking. Normally, should unset,
     *                              however infrequently it is easier to use a ticket more than once.
     *                              Other code should unset the ticket after the multiple uses are complete and ensure
     *                              repeated use does not create a vulnerability
     *
     * @param string $ticket        Ticket may be provided, e.g., in cases where it is used more than once and is
     *                              stored in a session variable rather than being part of the $_POST. Only tickets
     *                              that originated in a $_POST should be used.
     */
    private function ticketCheck($unsetTicket, $ticket)
    {
        $logInfo = [];
        $logInfo[] = ['step' => 'args', 'unsetTicket' => $unsetTicket, 'ticket' => $ticket];

        if (! empty($ticket)) {
            $this->ticket = $ticket;
        } elseif (! empty($_POST['ticket'])) {
            $this->ticket = $_POST['ticket'];
        } else {
            $this->ticket = false;
        }
        //just in case url decoding is needed
        if (strpos($this->ticket, '%') !== false) {
            $this->ticket = urldecode($this->ticket);
        }

        $logInfo[] = ['step' => 'final_ticket', 'ticket' => $this->ticket];

        global $prefs;

        if ($this->ticket && ($prefs['site_short_lived_csrf_tokens'] ?? 'n') !== 'y' && ($this->ticket === (isset($_SESSION['CSRF_TOKEN']) ? $_SESSION['CSRF_TOKEN'] : $this->retrieveTicketFromCookie()))) {
            $this->ticketMatch = true;
        } elseif ($this->ticket && ! empty($_SESSION['tickets'][$this->ticket])) {
            //check that request ticket matches server ticket
            //check that ticket has not expired
            global $prefs;
            $maxTime = $prefs['site_security_timeout'];
            $ticketTime = $_SESSION['tickets'][$this->ticket];
            $requestTime = $_SERVER['REQUEST_TIME'];
            if ($ticketTime <= $requestTime && $ticketTime > ($requestTime - $maxTime)) {
                //ticket is still valid
                $this->ticketMatch = true;
            } else {
                //ticket is expired
                $msg = tr('The security ticket matches but is expired.');
                $ticketAgeSeconds = $requestTime - $ticketTime;
                $ticketAgeMinutes = $ticketAgeSeconds < 60 ? '' : ' (' . round($ticketAgeSeconds / 60, 1)
                    . ' ' . tr('minutes') . ')';
                $this->logMsg = $msg . PHP_EOL . '  ' . tr('Age of security ticket:') . ' '
                    . $ticketAgeSeconds . ' ' . tr('seconds') . $ticketAgeMinutes;
                $this->userMsg = ' ' . $msg . ' ' . tr('Reload the page.');
                $this->ticketMatch = false;
            }
            if ($unsetTicket) {
                unset($_SESSION['tickets'][$this->ticket]);
            }
        } else {
            //ticket doesn't match or is missing
            if (! $this->ticket) {
                $msg = tr('The security ticket is missing from the request.');
            } else {
                $msg = tr('The security ticket included in the request does not match a ticket on the server.');
            }
            $this->logMsg = $msg;
            $this->userMsg = ' ' . $this->logMsg . ' ' . tr('Reloading the page may help.');
            $this->ticketMatch = false;
        }

        $logInfo[] = [
            'step' => 'main_check',
            'pref_short' => $prefs['site_short_lived_csrf_tokens'],
            'session_token' => $_SESSION['CSRF_TOKEN'] ?? '',
            'cookie_token' => $this->retrieveTicketFromCookie(),
            'session_tickets' => $_SESSION['tickets'][$this->ticket] ?? '',
            'ticketMatch' => $this->ticketMatch
        ];
        if (empty($this->logInfo[__FUNCTION__])) {
            $this->logInfo[__FUNCTION__] = [];
        }
        $this->logInfo[__FUNCTION__][] = $logInfo;
    }

    /**
     * Generate tiki log entry and user feedback for CSRF errors
     * @param string $error     * 'session'     The regular way of providing feedback (the anti-csrf error message) using the standard Feedback class.
     *                          * 'services'    Used to provide feedback for ajax services.
     *                          * 'page'        Used when the error needs to be shown on a separate page (redirects to a 400 error page).
     *                          * 'none'        Any errors are not displayed
     * @throws Exception
     * @throws Services_Exception
     */
    private function csrfError($error = 'session')
    {
        if ($error !== 'none') {
            global $user;

            $crsfErrorId = 'CRSF' . md5(uniqId($user));
            $this->userMsg = tr('Your attempt was blocked as a suspected malicious operation. If you are a real person and this is an incorrect report, please report to the site administrators with the following code: ') . $crsfErrorId;
            //log message
            $this->csrfPhpErrorLog($this->logMsg);
            $this->csrfSystemLog($crsfErrorId);

            //user feedback
            switch ($error) {
                case 'services':
                    throw new Services_Exception($this->userMsg, 401);
                    break;
                case 'page':
                    Feedback::errorPage(['mes' => $this->userMsg, 'errortype' => 401]);
                    break;
                case 'session':
                default:
                    Feedback::error($this->userMsg);
                    break;
            }
        }
    }

    /**
     * CSRF ticket - Check that the ticket has been matched to the previous ticket set
     *
     * @return bool     Returns true if the request ticket matches the server ticket and is not expired, false if not
     */
    private function ticketMatch()
    {
        return $this->ticketMatch === true;
    }

    /**
     * CSRF origin check - Check that origin matches the server
     *
     * @return bool     Returns true if the request origin matches the origin of the server, false if not
     */
    private function originMatch()
    {
        return $this->originMatch === true;
    }

    /**
     * Check that the request method is POST
     *
     * @return bool     Returns true if the request method is POST, false if not
     */
    public function requestIsPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * CSRF ticket - Return results of ticket and origin match
     *
     * @return bool     Returns true if both matches were successful, false if not
     */
    public function csrfResult()
    {
        return $this->originMatch() && $this->ticketMatch();
    }

    /**
     * CSRF ticket - Get the ticket
     *
     * @return mixed    Returns the ticket if set, false if not
     */
    public function getTicket()
    {
        if (! empty($this->ticket)) {
            return $this->ticket;
        } else {
            return false;
        }
    }

    /**
     * Check that the request is POST and includes a ticket
     *
     * @return bool     Returns true if the request is post and the request includes a ticket, false if not
     */
    public function isActionPost()
    {
        if (TIKI_API) {
            return $this->requestIsPost();
        } else {
            return ($this->requestIsPost() && ! empty($_POST['ticket']));
        }
    }

    /**
     * Utility method for checkCsrfForm and also used in infrequent cases where a database-changing action is initiated
     * through an outside link, for example an unsubscribe link, in which case an additional validation method should
     * also be applied
     *
     * @param string $confirmText       The confirm question posed to the user.
     * @param string $error             Options include 'session', 'none', 'services' and 'page'. Used in csrfError()
     *
     * @return bool                     True if conformation was accepted, false otherwise
     * @throws Services_Exception
     * @see csrfError() for further details on error types and uses.
     */
    private function confirmRedirect($confirmText, $error = 'session')
    {
        if (empty($_POST['confirmForm']) || $_POST['confirmForm'] !== 'y') {
            if (empty($confirmText)) {
                $confirmText = tr('Confirm action');
            }
            // Display the confirmation in the main tiki.tpl template
            $smarty = TikiLib::lib('smarty');
            if (empty($smarty->getTemplateVars('confirmaction'))) {
                $smarty->assign('confirmaction', $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF']);
            }
            $smarty->assign('post', $_REQUEST);
            $smarty->assign('print_page', 'n');
            $smarty->assign('title', tra('Please confirm action'));
            $smarty->assign('confirmation_action', 'y');
            $smarty->assign('confirmation_text', $confirmText);
            $smarty->assign('mid', 'confirm.tpl');
            $smarty->display('tiki.tpl');
            die();
        } else {
            return $this->checkCsrf($error);
        }
    }

    /**
     * Utility to compose and write the error message from CSRF errors to the server php error log, adding on
     * certain information regarding the environment. Broken into two pieces since the the GET and POST
     * parameters have the potential to exceed the character limit.
     *
     * @param $msg  string      Description of the specific error which will be placed first ahead of
     *                     the environmental information
     */
    private function csrfPhpErrorLog($msg)
    {
        global $prefs;
        error_log(PHP_EOL
            . '**** ' . tr('Start CSRF error from') . $_SERVER['SERVER_NAME'] . ' *****' . PHP_EOL
            . '  ' . $msg . PHP_EOL
            . '  site_security_timeout' . tr('preference:') . $prefs['site_security_timeout']
            . tr('seconds') . '(' . $prefs['site_security_timeout'] / 60 . ' minutes)' . PHP_EOL
            . (isset($_SERVER['SCRIPT_NAME']) ? '  SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME'] . PHP_EOL : '')
            . (isset($_SERVER['REQUEST_URI']) ? '  REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . PHP_EOL : '')
            . (isset($_SERVER['HTTP_ORIGIN']) ? '  HTTP_ORIGIN: ' . $_SERVER['HTTP_ORIGIN'] . PHP_EOL : '')
            . (isset($_SERVER['HTTP_REFERER']) ? '  HTTP_REFERER: ' . $_SERVER['HTTP_REFERER'] . PHP_EOL : '')
            . (isset($_SERVER['REQUEST_METHOD']) ? '  REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD'] : '') . PHP_EOL);
        $get = count($_GET) ? json_encode($_GET, JSON_PRETTY_PRINT) : tr('empty');
        $post = count($_POST) ? json_encode($_POST, JSON_PRETTY_PRINT) : tr('empty');
        error_log(PHP_EOL
            . '  $_GET: ' . $get . PHP_EOL
            . '  $_POST: ' . $post . PHP_EOL
            . '**** ' . tr('End CSRF error from') . $_SERVER['SERVER_NAME'] . ' *****');
    }


    /**
     * ***** Note: Being replaced by checkCsrf method above *************
     *
     *
     * @param string $confirmation_text Custom text to use if a confirmation page is brought up first
     * @param bool $returnHtml Set to false to not use the standard confirmation page and to not use the
     *                          standard error page. Suitable for popup confirmations when set to false.
     * @param bool $errorMsg        Set to true to have the Feedback error message sent automatically
     * @return array|bool
     * @throws Exception
     * @throws Services_Exception
     * @deprecated replaced by checkCsrfForm() and checkCsrf()
     * @see checkCsrf()             For post validation with or without confirmation check
     */
    public function check_authenticity($confirmation_text = '', $returnHtml = true, $errorMsg = false)
    {
        $check = true;
        if (empty($_POST['confirmForm']) || $_POST['confirmForm'] !== 'y') {
            if ($this->checkCsrf(null, null, 'host')) {
                if ($returnHtml) {
                    //redirect to a confirmation page
                    if (empty($confirmation_text)) {
                        $confirmation_text = tra('Confirm action');
                    }
                    if (empty($confirmaction)) {
                        $confirmaction = $_SERVER['PHP_SELF'];
                    }
                    // Display the confirmation in the main tiki.tpl template
                    $smarty = TikiLib::lib('smarty');
                    $smarty->assign('post', $_REQUEST);
                    $smarty->assign('print_page', 'n');
                    $smarty->assign('confirmation_action', 'y');
                    $smarty->assign('confirmation_text', $confirmation_text);
                    $smarty->assign('confirmaction', $confirmaction);
                    $smarty->assign('mid', 'confirm.tpl');
                    $smarty->display('tiki.tpl');
                    die();
                } else {
                    //return ticket to be placed in a form with other code
                    return ['ticket' => $this->ticket];
                }
            } else {
                $check = false;
            }
        } elseif (! empty($_POST['confirmForm']) && $_POST['confirmForm'] === 'y') {
            $check = $this->checkCsrf();
        }
        if (! $check) {
            if ($returnHtml) {
                $smarty = TikiLib::lib('smarty');
                $smarty->display('error.tpl');
                exit();
            } else {
                return false;
            }
        }
    }

    /**
     * @param $page
     * @param string $errortitle
     * @param string $errortype
     * @param bool $enableRedirect
     * @param string $message
     * @throws Exception
     */
    public function display_error($page, $errortitle = "", $errortype = "", $enableRedirect = true, $message = '')
    {
        if ($this->noDisplayError) {
            return;
        }

        global $prefs, $tikiroot, $user;
        require_once('tiki-setup.php');
        $userlib = TikiLib::lib('user');
        $smarty = TikiLib::lib('smarty');

        // Don't redirect when calls are made for web services
        if (
            $enableRedirect && $prefs['feature_redirect_on_error'] == 'y' && ! $this->is_machine_request()
                && $tikiroot . $prefs['tikiIndex'] != $_SERVER['PHP_SELF']
                && ( $page != $userlib->get_user_default_homepage($user) || $page === '' )
        ) {
            $this->redirect($prefs['tikiIndex']);
        }

        $detail = [
                'code' => $errortype,
                'errortitle' => $errortitle,
                'message' => $message,
        ];

        if (! isset($errortitle)) {
            $detail['errortitle'] = tra('unknown error');
        }

        if (empty($message)) {
            $detail['message'] = $detail['errortitle'];
        }

        // Display the template
        switch ($errortype) {
            case '404':
                header("HTTP/1.0 404 Not Found");
                $detail['page'] = $page;
                $detail['message'] .= ' (404)';
                break;

            case '403':
                header("HTTP/1.0 403 Forbidden");
                break;

            case '503':
                header("HTTP/1.0 503 Service Unavailable");
                break;

            default:
                $errortype = (int) $errortype;
                $title = strip_tags($detail['errortitle']);

                if (! $errortype) {
                    $errortype = 403;
                    $title = 'Forbidden';
                }
                header("HTTP/1.0 $errortype $title");
                break;
        }

        if ($this->is_serializable_request()) {
            if (! TIKI_API) {
                Feedback::error($errortitle, true);
            }

            $this->output_serialized($detail);
        } elseif ($this->is_xml_http_request()) {
            $smarty->assign('detail', $detail);
            $smarty->display('error-ajax.tpl');
        } else {
            if (
                ($errortype == 401 || $errortype == 403) &&
                        empty($user) &&
                        ($prefs['permission_denied_login_box'] == 'y' || ! empty($prefs['permission_denied_url']))
            ) {
                if (empty($_SESSION['loginfrom'])) {
                    $_SESSION['loginfrom'] = $_SERVER['REQUEST_URI'];
                }
                if ($prefs['login_autologin'] == 'y' && $prefs['login_autologin_redirectlogin'] == 'y' && ! empty($prefs['login_autologin_redirectlogin_url'])) {
                    $this->redirect($prefs['login_autologin_redirectlogin_url']);
                }
            }

            $smarty->assign('errortitle', $detail['errortitle']);
            $smarty->assign('msg', $detail['message']);
            $smarty->assign('errortype', $detail['code']);
            if (isset($detail['page'])) {
                $smarty->assign('page', $page);
            }
            $smarty->display("error.tpl");
        }
        die;
    }

    /**
     * @param string $page
     * @return string
     */
    public function get_home_page($page = '')
    {
        global $prefs, $use_best_language, $user;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');

        if (! isset($page) || $page == '') {
            if ($prefs['useGroupHome'] == 'y') {
                $groupHome = $userlib->get_user_default_homepage($user);
                if ($groupHome) {
                    $page = $groupHome;
                } else {
                    $page = $prefs['wikiHomePage'];
                }
            } else {
                $page = $prefs['wikiHomePage'];
            }
            if (! $tikilib->page_exists($prefs['wikiHomePage'])) {
                $tikilib->create_page($prefs['wikiHomePage'], 0, '', $this->now, 'Tiki initialization');
            }
            if ($prefs['feature_best_language'] == 'y') {
                $use_best_language = true;
            }
        }
        return $page;
    }

    /**
     * Returns an absolute URL for the given one
     *
     * Inspired on \ZendOpenId\OpenId::absoluteUrl
     *
     * @param string $url absolute or relative URL
     * @return string
     */
    public static function absoluteUrl($url)
    {
        if (empty($url)) {
            return self::selfUrl();
        } elseif (! preg_match('|^([^:]+)://|', $url)) {
            if (preg_match('|^([^:]+)://([^:@]*(?:[:][^@]*)?@)?([^/:@?#]*)(?:[:]([^/?#]*))?(/[^?]*)?((?:[?](?:[^#]*))?(?:#.*)?)$|', self::selfUrl(), $reg)) {
                $scheme = $reg[1];
                $auth = $reg[2];
                $host = $reg[3];
                $port = $reg[4];
                $path = $reg[5];
                $query = $reg[6];
                if ($url[0] == '/') {
                    return $scheme
                        . '://'
                        . $auth
                        . $host
                        . (empty($port) ? '' : (':' . $port))
                        . $url;
                } else {
                    $dir = dirname($path);
                    return $scheme
                        . '://'
                        . $auth
                        . $host
                        . (empty($port) ? '' : (':' . $port))
                        . (strlen($dir) > 1 ? $dir : '')
                        . '/'
                        . $url;
                }
            }
        }
        return $url;
    }

    /**
     * Returns a full URL that was requested on current HTTP request.
     *
     * Inspired on \ZendOpenId\OpenId::selfUrl
     *
     * @return string
     */
    public static function selfUrl()
    {
        $url = '';
        $port = '';

        if (isset($_SERVER['HTTP_HOST'])) {
            if (($pos = strpos($_SERVER['HTTP_HOST'], ':')) === false) {
                if (isset($_SERVER['SERVER_PORT'])) {
                    $port = ':' . $_SERVER['SERVER_PORT'];
                }
                $url = $_SERVER['HTTP_HOST'];
            } else {
                $url = substr($_SERVER['HTTP_HOST'], 0, $pos);
                $port = substr($_SERVER['HTTP_HOST'], $pos);
            }
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $url = $_SERVER['SERVER_NAME'];
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];
            }
        }

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url = 'https://' . $url;
            if ($port == ':443') {
                $port = '';
            }
        } else {
            $url = 'http://' . $url;
            if ($port == ':80') {
                $port = '';
            }
        }

        $url .= $port;
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $url .= $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $url .= $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['SCRIPT_URL'])) {
            $url .= $_SERVER['SCRIPT_URL'];
        } elseif (isset($_SERVER['REDIRECT_URL'])) {
            $url .= $_SERVER['REDIRECT_URL'];
        } elseif (isset($_SERVER['PHP_SELF'])) {
            $url .= $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['SCRIPT_NAME'])) {
            $url .= $_SERVER['SCRIPT_NAME'];
            if (isset($_SERVER['PATH_INFO'])) {
                $url .= $_SERVER['PATH_INFO'];
            }
        }
        return $url;
    }

    /**
     * Utility function redirect the browser location to another url

     * @param string $url       The target web address
     * @param string $msg       An optional message to display
     * @param int $code         HTTP code
     * @param string $msgtype   Type of message which determines styling (e.g., success, error, warning, etc.)
     */
    public function redirect($url = '', $msg = '', $code = 302, $msgtype = '')
    {
        global $prefs;

        if ($this->noRedirect) {
            return;
        }

        // TODO: Validate URL
        if ($url == '') {
            $url = $prefs['tikiIndex'];
        }

        if (trim($msg)) {
            $session = session_id();
            if (empty($session)) {
                // Can happen if session_silent is enabled. But does any instance enable session_silent?
                // Removing this case would allow removing the $msg parameters and just have callers using Feedback::add() before calling redirect(). Chealer 2017-08-16
                $start = strpos($url, '?') ? '&' : '?';
                $url = $start . 'msg=' . urlencode($msg) . '&msgtype=' . urlencode($msgtype);
            } else {
                $_SESSION['msg'] = $msg;
                $_SESSION['msgtype'] = $msgtype;
            }
        }

        TikiLib::events()->trigger('tiki.process.redirect');

        session_write_close();
        if (headers_sent()) {
            echo "<script>document.location.href='" . smarty_modifier_escape($url, 'javascript') . "';</script>\n";
        } else {
            @ob_end_clean(); // clear output buffer
            if ($prefs['feature_obzip'] == 'y') {
                @ob_start('ob_gzhandler');
            }
            header("HTTP/1.0 $code Found");
            header("Location: $url");
        }
        exit();
    }

    /**
     * @param $message
     */
    public function flash($message)
    {
        $this->redirect($_SERVER['REQUEST_URI'], $message);
    }

    /**
     * Authorizes access to Tiki RSS feeds via user/password embedded in a URL
     * e.g. https://joe:secret@localhost/tiki/tiki-calendars_rss.php?ver=2
     *              ~~~~~~~~~~
     *
     * @param array the permissions that needs to be checked against (e.g. tiki_p_view)
     *
     * @return null if authorized, otherwise an array(msg,header)
     *              where msg can be displayed, and header decides whether to
     *              send 401 Unauthorized headers.
     */

    public function authorize_rss($rssrights)
    {
        global $user, $prefs;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');
        $perms = Perms::get();
        $result = ['msg' => tra("You do not have permission to view this section"), 'header' => 'n'];

        // if current user has appropriate rights, allow.
        foreach ($rssrights as $perm) {
            if ($perms->$perm) {
                return;
            }
        }

        // deny if no basic auth allowed.
        if ($prefs['feed_basic_auth'] != 'y') {
            return $result;
        }

        //login is needed to access the contents
        $https_mode = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';

        //refuse to authenticate in plaintext if https_login_required.
        if ($prefs['https_login_required'] == 'y' && ! $https_mode) {
            $result['msg'] = tra("For the security of your password, direct access to the feed is only available via HTTPS");
            return $result;
        }

        if ($this->http_auth()) {
            $perms = Perms::get();
            foreach ($rssrights as $perm) {
                if ($perms->$perm) {
                    // if user/password and the appropriate rights are correct, allow.
                    return;
                }
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function http_auth()
    {
        global $tikidomain, $user;
        $userlib = TikiLib::lib('user');
        $smarty = TikiLib::lib('smarty');

        if (! $tikidomain) {
            $tikidomain = "Default";
        }

        if (! isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="' . $tikidomain . '"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }

        $attempt = $_SERVER['PHP_AUTH_USER'] ;
        $pass = $_SERVER['PHP_AUTH_PW'] ;
        list($res, $rest) = $userlib->validate_user_tiki($attempt, $pass);

        if ($res == USER_VALID) {
            global $_permissionContext;

            $_permissionContext = new Perms_Context($attempt, false);
            $_permissionContext->activate(true);

            return true;
        } else {
            header('WWW-Authenticate: Basic realm="' . $tikidomain . '"');
            header('HTTP/1.0 401 Unauthorized');
            return false;
        }
    }

    /**
     * @param bool $acceptFeed
     * @return array
     */
    public static function get_accept_types($acceptFeed = false)
    {
        if (TIKI_API) {
            return ['json' => 'application/json'];
        }

        $accept = explode(',', $_SERVER['HTTP_ACCEPT'] ?? '');

        if (isset($_REQUEST['httpaccept'])) {
            $accept = array_merge(explode(',', $_REQUEST['httpaccept']), $accept);
        }

        $types = [];

        foreach ($accept as $type) {
            $known = null;

            if (strpos($type, $t = 'application/json') !== false) {
                $known = 'json';
            } elseif (strpos($type, $t = 'text/javascript') !== false) {
                $known = 'json';
            } elseif (strpos($type, $t = 'text/x-yaml') !== false) {
                $known = 'yaml';
            } elseif (strpos($type, $t = 'application/rss+xml') !== false) {
                $known = 'rss';
            } elseif (strpos($type, $t = 'application/atom+xml') !== false) {
                $known = 'atom';
            }

            if ($known && ! isset($types[$known])) {
                $types[$known] = $t;
            }
        }

        if (empty($types)) {
            $types['html'] = 'text/html';
        }

        return $types;
    }

    /**
     * @return bool
     */
    public static function is_machine_request()
    {
        foreach (self::get_accept_types() as $name => $full) {
            switch ($name) {
                case 'html':
                    return false;
                case 'json':
                case 'yaml':
                    return true;
            }
        }

        return false;
    }

    /**
     * @param bool $acceptFeed
     * @return bool
     */
    public static function is_serializable_request($acceptFeed = false)
    {
        foreach (self::get_accept_types($acceptFeed) as $name => $full) {
            switch ($name) {
                case 'json':
                case 'yaml':
                    return true;
                case 'rss':
                case 'atom':
                    if ($acceptFeed) {
                        return true;
                    }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function is_xml_http_request()
    {
        return ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Will process the output by serializing in the best way possible based on the request's accept headers.
     * To output as an RSS/Atom feed, a descriptor may be provided to map the array data to the feed's properties
     * and to supply additional information. The descriptor must contain the following keys:
     * [feedTitle] Feed's title, static value
     * [feedDescription] Feed's description, static value
     * [entryTitleKey] Key to lookup for each entry to find the title
     * [entryUrlKey] Key to lookup to find the URL of each entry
     * [entryModificationKey] Key to lookup to find the modification date
     * [entryObjectDescriptors] Optional. Array containing two key names, object key and object type to lookup missing information (url and title)
     */
    public static function output_serialized($data, $feed_descriptor = null)
    {
        foreach (self::get_accept_types(! is_null($feed_descriptor)) as $name => $full) {
            switch ($name) {
                case 'json':
                    header("Content-Type: $full");
                    $data = json_encode($data);
                    if ($data === false) {
                        $error = '';
                        switch (json_last_error()) {
                            case JSON_ERROR_NONE:
                                $error = 'json_encode - No errors';
                                break;
                            case JSON_ERROR_DEPTH:
                                $error = 'json_encode - Maximum stack depth exceeded';
                                break;
                            case JSON_ERROR_STATE_MISMATCH:
                                $error = 'json_encode - Underflow or the modes mismatch';
                                break;
                            case JSON_ERROR_CTRL_CHAR:
                                $error = 'json_encode - Unexpected control character found';
                                break;
                            case JSON_ERROR_SYNTAX:
                                $error = 'json_encode - Syntax error, malformed JSON';
                                break;
                            case JSON_ERROR_UTF8:
                                $error = 'json_encode - Malformed UTF-8 characters, possibly incorrectly encoded';
                                break;
                            default:
                                $error = 'json_encode - Unknown error';
                                break;
                        }
                        throw new Exception($error);
                    }
                    if (isset($_REQUEST['callback'])) {
                        $data = $_REQUEST['callback'] . '(' . $data . ')';
                    }
                    echo $data;
                    return;

                case 'yaml':
                    header("Content-Type: $full");
                    echo Yaml::dump($data, 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
                    return;

                case 'rss':
                    $rsslib = TikiLib::lib('rss');
                    $writer = $rsslib->generate_feed_from_data($data, $feed_descriptor);
                    $writer->setFeedLink(self::tikiUrl($_SERVER['REQUEST_URI']), 'rss');

                    header('Content-Type: application/rss+xml');
                    echo $writer->export('rss');
                    return;

                case 'atom':
                    $rsslib = TikiLib::lib('rss');
                    $writer = $rsslib->generate_feed_from_data($data, $feed_descriptor);
                    $writer->setFeedLink(self::tikiUrl($_SERVER['REQUEST_URI']), 'atom');

                    header('Content-Type: application/atom+xml');
                    echo $writer->export('atom');
                    return;

                case 'html':
                    header("Content-Type: $full");
                    echo $data;
                    return;
            }
        }
    }

    /**
     * @param $filename string The file name directory structure to test. May be an absolute or relative file path.
     *
     * @return bool|null Return true upon file access success, false upon failure, and null if the file does not exist.
     */
    public function isFileWebAccessible(string $filename): ?bool
    {
        global $tikipath, $base_url_http, $base_url_https;
        // if the directory is within the Tiki root, then remove the prefixed Tiki root
        if (0 === strpos($filename, $tikipath)) {
            $filename = substr($filename, strlen($tikipath));
        }

        // if the file does not exist, then don't bother proceeding further
        if (! file_exists($filename)) {
            return null;
        }
        // if the file is outside the Tiki Root
        if ($filename[0] === '/') {
            return false;
        }

        // now load try accessing the file and check for a 200 (ok) or 300 (moved)
        // lets check http first
        $response = @get_headers($base_url_http . $filename);
        if (! isset($response)) {
            $response = substr($response[0], 9, 1);
            if ($response == '2' || $response == '3') {
                return true;
            } else {  // now we try https, just to be sure.
                $response = @get_headers($base_url_https . $filename);
                $response = substr($response[0], 9, 1);
                if ($response == '2' || $response == '3') {
                    return true;
                }
            }
        }
        // if all else has failed, conclude that the file is not accessible
        return false;
    }

    /**
     * @param string $mode closed|busy
     *
     * @return void
     */
    public function showSiteClosed(string $mode, string $errorMessage = '')
    {
        global $prefs, $error_login;

        switch ($mode) {
            case 'busy':
                $title = $prefs['site_busy_title'];
                $error = $prefs['site_busy_msg'];
                $prefs['site_closed'] = 'y';    // tell the rest of tiki we're closed

                break;
            case 'maintenance':
                $title = tra('Maintenance');
                $error = $errorMessage;
                $prefs['site_closed'] = 'y';

                break;
            case 'closed':
            default:
                $title = $prefs['site_closed_title'];
                $error = $prefs['site_closed_msg'];
        }

        if (TIKI_API) {
            $this->output_serialized(['error' => $error, 'title' => $title]);
            die;
        }

        if ($prefs['twoFactorAuth'] === 'y' && $error_login === tra('Invalid two-factor code ')) {
            $twoFactorAuthCode = '<div class="pass tiki-form-group row mx-0 clearfix">
    <label for="login-2fa">Two-factor Authenticator Code</label>
    <input type="text" name="twoFactorAuthCode" autocomplete="off" class="form-control" id="login-2fa">
</div>
';
            $error_login = '';
        } else {
            $twoFactorAuthCode = '';
        }

        $style_alert = '' . $error_login != '' ? 'alert alert-danger' : '';
        $style_alert_btn = '' . $error_login != '' ? '' : 'display:none';

        require_once('lib/setup/cookies.php');
        $this->setTicket();

        $login = '<form class="form-detail" id="myform" action="tiki-login.php?page=tikiIndex" method="post">
                   <div class="form-row">
                       <label>User</label>
                       <input type="text" name="user" id="your_email" class="input-text" required>
                   </div>
                   ' . $twoFactorAuthCode . '
                   <div class="form-row form-row-1">
                       <label for="password">Password</label>
                       <input type="password" name="pass" id="password" class="input-text" required>
                   </div>
                   <div class="' . $style_alert . '" role="alert">' . $error_login . '
                       <button type="button" class="close" style="' . $style_alert_btn . '" data-bs-dismiss="alert" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                       </button>
                   </div>
                   <div class="form-row-last">
                       <input type="hidden" class="ticket" name="ticket" value="' . $this->ticket . '" />
                       <input type="hidden" name="confirmForm" value="y" />
                       <input type="submit" name="login" value="Log in" class="register">
                   </div>
       </form>';

        if (file_exists('themes/base_files/other/site_closed_local.html')) {
            $html = file_get_contents('themes/base_files/other/site_closed_local.html');
        } else {
            $html = file_get_contents('themes/base_files/other/site_closed.html');
        }

        $html = str_replace('{error}', $error, $html);
        $html = str_replace('{title}', $title, $html);
        $html = str_replace('{login}', $login, $html);
        header("HTTP/1.0 503 Service Unavailable");

        die($html);
    }

    /**
     * Save CSRF error to system log
     *
     * @param string $crsfErrorId
     * @return null
     */
    private function csrfSystemLog($crsfErrorId)
    {
        global $prefs, $user;

        $tikilib = TikiLib::lib('tiki');
        $logslib = TikiLib::lib('logs');

        $redactPass = function (&$item, $key) {
            if (strpos($key, 'pass') !== false) {
                $item = '** ' . tr('redacted') . ' **';
            }
        };

        $get = count($_GET) ? $_GET : tr('empty');
        if (is_array($get)) {
            array_walk($get, $redactPass);
        }
        $post = count($_POST) ? $_POST : tr('empty');
        if (is_array($post)) {
            array_walk($post, $redactPass);
        }

        $logCrsf = [
            'serverName' => $_SERVER['SERVER_NAME'],
            'userMessage' => $this->userMsg,
            'internalMessage' => $this->logMsg,
            'internalInfo' => $this->logInfo,
            'siteSecurityTimeout' => tr('preference:') . ' ' . $prefs['site_security_timeout'] . tr('seconds') . ' (' . $prefs['site_security_timeout'] / 60 . ' minutes)',
            'scriptName' => $_SERVER['SCRIPT_NAME'],
            'requestURI' => $_SERVER['REQUEST_URI'],
            'httpOrigin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null,
            'httpReferer' => $_SERVER['HTTP_REFERER'],
            'requestMethod' => $_SERVER['REQUEST_METHOD'],
            'queryString' => $_SERVER['QUERY_STRING'] ?? tr('empty'),
            'get' => $get,
            'post' => $post
        ];
        $logslib->add_action('CRSF Error', 'system', 'system', $crsfErrorId, '', '', '', '', '', '', $logCrsf);
    }
}
