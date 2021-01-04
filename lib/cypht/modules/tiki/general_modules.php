<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Tiki general modules
 * @package modules
 * @subpackage tiki
 */

if (!defined('DEBUG_MODE')) { die(); }

/**
 * Load Tiki contacts into the Cypht contact store
 * @subpackage tiki/handler
 */
class Hm_Handler_load_tiki_contacts extends Hm_Handler_Module {
    public function process() {
        global $user;
        $contactlib = TikiLib::lib('contact');
        $contacts = $this->get('contact_store');
        $tiki_contacts = $contactlib->list_contacts($user);
        foreach ($tiki_contacts as $contact) {
            $contacts->add_contact(array(
                'source' => 'tiki',
                'email_address' => $contact['email'],
                'display_name' => $contact['firstName'].($contact['lastName'] ? ' '.$contact['lastName'] : '')
            ));
        }
        $this->append('contact_sources', 'tiki');
        $this->out('contact_store', $contacts, false);
    }
}

/**
 * Check for Tiki redirect and instruct Cypht to redirect after compose finished successfully
 * @subpackage tiki/handler
 */
Class Hm_Handler_check_for_tiki_redirect extends Hm_Handler_Module {
    public function process() {
        if ($this->get('msg_sent') && $this->session->get('pageaftersend')) {
            $this->out('redirect_url', $this->session->get('pageaftersend'));
            $this->session->del('pageaftersend');
        }
    }
}

/**
 * Add optional Tiki File attachment to compose page
 * @subpackage tiki/handler
 */
Class Hm_Handler_add_file_attachment extends Hm_Handler_Module {
    public function process() {
        $draft_id = $this->request->get['draft_id'] ?? -1;
        $draft = get_draft($draft_id, $this->session);
        if ($draft && $draft['draft_fattId']) {
            $tikifile = Tiki\FileGallery\File::id($draft['draft_fattId']);
            $file = [
                'name' => $tikifile->name,
                'filename' => $tikifile->filename,
                'type' => $tikifile->filetype,
                'size' => $tikifile->filesize
            ];
            if (!attach_file($tikifile->getContents(), $file, $this->config->get('attachment_dir'), $draft_id, $this)) {
                Hm_Msgs::add('ERRAn error occurred attaching the file gallery file.');
            }
        }
    }
}

/**
 * Output the Tiki Contacts menu item
 * @subpackage tiki/output
 */
class Hm_Output_tiki_contacts_page_link extends Hm_Output_Module {
    protected function output() {
        $res = '<li class="menu_contacts"><a class="unread_link" href="tiki-contacts.php">';
        if (!$this->get('hide_folder_icons')) {
            $res .= '<img class="account_icon" src="'.$this->html_safe(Hm_Image_Sources::$people).'" alt="" width="16" height="16" /> ';
        }
        $res .= $this->trans('Contacts').'</a></li>';
        if ($this->format == 'HTML5') {
            return $res;
        }
        $this->concat('formatted_folder_list', $res);
    }
}

/**
 * Save debug setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_debug_mode extends Hm_Handler_Module {
    public function process() {
        function debug_mode_callback($val) { return $val; }
        process_site_setting('debug_mode', $this, 'debug_mode_callback', false, true);
    }
}

/**
 * Save external image sources setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_allow_external_images extends Hm_Handler_Module {
    public function process() {
        function allow_external_images_callback($val) { return $val; }
        process_site_setting('allow_external_images', $this, 'allow_external_images_callback', false, true);
    }
}

/**
 * Set special variable to prevent saving settings on each config update
 * @subpackage tiki/handler
 */
class Hm_Handler_before_save_user_settings extends Hm_Handler_Module {
    public function process() {
        if (array_key_exists('save_settings', $this->request->post)) {
            $this->user_config->set('skip_saving_on_set', true);
        }
    }
}

/**
 * Remove special variable skipping settings save and save the settings
 * @subpackage tiki/handler
 */
class Hm_Handler_after_save_user_settings extends Hm_Handler_Module {
    public function process() {
        if (array_key_exists('save_settings', $this->request->post)) {
            $this->user_config->del('skip_saving_on_set');
            $this->user_config->save();
        }
    }
}

/**
 * Expose debug setting
 * @subpackage tiki/output
 */
class Hm_Output_debug_mode_setting extends Hm_Output_Module {
    protected function output() {
        $debug_mode = false;
        $settings = $this->get('user_settings', array());
        if (array_key_exists('debug_mode', $settings)) {
            $debug_mode = $settings['debug_mode'];
        }
        return '<tr class="general_setting"><td>'.tr('Debug mode messages to Tiki Log (caution: this may flood the logs if used extensively)').'</td><td><input type="checkbox" name="debug_mode" value="1" '.($debug_mode ? 'checked' : '').'></td></tr>';
    }
}

/**
 * Start the Advanced section on the settings page
 * @subpackage tiki/output
 */
class Hm_Output_start_advanced_settings extends Hm_Output_Module {
    /**
     * Settings in this section control the advanced integration settings betwene Cypht and Tiki (hm3.ini ones)
     */
    protected function output() {
        return '<tr><td data-target=".advanced_setting" colspan="2" class="settings_subtitle">'.
            '<img alt="" src="'.Hm_Image_Sources::$code.'" width="16" height="16" />'.
            $this->trans('Advanced').'</td></tr>';
    }
}

/**
 * Expose image sources setting
 * @subpackage tiki/output
 */
class Hm_Output_allow_external_images_setting extends Hm_Output_Module {
    protected function output() {
        $allow_external_images = false;
        $settings = $this->get('user_settings', array());
        if (array_key_exists('allow_external_images', $settings)) {
            $allow_external_images = $settings['allow_external_images'];
        }
        return '<tr class="general_setting"><td>'.tr('Allow remote image sources').'</td><td><input type="checkbox" name="allow_external_images" value="1" '.($allow_external_images ? 'checked' : '').'></td></tr>';
    }
}