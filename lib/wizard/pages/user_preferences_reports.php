<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('lib/wizard/wizard.php');

/**
 * Set up the wysiwyg editor, including inline editing
 */
class UserWizardPreferencesReports extends Wizard
{
    public function pageTitle()
    {
        return tra('User Watches:') . ' ' . tra('Report Preferences');
    }

    public function isEditable()
    {
        return true;
    }

    public function isVisible()
    {
        global  $prefs;

        $linkVisible = false;

        // Show if options is selected
        if ($prefs['feature_user_watches'] === 'y' && $prefs['feature_daily_report_watches'] === 'y') {
            $linkVisible = true;
        }
        return $linkVisible;
    }

    public function onSetupPage($homepageUrl)
    {
        global$user, $prefs;

        $smarty = TikiLib::lib('smarty');

        // Run the parent first
        parent::onSetupPage($homepageUrl);

        $showPage = false;

        // Show if options is selected
        if ($prefs['feature_user_watches'] === 'y' && $prefs['feature_daily_report_watches'] === 'y') {
            $showPage = true;
        }

        // Setup initial wizard screen
        $reportsUsers = Reports_Factory::build('Reports_Users');
        $reportsUsersUser = $reportsUsers->get($user);
        $smarty->assign_by_ref('report_preferences', $reportsUsersUser);

        return $showPage;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/user_preferences_reports.tpl';
        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        global $user, $prefs;

        // Run the parent first
        parent::onContinue($homepageUrl);

        // Show if option is selected
        if ($prefs['feature_user_watches'] === 'y' && $prefs['feature_daily_report_watches'] === 'y') {
            $reportsManager = Reports_Factory::build('Reports_Manager');

            $interval = isset($_POST['interval']) ? htmlspecialchars($_POST['interval']) : null;
            $view = isset($_POST['view']) ? htmlspecialchars($_POST['view']) : null;
            $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : null;
            $always_email = filter_input(INPUT_POST, 'always_email', FILTER_SANITIZE_NUMBER_INT);
            if ($always_email != 1) {
                $always_email = 0;
            }

            $reportsManager->save($user, $interval, $view, $type, $always_email);
        }
    }
}
