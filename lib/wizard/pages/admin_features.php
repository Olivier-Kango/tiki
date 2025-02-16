<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('lib/wizard/wizard.php');

/**
 * The Wizard's namespace handler
 */
class AdminWizardFeatures extends Wizard
{
    public function pageTitle()
    {
        return tra('Set up Main features');
    }
    public function isEditable()
    {
        return true;
    }

    public function onSetupPage($homepageUrl)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        $isMultiLanguage = $prefs['feature_multilingual'] === 'y';
        if ($isMultiLanguage) {
            $smarty->assign('isMultiLanguage', $isMultiLanguage);
        }

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_features.tpl';
        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
