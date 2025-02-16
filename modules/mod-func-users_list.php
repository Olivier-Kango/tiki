<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_users_list_info()
{
    return [
        'name' => tra('Users List'),
        'description' => tra('Display a list of users with optional extra information from each.'),
        'prefs' => ['feature_wiki'],
        'params' => [
            'login' => [
                'name' => tra('Login'),
                'description' => tra('Show the user name.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'y'
            ],
            'realName' => [
                'name' => tra('Real Name'),
                'description' => tra('Show the user real name.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'lastLogin' => [
                'name' => tra('Last Login'),
                'description' => tra('Show the last login date.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'groups' => [
                'name' => tra('Groups'),
                'description' => tra('Show the direct and included groups a user belongs to.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'avatar' => [
                'name' => tra('Profile picture'),
                'description' => tra('Show the user profile picture.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'userPage' => [
                'name' => tra('User Page'),
                'description' => tra('Show a link to the userPage.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'log' => [
                'name' => tra('Log'),
                'description' => tra('Show a link to the user logs(feature actionlog must be activated).') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'group' => [
                'name' => tra('Group'),
                'description' => tra('Show only the users of the group.') . ' ' . tra('Possible values:') . ' ' . tra('Groupname'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'includedGroups' => [
                'name' => tra('Included Groups'),
                'description' => tra('Show only the users of the group group and of a group including group.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'initial' => [
                'name' => tra('initial'),
                'description' => tra('Show only the users whose name begins with the letter.') . ' ' . tra('Possible values:') . ' ' . tra('a letter'),
                'filter' => 'word',
                'required' => false,
                'default' => 'n'
            ],
            'heading' => [
                'name' => tra('heading'),
                'description' => tra('Show the table heading.') . ' ' . tra('Possible values:') . ' ' . tra('y|n'),
                'filter' => 'word',
                'required' => false,
                'default' => 'y'
            ],
            'sort_mode' => [
                'name' => tra('Sort Mode'),
                'description' => tra('Sort users in ascending or descending order using these values: ') .
                    'login_asc, login_desc, email_asc, email_desc.',
                'filter' => 'word',
                'required' => false,
                'default' => 'login_asc'
            ],
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $module_params
 */
function module_users_list($mod_reference, $module_params)
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    if (isset($module_params['group'])) {
        $group = [$module_params['group']];
        if (isset($module_params['includedGroups']) && $module_params['includedGroups'] == 'y') {
            $group = array_merge($group, $userlib->get_including_groups($group[0]));
        }
    } else {
        $group = '';
    }

    if (! isset($module_params['sort_mode'])) {
        $sort_mode = 'login_asc';
    } else {
        $sort_mode = $module_params['sort_mode'];
    }

    $users = $userlib->get_users(0, $mod_reference['rows'], $sort_mode, '', ! empty($module_params['initial']) ? $module_params['initial'] : '', isset($module_params['groups']) ? true : false, $group);
    if (isset($_REQUEST["realName"]) && ($prefs['auth_ldap_nameattr'] == '' || $prefs['auth_method'] != 'ldap')) {
        $tikilib->set_user_preference($userwatch, 'realName', $_REQUEST["realName"]);
        if ($prefs['user_show_realnames'] == 'y') {
            $cachelib = TikiLib::lib('cache');
            $cachelib->invalidate('userlink.' . $user . '0');
        }
    }

    for ($i = 0; $i < $users['cant']; ++$i) {
        $my_user = $users['data'][$i]['user'];
        if (isset($module_params['realName']) && $module_params['realName'] == 'y') {
            $users['data'][$i]['realName'] = $tikilib->get_user_preference($my_user, 'realName', '');
        }
        if (isset($module_params['avatar']) && $module_params['avatar'] == 'y') {
            $users['data'][$i]['avatar'] = $tikilib->get_user_avatar($my_user);
        }
        if (
            (isset($module_params['realName']) && $module_params['realName'] == 'y')
            || (isset($module_params['login']) && $module_params['login'] == 'y')
        ) {
            $users['data'][$i]['info_public'] = $tikilib->get_user_preference($my_user, 'user_information', 'public') != 'private' ? 'y' : 'n';
        }
        if (isset($module_params['userPage']) && $module_params['userPage'] == 'y') {
            global $feature_wiki_userpage;
            if ($prefs['feature_wiki_userpage'] == 'y' or $feature_wiki_userpage == 'y') {
                if (! isset($prefs['feature_wiki_userpage_prefix'])) {//trick compat 1.9, 1.10
                    global $feature_wiki_userpage_prefix;
                    $pre = $feature_wiki_userpage_prefix;
                } else {
                    $pre = $prefs['feature_wiki_userpage_prefix'];
                }
                if ($tikilib->page_exists($pre . $my_user)) {
                    $users['data'][$i]['userPage'] = $pre . $my_user;
                }
            }
        }
    }
    if (isset($module_params['log']) && $module_params['log'] == 'y' && $prefs['feature_actionlog'] != 'y') {
        $module_params['log'] = 'n';
    }
    $smarty->assign_by_ref('users', $users['data']);
    $smarty->assign_by_ref('module_params_users_list', $module_params);
}
