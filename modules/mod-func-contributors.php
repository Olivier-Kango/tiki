<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_contributors_info()
{
    return [
        'name' => tra('Contributors'),
        'description' => tra('Lists the contributors to the wiki page being viewed and some information on them.'),
        'prefs' => ['feature_wiki'],
        'params' => []
    ];
}

// Hides contributors past the fifth until a link is clicked
/**
 * @param $mod_reference
 * @param $module_params
 */
function module_contributors($mod_reference, $module_params)
{
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $headerlib = TikiLib::lib('header');
    $wikilib = TikiLib::lib('wiki');
    $currentObject = current_object();
    if ($currentObject && $currentObject['type'] == 'wiki page') {
        $objectperms = Perms::get(['type' => 'wiki page', 'object' => $currentObject['object']]);
        if ($objectperms->view) {
            $contributors = $wikilib->get_contributors($currentObject['object']);
            $contributors_details = [];
            $headerlib->add_css('div.contributors div br {clear: both;}'); // Avoid avatar conflicts with lines below
            foreach ($contributors as $contributor) {
                $details = ['login' => $contributor];
                $details['realName'] = $userlib->get_user_preference($contributor, 'realName');
                $country = $tikilib->get_user_preference($contributor, 'country');
                if (! is_null($country) && $country != 'Other') {
                    $details['country'] = $country;
                }
                $email_isPublic = $tikilib->get_user_preference($contributor, 'email is public');
                if ($email_isPublic != 'n') {
                    $details['email'] = $userlib->get_user_email($contributor);
                    $details['scrambledEmail'] = TikiLib::scrambleEmail($details['email']);
                }
                $details['homePage'] = $tikilib->get_user_preference($contributor, 'homePage');
                $details['avatar'] = $tikilib->get_user_avatar($contributor);
                $contributors_details[] = $details;
            }
            $smarty->assign_by_ref('contributors_details', $contributors_details);
            $hiddenContributors = count($contributors_details) - 5;
            if ($hiddenContributors > 0) {
                $smarty->assign('hiddenContributors', $hiddenContributors);
            }
        }
    }
}
