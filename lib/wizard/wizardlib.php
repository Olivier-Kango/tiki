<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

class WizardLib extends TikiLib
{
    public $wizard_stepNr;

    /**
     * onLogin is the collection point for all login events, which may run a wizard.
     * All triggers at login time, must call this function.
     *
     * The function will not return, if the wizard is run.
     * Instead it will redirect to the specified homePageUrl
     *
     * Use the start functions, e.g. startAdminWizard, to start the wizard manually, not this function
     *
     * @param mixed $user The logged-in user
     * @param string $homePageUrl The return URL
     * @param bool $force Force the opening of the admin wizard
     * @return none
     *
     */
    public function onLogin($user, $homePageUrl, $force = false)
    {
        global $base_url;
        $userlib = TikiLib::lib('user');

        // Check the user status
        $isAdmin = $userlib->user_has_permission($user, 'tiki_p_admin');

        // Check if a Login Wizard should be displayed
        $activeLoginWizard = $this->get_preference('wizard_admin_hide_on_login') !== 'y';
        if ($force || ($isAdmin && $activeLoginWizard)) {
            // User is an admin. Show Setup Wizards
            $this->startAdminWizard($homePageUrl, 0);
        } else {
            // Do not activate the user wizard (yet)
            // $show = false;
            // if ($show) {
            //  $this->startUserWizard($homePageUrl,0);
            // }
        }
    }

    /**
     *  startAdminWizard - Manually start the admin wizard
     *  Can also be started by going to: tiki-wizard_admin.php?url=tiki-admin.php?page=general#content2
     *  The url value, is where the wizard should return, when it's done
     *
     *  @param  $homePageUrl    The url to return to, when the wizard is done
     *  @param  $stepNr         Which step in the wizard to go to. Default = 0
     *  @return     This function doesn't return
     */
    public function startAdminWizard($homePageUrl, $stepNr = 0)
    {
        global $base_url;

        // Start the admin wizard
        $url = $base_url . 'tiki-wizard_admin.php?&stepNr=' . $stepNr . '&url=' . rawurlencode($homePageUrl);
        $accesslib = TikiLib::lib('access');
        $accesslib->redirect($url);
    }

    /**
     *  startUserWizard - Manually start the user wizard
     *  Can also be started by going to: tiki-wizard_user.php?url=tiki-index.php?page=Hello
     *  The url value, is where the wizard should return, when it's done
     *
     *  @param  $homePageUrl    The url to return to, when the wizard is done
     *  @param  $stepNr         Which step in the wizard to go to. Default = 0
     *  @return     This function doesn't return
     */
    public function startUserWizard($homePageUrl, $stepNr = 0)
    {
        global $base_url;

        // Start the admin wizard
        $url = $base_url . 'tiki-wizard_user.php?&stepNr=' . $stepNr . '&url=' . rawurlencode($homePageUrl);
        $accesslib = TikiLib::lib('access');
        $accesslib->redirect($url);
    }


