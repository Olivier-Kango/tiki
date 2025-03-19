<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\CookieConsent;

class CookieConsentLib
{
    // Define constants for cookie categories
    public const BUILTIN_COOKIE_CATEGORY_ESSENTIAL = 'essential';
    public const BUILTIN_COOKIE_CATEGORY_ANALYTICS = 'analytics';
    public const BUILTIN_COOKIE_CATEGORY_MARKETING = 'marketing';
    public const BUILTIN_COOKIE_CATEGORY_FUNCTIONAL = 'functional';

    public const COOKIE_CONSENT_NAME = 'cookie_consent';

    private static $cookiePath = '';
    private static $cookieSecure = true; // Use HTTPS

    /**
     * Retrieve available cookie categories information with translations.
     *
     * @return array The available cookie categories, including translated names and descriptions.
     */
    public static function getCookieCategories(): array
    {
        return [
            self::BUILTIN_COOKIE_CATEGORY_ESSENTIAL => [
                'name' => tra('Essential'),
                'description' => tra('Necessary cookies for the website to function correctly.')
            ],
            self::BUILTIN_COOKIE_CATEGORY_ANALYTICS => [
                'name' => tra('Analytics'),
                'description' => tra('Cookies that track website usage and performance.')
            ],
            self::BUILTIN_COOKIE_CATEGORY_MARKETING => [
                'name' => tra('Marketing'),
                'description' => tra('Cookies used for targeted advertising and campaigns.')
            ],
            self::BUILTIN_COOKIE_CATEGORY_FUNCTIONAL => [
                'name' => tra('Functional'),
                'description' => tra('Cookies that enable additional functionality, such as personalization and enhanced user experience.')
            ]
        ];
    }

