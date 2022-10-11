<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Override user config handling in Cypht.
 * Store settings in Tiki user preferences and load them from there.
 * Ignore encryption and decryption of the settings due to missing password key when loading.
 */
class Tiki_Hm_User_Config extends Hm_Config
{
    /* username */
    private $username;
    private $site_config;

    /**
     * Load site configuration
     * @param object $config site config
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config->user_defaults);
        $this->site_config = $config;
    }

    /**
     * Load the settings for a user
     * @param string $username username
     * @param string $key key to decrypt the user data (not used)
     * @return void
     */
    public function load($username, $key = null)
    {
        $this->username = $username;
        $session_prefix = $this->site_config->get('session_prefix');
        if ($this->site_config->settings_per_page) {
            $data = $_SESSION[$session_prefix]['plugin_data'] ?? TikiLib::lib('tiki')->get_user_preference('%', $_SESSION[$session_prefix]['preference_name']);
        } else {
            $data = TikiLib::lib('tiki')->get_user_preference($username, $_SESSION[$session_prefix]['preference_name']);
        }
        if ($data) {
            $data = $this->decode($data);
            $this->config = array_merge($this->config, $data);
            $this->set_tz();
        }
        // merge imap/smtp servers config with session as plugin cypht might be overriding these
        foreach (['imap_servers', 'smtp_servers'] as $key) {
            if (! empty($_SESSION[$session_prefix]['user_data'][$key])) {
                if (empty($this->config[$key])) {
                    $this->config[$key] = [];
                }
                foreach ($_SESSION[$session_prefix]['user_data'][$key] as $server) {
                    $found = false;
                    foreach ($this->config[$key] as $cserver) {
                        if ($server['server'] == $cserver['server'] && $server['tls'] == $cserver['tls'] && $server['port'] == $cserver['port'] && $server['user'] == $cserver['user']) {
                            $found = true;
                            break;
                        }
                    }
                    if (! $found) {
                        do {
                            $id = uniqid();
                        } while (isset($this->config[$key][$id]));
                        $this->config[$key][$id] = $server;
                    }
                }
            }
        }
    }

    /**
     * Reload from outside input - done upon load_user_data handler executed in Cypht.
     * This loads user confirm from session but also saves to persistent storage
     * as Tiki-Cypht does not warn user about unsaved settings when logging out...
     * @param array $data new user data
     * @param string $username
     * @return void
     */
    public function reload($data, $username = false)
    {
        $this->username = $username;
        $this->config = $data;
        $this->set_tz();
        if ($username) {
            $temp_config = new Tiki_Hm_User_Config($this->site_config);
            $temp_config->load($username);
            $existing = $temp_config->dump();
            ksort($existing);
            ksort($data);
            if (json_encode($existing) != json_encode($data)) {
                $this->save($username);
            }
        }
    }

    /**
     * Save user settings into Tiki
     * @param string $username username
     * @param string $key encryption key (not used)
     * @return void
     */
    public function save($username = null, $key = null)
    {
        if ($this->get('skip_saving_on_set', false)) {
            return;
        }
        if (empty($username)) {
            $username = $this->username;
        }
        $this->shuffle();
        $removed = $this->filter_servers();
        ksort($this->config);
        $data = json_encode($this->config);
        if ($this->site_config->settings_per_page) {
            $original_plugin_data = $_SESSION[$this->site_config->get('session_prefix')]['plugin_data'] ?? '';
            if ($original_plugin_data != $data) {
                $util = new Services_Edit_Utilities();
                $util->replacePlugin(new JitFilter([
                    'page' => $this->site_config->settings_per_page,
                    'message' => "Auto-saving Cypht settings.",
                    'type' => 'cypht',
                    'content' => $data,
                    'index' => 1
                ]), false);
            }
        } else {
            TikiLib::lib('tiki')->set_user_preference($username, $_SESSION[$this->site_config->get('session_prefix')]['preference_name'], $data);
        }
        $this->restore_servers($removed);
        $_SESSION[$this->site_config->get('session_prefix')]['user_data'] = $this->dump();
    }

    /**
     * Set a config value
     * @param string $name config value name
     * @param string $value config value
     * @return void
     */
    public function set($name, $value)
    {
        $this->config[$name] = $value;
        $this->save($this->username);
    }

    /**
     * Clear state variables in server list like 'object' and 'connected'.
     * Pass the rest of the cleanup to parent.
     */
    public function filter_servers()
    {
        foreach ($this->config as $key => $vals) {
            if (in_array($key, ['pop3_servers', 'imap_servers', 'smtp_servers'])) {
                foreach ($vals as $index => $server) {
                    $this->config[$key][$index]['object'] = false;
                    $this->config[$key][$index]['connected'] = false;
                }
            }
        }
        return parent::filter_servers();
    }
}