    /**
    *   Wizard's page stepping logic
    *   Every page added must be a subclass of the Wizard class
    *   If the admin wizard is run the "showOnLogin" is processed. Otherwize not
    *   @param  array   $pages  Array of Wizard pages
    *   @param  bool    $adminWizard Flag if the wizard is a login/admin wizard. Default = false
    */
    public function showPages($pages, $adminWizard = false)
    {
        global $base_url;
        $smarty = TikiLib::lib('smarty');
        try {
            if (! isset($_REQUEST['url'])) {
                // User the base url as the return URL
                $_REQUEST['url'] = $base_url;
                // throw new Exception(tra("No return URL specified"));
            }
            if (empty($pages)) {
                // Nothing to do
                return;
                // throw new Exception(tra("No wizard pages specified"));
            }

            // Assign the return URL
            $homepageUrl = $_REQUEST['url'];
            $smarty->assign('homepageUrl', $homepageUrl);

            // User pressed "Close".
            //  Save the "Show on login" setting, and no other preferences
            if (isset($_POST['close'])) {
                if ($adminWizard) {
                    // Save "Show on login" setting
                    $showOnLogin = ( isset($_POST['showOnLogin']) && $_POST['showOnLogin'] == 'on' ) ? 'y' : 'n';
                    $this->showOnLogin($showOnLogin);
                }

                //  Then exit, by returning the specified URL
                $accesslib = TikiLib::lib('access');
                $accesslib->redirect($homepageUrl);
            }

            $isFirstStep = ! isset($_POST['wizard_step']);
            $isUserStep = isset($_GET['stepNr']);   // User defined step nr
            if ($isUserStep) {
                $stepNr = (int)$_GET['stepNr'];
            } else {
                $stepNr = isset($_POST['wizard_step']) ? (int)$_POST['wizard_step'] : 0;
            }

            $stepBack = false;
            if (isset($_POST['back'])) {
                // Discard changes on page
                //  Go to previous page
                $stepNr -= 1;
                $stepBack = true;
            }

            // Validate the specified stepNr
            if (($stepNr < 0) || ($stepNr >= count($pages))) {
                throw new Exception(tra("Invalid wizard stepNr specified"));
            }

            if (! $stepBack && ! $isFirstStep && (! $isUserStep && $stepNr >= 0)) {
                // Commit the step just completed
                $pages[$stepNr]->onContinue($homepageUrl);

                // Loop until the next displayed wizard page
                //  Return when all pages have been processed.
                do {
                    $next = true;
                    if (count($pages) > $stepNr + 1) {
                        $stepNr += 1;
                        if (count($pages) == $stepNr + 1) {
                            $smarty->assign('lastWizardPage', 'y');
                        } else {
                            $isEditable = $pages[$stepNr]->isEditable();
                            $smarty->assign('isEditable', $isEditable);
                        }

                        // If onSetupPage returns true, processing should continue
                        $show = $pages[$stepNr]->onSetupPage($homepageUrl);

                        // Do not show page, if it doesn't return a boolean
                        if ($show === true) {
                            $template = $pages[$stepNr]->getTemplate();
                            $smarty->assign('wizardBody', $smarty->fetch($template));
                            $next = false;
                            break;
                        }
                    } else {
                        // Return to homepage, when we get to the end
                        $accesslib = TikiLib::lib('access');
                        $accesslib->redirect($homepageUrl);
                    }
                } while ($next);
            } else {
                // For directly accessed wizard pages scroll back, when not displayed
                do {
                    $isEditable = $pages[$stepNr]->isEditable();
                    $smarty->assign('isEditable', $isEditable);

                    $next = true;
                    $show = $pages[$stepNr]->onSetupPage($homepageUrl);

                    // Do not show page, if it doesn't return a boolean
                    if ($show === true) {
                        $template = $pages[$stepNr]->getTemplate();
                        $showChangesWizard = $this->showChangesWizard();
                        $smarty->assign('showChangesWizard', $showChangesWizard);
                        $smarty->assign('wizardBody', $smarty->fetch($template));
                        $next = false;
                    }

                    if ($stepNr == 0) {
                        $smarty->assign('firstWizardPage', 'y');
                        $next = false;
                    } elseif ($show === false) {
                        // Step back
                        $stepNr -= 1;
                    }
                } while ($next);
            }

            $smarty->assign('wizard_step', $stepNr);
            $this->wizard_stepNr = $stepNr;

            // Set the page title
            $smarty->assign('pageTitle', $pages[$stepNr]->pageTitle());
        } catch (Exception $e) {
            $error = $e->getMessage();
            $smarty->assign('msg', $error);
            $smarty->display("error.tpl");
            die;
        }
    }

    public function showOnLogin($showOnLogin)
    {
        $hide = $showOnLogin === 'y' ? 'n' : 'y';
        $this->set_preference('wizard_admin_hide_on_login', $hide);
    }

    /**
     * Check if tiki get an upgrade to show changes wizard
     *
     * @return bool
     */
    private function showChangesWizard()
    {
        $tikilib = TikiLib::lib('tiki');
        $TWV = new TWVersion();
        $tikiCurrentVersion = (float) $TWV->getBaseVersion();
        $tikiInstallVersion = $tikilib->get_preference('tiki_install_version');
        $installCommitDate = ! empty($tikiInstallVersion)
            ? trim(shell_exec('git show -s --format=%cd --date=unix ' . $tikiInstallVersion . ' --date=unix 2>/dev/null'))
            : null;
        $showChangesWizard = false;

        if ($TWV->svn === 'y') {
            $repoUrl = trim(shell_exec('svn info --show-item repos-root-url'));
            $repoRevision = trim(shell_exec('svn info --show-item revision'));
            if (! empty($tikiInstallVersion) && ! empty($repoUrl)) {
                $installRevision = shell_exec('svn log -r ' . $tikiInstallVersion . ' ' . $repoUrl);
            }
            if (empty($tikiInstallVersion) || (isset($installRevision) && $repoRevision > $tikiInstallVersion)) {
                $showChangesWizard = true;
                $tikilib->set_preference('tiki_install_version', $repoRevision);
            }
        }

        if ($TWV->git === 'y') {
            $output = shell_exec('git log -n 1 --format=%H-%cd --date=unix 2>/dev/null');
            $commit = $output ? explode('-', $output) : [];
            $commitId = ! empty($commit[0]) ? trim($commit[0]) : 0;
            $commitDate = ! empty($commit[1]) ? trim($commit[1]) : 0;
            if (! isset($installCommitDate) || $commitDate > $installCommitDate) {
                $showChangesWizard = true;
                $tikilib->set_preference('tiki_install_version', $commitId);
            }
        }

        if ($TWV->svn !== 'y' && $TWV->git !== 'y' && (empty($tikiInstallVersion) || ! empty($installCommitDate) || $tikiCurrentVersion > (float) $tikiInstallVersion)) {
            $showChangesWizard = true;
            $tikilib->set_preference('tiki_install_version', $tikiCurrentVersion);
        }

        return $showChangesWizard;
    }
}