    /**
     * Initialize the consent preferences based on existing cookies.
     *
     * @return array The initialized consent preferences.
     */
    public static function initializeConsentPreferences()
    {
        global $tikilib, $user, $prefs;
        $cookieName = self::COOKIE_CONSENT_NAME;
        $defaultPreferences = [
            'action' => 'customized',
            'consentGiven' => false, // Helps to determine if the user has given consent or not
            'categories' => array_map(fn() => false, array_keys(self::getCookieCategories())) // Default to false for all categories
        ];

        // First, try to read the consent cookie
        $consentCookie = self::getCookie($cookieName);
        if ($consentCookie) {
            $decoded = json_decode($consentCookie, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // If the user is logged in, try to load their stored preference
        if ($user) {
            $userPreferences = $tikilib->get_user_preference($user, 'cookie_consent_user_pref', '');
            if ($userPreferences) {
                $decoded = json_decode($userPreferences, true);
                if (is_array($decoded)) {
                    // Sync the cookie with the user preference
                    self::setConsentPreferences($decoded);
                    return $decoded;
                }
            }
        }
        return $defaultPreferences;
    }

    /**
     * Get the current user consent preferences.
     *
     * @param string|null $category The category to check (e.g., 'analytics'). If null, returns the main consent preferences.
     * @return array|null User preferences for the specified category, or the main consent preferences if no category is specified. Returns null if no preferences are set.
     */
    public static function getConsentPreferences(?string $category = null)
    {
        $preferences = self::initializeConsentPreferences();
        return $category ? ($preferences['categories'][$category] ?? null) : $preferences;
    }

    /**
     * Set or update the user consent preferences.
     *
     * @param array $preferences An associative array with consent preferences, e.g.:
     *                           [
     *                               'consentGiven' => 'true',
     *                               'action' => 'acceptAll',
     *                               'categories' => [
     *                                   'essential' => true,
     *                                   'analytics' => true,
     *                                   'marketing' => false
     *                               ]
     *                           ]
     * @return void
     */
    public static function setConsentPreferences(array $preferences)
    {
        global $prefs, $tikilib, $user;

        // Validate each category
        foreach ($preferences['categories'] as $category => $status) {
            if (! array_key_exists($category, self::getCookieCategories()) || ! is_bool($status)) {
                throw new \InvalidArgumentException('Invalid category or status.');
            }
        }

        $preferences['consentGiven'] = true; // in case trying to update but consentGiven is not set
        // Store the preferences in the cookie
        self::setCookieSection(
            self::COOKIE_CONSENT_NAME,
            json_encode($preferences),
            '',
            time() + (86400 * $prefs['cookie_consent_expires']),
            self::$cookiePath,
            '',
            self::$cookieSecure
        );

        // Save the preferences in the user's prefs if logged in
        if ($user) {
            $tikilib->set_user_preference($user, 'cookie_consent_user_pref', json_encode($preferences));
        }
    }

    /**
     * Check if a specific category is allowed based on current user cookie consent preferences.
     *
     * @param string $category The category to check (e.g., 'analytics').
     * @return bool True if the category is explicitly allowed (true), false otherwise.
     */
    private static function isCategoryAllowed(string $category)
    {
        if (! array_key_exists($category, self::getCookieCategories())) {
            throw new \InvalidArgumentException('Invalid category.');
        }
        // throw an error when category not provided
        if (empty($category)) {
            throw new \InvalidArgumentException('Category not provided.');
        }
        // Retrieve the stored consent for the given category.
        // getConsentPreferences($category) should return
        // true (allowed), false (refused) or null (not answered).
        return self::getConsentPreferences($category) === true;
    }

    /**
     * Public wrapper to check if a specific category is allowed.
     *
     * @param string $category The category to check (e.g., 'analytics').
     * @return bool True if the category is allowed, false otherwise.
     */
    public static function checkAllowedCookieCategory(string $category)
    {
        return self::isCategoryAllowed($category);
    }

    /**
     * Set a cookie at runtime with the specified category, if allowed.
     *
     * @return bool Returns true if the cookie was set, false if consent was not given.
     */
    public static function tikiSetCookie(
        string $name,
        string $value,
        string $category,
        int $expire = 0,
        string $section = '',
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false
    ): bool {
        // Retrieve the stored consent preferences
        $preferences = self::initializeConsentPreferences();
        // Check if the category is allowed
        if (isset($preferences['categories'][$category]) && $preferences['categories'][$category] === true) {
            self::setCookieSection(
                $name,
                $value,
                $section,
                $expire,
                $path,
                $domain,
                $secure
            );
            return true; // Consent was given, cookie was set
        }
        return false; // Consent was NOT given, cookie was NOT set
    }

    private static function setCookieSection($name, $value, $section = '', $expire = 0, $path = '', $domain = '', $secure = '')
    {
        global $feature_no_cookie;

        if (TIKI_API) {
            return;
        }

        if ($section) {
            $valSection = self::getCookie($section);
            $name2 = '@' . $name . ':';
            if ($valSection) {
                if (preg_match('/' . preg_quote($name2) . '/', $valSection)) {
                    $valSection  = preg_replace('/' . preg_quote($name2) . '[^@;]*/', $name2 . $value, $valSection);
                } else {
                    $valSection = $valSection . $name2 . $value;
                }
                self::setCookieSection($section, $valSection, '', $expire, $path, $domain, $secure);
            } else {
                $valSection = $name2 . $value;
                self::setCookieSection($section, $valSection, '', $expire, $path, $domain, $secure);
            }
        } else {
            if ($feature_no_cookie) {
                $_SESSION['tiki_cookie_jar'][$name] = $value;
            } else {
                setcookie($name, $value, $expire, $path, $domain, $secure);
            }
        }
    }

    public static function getCookie($name, $section = null, $default = null)
    {
        global $feature_no_cookie, $jitCookie;

        if (isset($_COOKIE[$name])) {
            $cookie = $_COOKIE[$name];
        } elseif (isset($jitCookie[$name])) {
            $cookie = $jitCookie[$name];
        }

        if (isset($cookie)) {
            // we need a reliable way to get cookies even if user has not accepted cookies
            // e.g. CSRF token in a cookie needs to be read as it is already set as a cookie
            return $cookie;
        }

        if ($feature_no_cookie || (empty($section) && ! isset($cookie) && isset($_SESSION['tiki_cookie_jar'][$name]))) {
            if (isset($_SESSION['tiki_cookie_jar'][$name])) {
                return $_SESSION['tiki_cookie_jar'][$name];
            } else {
                return $default;
            }
        } elseif ($section) {
            if (isset($_COOKIE[$section])) {
                if (preg_match("/@" . preg_quote($name, '/') . "\:([^@;]*)/", $_COOKIE[$section], $matches)) {
                    return $matches[1];
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        } else {
            if (isset($cookie)) {
                return $cookie;
            } else {
                return $default;
            }
        }
    }

    /**
     * Revoke all consent by clearing the cookie and user cookie consent pref.
     */
    public static function revokeConsent()
    {
        global $tikilib, $user;
        self::setCookieSection(self::COOKIE_CONSENT_NAME, '', '', time() - 3600, self::$cookiePath, '', self::$cookieSecure);
        if ($user) {
            $tikilib->set_user_preference($user, 'cookie_consent_user_pref', '');
        }
    }

    /**
     * Get the current consent action.
     */
    public static function getConsentAction()
    {
        return self::getConsentPreferences()['action'] ?? null;
    }
}
