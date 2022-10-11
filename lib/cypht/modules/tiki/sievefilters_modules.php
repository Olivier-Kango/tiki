<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Tiki groupmail modules
 * @package modules
 * @subpackage tiki
 */

if (! defined('DEBUG_MODE')) {
    die();
}

/**
 * Cron job setup button
 * @subpackage tiki/output
 */
class Hm_Output_tiki_filters_cron extends Hm_Output_Module
{
    protected function output()
    {
        $factory = get_sieve_client_factory($this->get('site_config'));
        $has_tiki_storage = array_filter($this->get('user_config')->get('imap_servers'), function ($mailbox) use ($factory) {
            $client = $factory->init($this->get('user_config'), $mailbox);
            if (method_exists($client, 'isTikiStorage') && $client->isTikiStorage()) {
                return true;
            }
            return false;
        });
        if (! $has_tiki_storage) {
            return '';
        }

        $scheduled_jobs = TikiLib::lib('scheduler')->get_scheduler(null, null, ['run_only_once' => 0, 'params' => new TikiDb_Expr('$$ LIKE "%sieve:filters%"', [])]);
        if ($scheduled_jobs) {
            return '';
        }

        return '<div class="settings_subtitle">' . tr('Some of the filters defined here require scheduled job to process. Define a SieveFiltersTask here:')
            . ' <a href="tiki-admin_schedulers.php">' . tr('Scheduler') . '</a>'
            . '</div>';
    }
}

/**
 * Placeholder handler module used by sieve filters command
 * @subpackage tiki/output
 */
class Hm_Handler_tiki_sieve_placeholder extends Hm_Handler_Module
{
    public function process()
    {
        // noop
    }
}
