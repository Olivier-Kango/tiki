<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('lib/wizard/wizard.php');

/**
 * The Wizard's editor type selector handler
 */
class AdminWizardLogin extends Wizard
{
    public function pageTitle()
    {
        return tra('Set up Login');
    }

    public function isEditable()
    {
        return true;
    }

    public function onSetupPage($homepageUrl)
    {
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_login.tpl';
        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);

        // Configure detail preferences in own page
    }
}
