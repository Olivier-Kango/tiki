<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_last_validated_faq_questions_info()
{
    return [
        'name' => tra('Newest Validated FAQ Questions'),
        'description' => tra('Displays the specified number of validated questions FAQs from newest to oldest.'),
        'prefs' => ["feature_faqs"],
        'params' => [
            'faqId' => [
                'name' => tra('FAQ identifier'),
                'description' => tra('If set to a FAQ identifier, restricts the chosen questions to those in the identified FAQ.') . " " . tra('Example value: 13.') . " " . tra('Not set by default.'),
                'profile_reference' => 'faq',
                'filter' => 'int',
            ],
            'truncate' => [
                'name' => tra('Number of characters to display'),
                'description' => tra('Number of characters to display'),
                'filter' => 'int',
            ],
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_validated_faq_questions($mod_reference, $module_params)
{
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $faqlib = TikiLib::lib('faq');
    $def = ['faqId' => 0, 'truncate' => 20];
    $module_params = array_merge($def, $module_params);
    $ranking = $faqlib->list_faq_questions($module_params['faqId'], 0, $mod_reference['rows'], 'created_desc', '');
    $smarty->assign_by_ref('modLastValidatedFaqQuestions', $ranking['data']);
    $smarty->assign_by_ref('trunc', $module_params['truncate']);
}
