<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'       => [
        'objectId'               => 'word',        //get
        'objectType'             => 'word',       //post
        'objectName'             => 'word',       //post
        'referer'                => 'word',       //post
        'parentId'               => 'int',        //post
        'permType'               => 'word',       //post
        'assignstructure'        => 'bool',       //post
        'propagate_category'     => 'bool',       //post
        'feature_select'         => 'bool',       //post
        'group_select'           => 'bool',       //post
        'group'                  => 'grouname',   //post
        'assign'                 => 'bool',       //post
        'quick_perms'            => 'bool',       //post
        'remove'                 => 'bool',       //post
        'copy'                   => 'bool',       //post
        'paste'                  => 'bool',       //post
        'used_groups'            => 'bool',       //post
        'show_disabled_features' => 'bool',       //post
        'filegals_manager'       => 'word',       //post
        ],
        'staticKeyFiltersForArrays' => [
        'feature_filter'            => 'bool',       //post
        'group_filter'              => 'bool',       //post
        'perm'                      => 'word',       //post
        'old_perm'                  => 'word',       //post
        ],
    ],
];

include_once('tiki-setup.php');
if (! empty($_REQUEST['objectType']) && $_REQUEST['objectType'] != 'global') {
    if (! isset($_REQUEST['objectName']) || empty($_REQUEST['objectId'])) {
        $smarty->assign('msg', tra('Not enough information to display this page'));
        $smarty->display('error.tpl');
        die;
    }
}

if (empty($_REQUEST['objectType'])) {
     $_REQUEST['objectType'] = 'global';
     $_REQUEST['objectName'] = '';
     $_REQUEST['objectId'] = '';
}

$auto_query_args = [
    'referer',
    'reloff',
    'objectName',
    'objectType',
    'permType',
    'objectId',
    'filegals_manager',
    'insertion_syntax',
    //'show_disabled_features', // this seems to cause issues - the $_GET version overrides the $_POST one...
];

$perm = 'tiki_p_assign_perm_' . preg_replace('/[ +]/', '_', $_REQUEST['objectType']);
if ($_REQUEST['objectType'] == 'wiki page') {
    if ($tiki_p_admin_wiki == 'y') {
        $special_perm = 'y';
    } else {
        $info = $tikilib->get_page_info($_REQUEST['objectName']);
        $tikilib->get_perm_object($_REQUEST['objectId'], $_REQUEST['objectType'], $info);
    }
} elseif ($_REQUEST['objectType'] == 'global') {
    $access->check_permission('tiki_p_admin');
} else {
    $tikilib->get_perm_object($_REQUEST['objectId'], $_REQUEST['objectType']);
    if ($_REQUEST['objectType'] == 'tracker') {
        $definition = Tracker_Definition::get($_REQUEST['objectId']);
        if ($groupCreatorFieldId = $definition->getWriterGroupField()) {
            $smarty->assign('group_tracker', 'y');
        }
    }
}

if (! ($tiki_p_admin_objects == 'y' || (isset($$perm) && $$perm == 'y') || (isset($special_perm) && $special_perm == 'y'))) {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra('You do not have permission to assign permissions for this object'));
    $smarty->display('error.tpl');
    die;
}

if (! isset($_REQUEST['referer'])) {
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tiki-objectpermissions.php') === false) {
        $_REQUEST['referer'] = $_SERVER['HTTP_REFERER'];
    } else {
        unset($_REQUEST['referer']);
    }
}

if (isset($_REQUEST['referer'])) {
    $smarty->assign('referer', $_REQUEST['referer']);
} else {
    $smarty->assign('referer', '');
}

$_REQUEST['objectId'] = urldecode($_REQUEST['objectId']);
$_REQUEST['objectType'] = urldecode($_REQUEST['objectType']);
$_REQUEST['parentId'] = ! empty($_REQUEST['parentId']) ? urldecode($_REQUEST['parentId']) : null;
$_REQUEST['permType'] = ! empty($_REQUEST['permType']) ? urldecode($_REQUEST['permType']) : 'global';
$smarty->assign('objectName', $_REQUEST['objectName']);
$smarty->assign('objectId', $_REQUEST['objectId']);
$smarty->assign('objectType', $_REQUEST['objectType']);
$smarty->assign_by_ref('permType', $_REQUEST['permType']);

if ($_REQUEST['objectType'] == 'wiki') {
    $_REQUEST['objectType'] = 'wiki page';
}

$objectFactory = Perms_Reflection_Factory::getDefaultFactory();
$currentObject = $objectFactory->get($_REQUEST['objectType'], $_REQUEST['objectId'], $_REQUEST['parentId']);

$permissionApplier = new Perms_Applier();
$permissionApplier->addObject($currentObject);

if ($restrictions = perms_get_restrictions()) {
    $permissionApplier->restrictPermissions($restrictions);
}

if ($_REQUEST['objectType'] == 'wiki page') {
    $structlib = TikiLib::lib('struct');
    $pageInfoTree = $structlib->s_get_structure_pages($structlib->get_struct_ref_id($_REQUEST['objectId']));
    if (count($pageInfoTree) > 1) {
        $smarty->assign('inStructure', 'y');
    }

    // If assign to structure is requested, add subelements to the applier
    if (! empty($_REQUEST['assignstructure']) && $_REQUEST['assignstructure'] == 'on' && ! empty($pageInfoTree)) {
        foreach ($pageInfoTree as $subPage) {
            $sub = $objectFactory->get($_REQUEST['objectType'], $subPage['pageName']);
            $permissionApplier->addObject($sub);
        }
    }
    $cachelib = TikiLib::lib('cache');
    $cachelib->invalidateAll('menu_');
    $cachelib->invalidateAll('structure_');
}

if ($_REQUEST['objectType'] == 'category') {
    $categlib = TikiLib::lib('categ');
    $categ = $categlib->get_category($_REQUEST['objectId']);
    $groupRole = false;
    if (isset($_REQUEST['propagate_category'])) {
        $descendants = $categlib->get_category_descendants($_REQUEST['objectId']);
        foreach ($descendants as $child) {
            $o = $objectFactory->get($_REQUEST['objectType'], $child, $_REQUEST['objectId']);
            $permissionApplier->addObject($o);
        }
    }
    $templatedGroupId = TikiLib::lib('attribute')->get_attribute("category", $_REQUEST['objectId'], "tiki.category.templatedgroupid");
    if (isset($categ["parentId"]) && $categ["parentId"] > 0 && $templatedGroupId) {
        $roles = TikiLib::lib("roles")->getAvailableCategoriesRolesIds($categ["parentId"]);
        $groupRole = ! empty($roles);
    }
    $smarty->assign('groupRole', $groupRole);
}

// apply feature filter change
if (isset($_REQUEST['feature_select'])) {
    if (! isset($_REQUEST['feature_filter'])) {
        $_REQUEST['feature_filter'] = [];
    }
    $tikilib->set_user_preference($user, 'objectperm_admin_features', serialize($_REQUEST['feature_filter']));
    $cookietab = '1';
}

$feature_filter = unserialize($tikilib->get_user_preference($user, 'objectperm_admin_features') ?? "");

// apply group filter change
if (isset($_REQUEST['group_select'])) {
    if (! isset($_REQUEST['group_filter'])) {
        $_REQUEST['group_filter'] = [];
    }
    $tikilib->set_user_preference($user, 'objectperm_admin_groups', serialize($_REQUEST['group_filter']));
    $cookietab = '1';
}

$group_filter = unserialize($tikilib->get_user_preference($user, 'objectperm_admin_groups') ?? "");

// Get a list of groups
$groups = $userlib->get_groups_for_permissions();
$smarty->assign_by_ref('groups', $groups['data']);

$OBJECTPERM_ADMIN_MAX_GROUPS = 4;

if ($group_filter === false) {
    $c = 0;
    $group_filter = [];
    foreach ($groups['data'] as $g) {   //  filter out if too many groups and hide Admins by default
        if ($c < $OBJECTPERM_ADMIN_MAX_GROUPS && $g['groupName'] != 'Admins') {
            $group_filter[] = $g['id'];
            $c++;
        }
    }
    if (count($groups['data']) > $OBJECTPERM_ADMIN_MAX_GROUPS) {
        $cookietab = '2';
        $smarty->assign('groupsFiltered', 'y');
    }
    $tikilib->set_user_preference($user, 'objectperm_admin_groups', serialize($group_filter));
}

if (isset($_REQUEST['group'])) {
    $grp_id = 0;
    foreach ($groups['data'] as $grp) {
        if ($grp['groupName'] == $_REQUEST['group']) {
            $grp_id = $grp['id'];
            break;
        }
    }
    if ($grp_id > 0 && ! in_array($grp_id, $group_filter)) {
        $group_filter[] = $grp_id;
    }
}

// Process the form to assign a new permission to this object
if (isset($_REQUEST['assign']) && ! isset($_REQUEST['quick_perms']) && $access->checkCsrf(true)) {
    if (isset($_REQUEST['perm']) && ! empty($_REQUEST['perm'])) {
        foreach ($_REQUEST['perm'] as $group => $gperms) {
            foreach ($gperms as $perm) {
                if ($tiki_p_admin_objects != 'y' && ! $userlib->user_has_perm_on_object($user, $_REQUEST['objectId'], $_REQUEST['objectType'], $perm)) {
                    Feedback::errorPage(['mes' => tr('Permission denied'), 'errortype' => 401]);
                }
            }
        }
    }
    $newPermissions = get_assign_permissions();
    $permissionApplier->apply($newPermissions);
    if (isset($_REQUEST['group'])) {
        $smarty->assign('groupName', $_REQUEST['group']);
    }

    //identify permissions changed for feedback message
    $newPerms = $_REQUEST['perm'] ?? [];
    $oldPerms = $_REQUEST['old_perm'] ?? [];
    $groupNames = array_unique(array_merge(array_keys($newPerms), array_keys($oldPerms)));
    $changed = [];
    foreach ($groupNames as $groupName) {
        $newPerms[$groupName] = ! isset($newPerms[$groupName]) ? [] : $newPerms[$groupName];
        $oldPerms[$groupName] = ! isset($oldPerms[$groupName]) ? [] : $oldPerms[$groupName];
        $changed['added'][$groupName] = array_diff($newPerms[$groupName], $oldPerms[$groupName]);
        $changed['deleted'][$groupName] = array_diff($oldPerms[$groupName], $newPerms[$groupName]);
    }

    $groupInheritance = [];
    foreach ($groups['data'] as $row) {
        if ($group_filter !== false && in_array($row['id'], $group_filter)) {
            $groupList[] = $row['groupName'];
            $groupInheritance[] = $userlib->get_included_groups($row['groupName']);
        }
    }

    $changed['added'] = ! empty($changed['added']) ? $changed['added'] : [];
    foreach ($changed['added'] as $groupName => $addPerms) { // group messages about permissions added by parent group
        if (count($addPerms) == 0) {
            continue;
        }

        $isParentGroup = false;
        foreach ($groupInheritance as $index => $gi) {
            if (is_array($gi) && in_array($groupName, $gi)) {
                $delPerms = $changed['deleted'][$groupList[$index]] ?? [];
                $changed['deleted'][$groupList[$index]] = array_diff($delPerms, $addPerms);
                $isParentGroup = true;
            }
        }

        if ($isParentGroup) {
            $changed['added'][tr('%0 and all the children groups', $groupName)] = $changed['added'][$groupName];
            unset($changed['added'][$groupName]);
        }
    }

    $changed['deleted'] = ! empty($changed['deleted']) ? $changed['deleted'] : [];
    foreach ($changed['deleted'] as $groupName => $delPerms) {  // group messages about permissions removed by parent group
        if (count($delPerms) == 0) {
            continue;
        }

        $isParentGroup = false;
        foreach ($groupInheritance as $index => $gi) {
            if (is_array($gi) && in_array($groupName, $gi)) {
                $isParentGroup = true;
                break;
            }
        }

        if ($isParentGroup) {
            $changed['deleted'][tr('%0 and all the children groups', $groupName)] = $changed['deleted'][$groupName];
            unset($changed['deleted'][$groupName]);
        }
    }

    if (isset($changed['deleted']['Admins']) && in_array('tiki_p_admin', $changed['deleted']['Admins'])) {
        unset($changed['deleted']['Admins'][array_search('tiki_p_admin', $changed['deleted']['Admins'])]);
    }

    //clean up array of changed permissions and indicate section for feedback
    $permInfo = $userlib->get_enabled_permissions();
    $changeCount = 0;
    foreach ($changed as $directionName => $directionInfo) {
        foreach ($directionInfo as $groupName => $groupInfo) {
            if (empty($groupInfo)) {
                unset($changed[$directionName][$groupName]);
            } else {
                foreach ($groupInfo as $no => $p) {
                    $changed[$directionName][$groupName][$no] = $p . ' (' . $permInfo[$p]['type'] . ')';
                    $changeCount++;
                }
            }
        }
        if (empty($changed[$directionName])) {
            unset($changed[$directionName]);
        }
    }
    if ($changeCount > 0) {
        Feedback::add(['type' => $_REQUEST['permType'],
            'mes' => $changed,
            'objname' => $_REQUEST['objectName'],
            'objid' => $_REQUEST['objectId'],
            'objtype' => $_REQUEST['objectType'],
            'count' => $changeCount,
            'tpl' => 'perm']);
    } else {
        Feedback::note(tr('No permissions were changed'));
    }
}

if (isset($_REQUEST['remove']) && $access->checkCsrf(true)) {
    $newPermissions = new Perms_Reflection_PermissionSet();
    $permissionApplier->apply($newPermissions);
}

if (isset($_REQUEST['copy'])) {
    $newPermissions = get_assign_permissions();
    $filter = TikiFilter::get('text');
    $to_copy = [
                    'perms' => $newPermissions->getPermissionArray(),
                    'object' => $filter->filter($_REQUEST['objectId']),
                    'type' => $filter->filter($_REQUEST['objectType'])
    ];
    $_SESSION['perms_clipboard'] = $to_copy;
}

if (! empty($_SESSION['perms_clipboard'])) {
    $perms_clipboard = $_SESSION['perms_clipboard'];
    $smarty->assign(
        'perms_clipboard_source',
        $perms_clipboard['type'] . (empty($perms_clipboard['object']) ? '' : ' : ') . $perms_clipboard['object']
    );

    if (isset($_REQUEST['paste']) && $access->checkCsrf()) {
        unset($_SESSION['perms_clipboard']);

        $set = new Perms_Reflection_PermissionSet();

        if (isset($perms_clipboard['perms'])) {
            foreach ($perms_clipboard['perms'] as $group => $gperms) {
                foreach ($gperms as $perm) {
                    $set->add($group, $perm);
                }
            }
        }
        $permissionApplier->apply($set);
        $smarty->assign('perms_clipboard_source', '');
    }
}

// Prepare display
// Get the individual object permissions if any
$displayedPermissions = get_displayed_permissions();

//Quickperms apply {{{
//Test to map permissions of ile galleries into read write admin admin levels.
if ($prefs['feature_quick_object_perms'] == 'y') {
    $qperms = quickperms_get_data();
    $smarty->assign('quickperms', $qperms);
    $quickperms = new Perms_Reflection_Quick();

    foreach ($qperms as $type => $data) {
        $quickperms->configure($type, $data['data']);
    }

    $groupNames = [];
    foreach ($groups['data'] as $key => $group) {
        $groupNames[] = $group['groupName'];
    }

    $map = $quickperms->getAppliedPermissions($displayedPermissions, $groupNames);

    foreach ($groups['data'] as $key => $group) {
        $groups['data'][$key]['groupSumm'] = $map[ $group['groupName'] ];
    }

    if (isset($_REQUEST['assign']) && isset($_REQUEST['quick_perms'])) {
        $access->checkCsrf();

        $groups = $userlib->get_groups(0, -1, 'groupName_asc', '', '', 'n');

        $userInput = [];
        foreach ($groups['data'] as $group) {
            $groupNameEncoded = rawurlencode($group['groupName']);
            if (isset($_REQUEST['perm_' . $groupNameEncoded])) {
                $group = $group['groupName'];
                $permission = $_REQUEST['perm_' . $groupNameEncoded];

                $userInput[$group] = $permission;
            }
        }

        $current = $currentObject->getDirectPermissions();
        $newPermissions = $quickperms->getPermissions($current, $userInput);
        if (! $newPermissions->has('Admins', 'tiki_p_admin')) {
            $newPermissions->add('Admins', 'tiki_p_admin');
        }
        $permissionApplier->apply($newPermissions);
        $url = $_SERVER['REQUEST_URI'];
        $query = array_filter(array_intersect_key($_REQUEST, array_flip(['objectType', 'objectId', 'permType', 'objectName'])));
        if ($query) {
            $url .= '?' . http_build_query($query, null, '&');
        }
        $access->redirect($url);
    }
}

if (isset($_REQUEST['used_groups'])) {
    $group_filter = [];
    foreach ($displayedPermissions->getPermissionArray() as $group => $perms) {
        $group_filter[] = $group;
        $group_filter = array_merge($group_filter, $userlib->get_including_groups($group, 'y'));
    }
    if (empty($group_filter)) {
        $group_filter = ['Anonymous', 'Registered', 'Admins'];
    }
    foreach ($group_filter as $i => $group) {
        $ginfo = $userlib->get_group_info($group);
        $group_filter[$i] = $ginfo['id'];
    }
    $cookietab = 1;
}


// get groupNames etc - TODO: jb will tidy...
//$checkboxInfo = array();
$permGroups = [];
$groupNames = [];
$groupIndices = [];
$groupInheritance = [];

foreach ($groups['data'] as &$row) {
    if ($group_filter !== false && in_array($row['id'], $group_filter)) {
        $groupNames[] = $row['groupName'];
        $permGroups[] = 'perm[' . $row['groupName'] . ']';
        $groupInheritance[] = $userlib->get_included_groups($row['groupName']);
        $inh = $userlib->get_included_groups($row['groupName']);

        $groupIndices[] = $row['groupName'] . '_hasPerm';

        $row['in_group_filter'] = 'y';
    } else {
        $row['in_group_filter'] = 'n';
    }

    // info for nested group treetable
    $parents = array_merge([$row['groupName']], $userlib->get_included_groups($row['groupName']));
    $parents = preg_replace('/[\s,]+/', '_', $parents);
    $parents = implode(",", array_reverse($parents));
    $row['parents'] = $parents;

// More TODO - merge all this into a single array - but that means considerable changes to treetable (soon)
//  $checkboxInfo[] = array('name' => $row['groupName'],
//                       'key' => 'perm['.$row['groupName'].']',
//                       'index' => $groupIndex,
//                       'inheritance' => $inh);
}

$smarty->assign('permGroups', $permGroups);
$smarty->assign('permGroupCols', $groupIndices);
$smarty->assign('groupNames', $groupNames);
//$smarty->assign('groupInheritance', $groupInheritance);


// Get the big list of permissions
if (isset($_REQUEST['show_disabled_features']) && ($_REQUEST['show_disabled_features'] == 'on' || $_REQUEST['show_disabled_features'] == 'y')) {
    $show_disabled_features = 'y';
} else {
    $show_disabled_features = 'n';
}
$smarty->assign('show_disabled_features', $show_disabled_features);

// get "master" list of all perms
$candidates = $userlib->get_permissions(0, -1, 'permName_asc', '', $_REQUEST['permType'], '', $show_disabled_features != 'y' ? true : false);

// list of all features
$ftemp = $userlib->get_permission_types();
$features = [];
foreach ($ftemp as $f) {
    $features[] = ['featureName' => $f, 'in_feature_filter' => $feature_filter === false || in_array($f, $feature_filter) ? 'y' : 'n'];
}
$features_enabled = [];

// build $masterPerms list and used (enabled) features
$masterPerms = [];

foreach ($candidates['data'] as $perm) {
    $perm['label'] = tra($perm['permDesc']) . ' <em>(' . $perm['permName'] . ')</em>' . '<span style="display:none;">' . tra($perm['level'] . '</span>');

    foreach ($groupNames as $index => $groupName) {
        $p = $displayedPermissions->has($groupName, $perm['permName']) ? 'y' : 'n';
        $perm[$groupName . '_hasPerm'] = $p;
        $perm[$groupIndices[$index]] = $p;
    }

    // work out if specific feature is on
    $pref_feature = false;
    if (isset($perm['feature_check'])) {
        foreach (explode(',', $perm['feature_check']) as $fchk) {
            if ($prefs[$fchk] == 'y') {
                $pref_feature = true;
                break;
            }
        }
    } else {    // if no feature check you can't turn them off (?)
        $pref_feature = true;
    }

    if (
        ($feature_filter === false || in_array($perm['type'], $feature_filter))
                && ($restrictions === false || in_array($perm['permName'], $restrictions))
                && $pref_feature
    ) {
        $masterPerms[] = $perm;
    }
    if ($show_disabled_features != 'y' && ! in_array($perm['type'], $features_enabled)) {
        // perms can be dependant on multiple features
        if ($pref_feature) {
            $features_enabled[] = $perm['type'];
        }
    }
}

if ($show_disabled_features != 'y') {
    $features_filtered = [];
    foreach ($features as $f) {
        if (in_array($f['featureName'], $features_enabled) && ! in_array($f, $features_filtered)) {
            $features_filtered[] = $f;
        }
    }
    $features = $features_filtered;
}

$smarty->assign_by_ref('perms', $masterPerms);
$smarty->assign_by_ref('features', $features);

// Create JS to set up checkboxs (showing group inheritance)
$js = '$("#perms_busy").show();
';
$i = 0;
foreach ($groupNames as $groupName) {
    $groupName = addslashes($groupName);
    $beneficiaries = '';
    foreach ($groupInheritance as $index => $gi) {
        if (is_array($gi) && in_array($groupName, $gi)) {
            $beneficiaries .= ! empty($beneficiaries) ? ',' : '';
            $beneficiaries .= 'input[name="perm[' . addslashes($groupNames[$index]) . '][]"]';
        }
    }

    $js .= "\$('input[name=\"perm[$groupName][]\"]').eachAsync({
    delay: 10,
    bulk: 0,
";
    if ($i == count($groupNames) - 1) {
        $js .= "end: function () {
                \$('#perms_busy').hide();
            },
";
    }
    $js .= "loop: function() {         // each one of this group

    if (\$(this).is(':checked')) {
        \$('input[value=\"'+\$(this).val()+'\"]').                    // other checkboxes of same value (perm)
            filter('$beneficiaries').                                // which inherit from this
            prop('checked',\$(this).is(':checked')).                // check and disable
            prop('disabled',\$(this).is(':checked'));
    }

    \$(this).on( 'change', function(e, parent) {    // bind click event

        if (\$(this).is(':checked')) {
            \$('input[value=\"'+\$(this).val()+'\"]').            // same...
                filter('$beneficiaries').each(function() {
                    $(this).
                        prop('checked',true).                    // check?
                        prop('disabled',true).                    // disable
                        trigger('change', [this]);
                });
        } else {
            \$('input[value=\"'+\$(this).val()+'\"]').            // same...
                filter('$beneficiaries').each(function() {
                    $(this).
                        prop('checked',false).                    // check?
                        prop('disabled',false).                    // disable
                        trigger('change', [this]);
                });
        }
    });
}
});

";
    $i++;
}   // end of for $groupNames loop

    // add cell colouring helpers
    $js .= /** @lang JavaScript */
        '
const $objectPermTables = $("table.objectperms tbody");

$("input[type=checkbox]", $objectPermTables).on("change", function () {
    const $this = $(this);
    const $parent = $this.parent();
    $this.data("checked", $this.is(":checked"));
    if ($this.is(":checked")) {
        if ($parent.hasClass("removed")) {
            $parent.removeClass("removed");
        } else {
            $parent.addClass("added");
        }
    } else {
        if ($parent.hasClass("added")) {
            $parent.removeClass("added");
        } else {
            $parent.addClass("removed");
        }
    }
});

/*** reduce the number of inputs by removing unchanged ones ***/

const $objectPermsForm = $objectPermTables.parents("form");
const $submitButtons = $("input[type=submit]", $objectPermsForm);

const removeUnchangedCheckboxes = function () {
    // this needs to remove the inputs from the confirm popup form as it runs before
    $("input[name^=\'perm[\']", "form#confirm-popup").each(function() {
        const $this = $(this),
            $oldPermInput = $this.parent()
                .find("input[name=\'old_" + $this.attr("name") + "\'][value=\'" + $this.val() + "\']");

        // old_perm[blah] is there when a perm is checked, so the back end can tell its been unchecked
        // so if theyre both there then its still checked
        if ($oldPermInput.length) {
            $oldPermInput.remove();
            $this.remove();
        }
    });

};

$submitButtons.on("click", function() {
  removeUnchangedCheckboxes();
  return true;
});

';

$headerlib->add_jq_onready($js);

// setup smarty remarks flags

// Display the template
$smarty->assign('mid', 'tiki-objectpermissions.tpl');
if (isset($_REQUEST['filegals_manager']) && $_REQUEST['filegals_manager'] != '') {
    $smarty->assign('filegals_manager', $_REQUEST['filegals_manager']);
    $smarty->display('tiki-print.tpl');
} else {
    $smarty->display('tiki.tpl');
}


/**
 * @return mixed
 */
function get_assign_permissions()
{
    global $objectFactory;

    // get existing perms
    $currentObject = $objectFactory->get($_REQUEST['objectType'], $_REQUEST['objectId'], $_REQUEST['parentId']);
    $currentPermissions = $currentObject->getDirectPermissions();
    if (count($currentPermissions->getPermissionArray()) === 0) {
        // get "default" perms so disabled feature perms don't get removed
        $currentPermissions = $currentObject->getParentPermissions();
    }

    // set any checked ones
    if (isset($_REQUEST['perm']) && ! empty($_REQUEST['perm'])) {
        foreach ($_REQUEST['perm'] as $group => $gperms) {
            foreach ($gperms as $perm) {
                $currentPermissions->add($group, $perm);
            }
        }
    }

    // unset any old_perms not there now
    if (isset($_REQUEST['old_perm'])) {
        foreach ($_REQUEST['old_perm'] as $group => $gperms) {
            foreach ($gperms as $perm) {
                if (! isset($_REQUEST['perm'][$group]) || ! in_array($perm, $_REQUEST['perm'][$group])) {
                    $currentPermissions->remove($group, $perm);
                }
            }
        }
    }

    return $currentPermissions;
}

/**
 * @return array
 */
function quickperms_get_data()
{
    if ($_REQUEST['permType'] == 'file galleries') {
        return quickperms_get_filegal();
    } else {
        return quickperms_get_generic();
    }
}

/**
 * @return array
 */
function quickperms_get_filegal()
{
    return [
        'admin' => [
            'name' => 'admin',
            'data' => [
                'tiki_p_admin_file_galleries' => 'tiki_p_admin_file_galleries',
                'tiki_p_assign_perm_file_gallery' => 'tiki_p_assign_perm_file_gallery',
                'tiki_p_batch_upload_files' => 'tiki_p_batch_upload_files',
                'tiki_p_batch_upload_file_dir' => 'tiki_p_batch_upload_file_dir',
                'tiki_p_create_file_galleries' => 'tiki_p_create_file_galleries',
                'tiki_p_download_files' => 'tiki_p_download_files',
                'tiki_p_edit_gallery_file' => 'tiki_p_edit_gallery_file',
                'tiki_p_list_file_galleries' => 'tiki_p_list_file_galleries',
                'tiki_p_upload_files' => 'tiki_p_upload_files',
                'tiki_p_remove_files' => 'tiki_p_remove_files',
                'tiki_p_view_fgal_explorer' => 'tiki_p_view_fgal_explorer',
                'tiki_p_view_fgal_path' => 'tiki_p_view_fgal_path',
                'tiki_p_view_file_gallery' => 'tiki_p_view_file_gallery',
            ],
        ],
        'write' => [
            'name' => 'write',
            'data' => [
                'tiki_p_batch_upload_files' => 'tiki_p_batch_upload_files',
                'tiki_p_batch_upload_file_dir' => 'tiki_p_batch_upload_file_dir',
                'tiki_p_create_file_galleries' => 'tiki_p_create_file_galleries',
                'tiki_p_download_files' => 'tiki_p_download_files',
                'tiki_p_edit_gallery_file' => 'tiki_p_edit_gallery_file',
                'tiki_p_list_file_galleries' => 'tiki_p_list_file_galleries',
                'tiki_p_upload_files' => 'tiki_p_upload_files',
                'tiki_p_remove_files' => 'tiki_p_remove_files',
                'tiki_p_view_fgal_explorer' => 'tiki_p_view_fgal_explorer',
                'tiki_p_view_fgal_path' => 'tiki_p_view_fgal_path',
                'tiki_p_view_file_gallery' => 'tiki_p_view_file_gallery',
            ],
        ],
        'read' => [
            'name' => 'read',
            'data' => [
                'tiki_p_download_files' => 'tiki_p_download_files',
                'tiki_p_list_file_galleries' => 'tiki_p_list_file_galleries',
                'tiki_p_view_fgal_explorer' => 'tiki_p_view_fgal_explorer',
                'tiki_p_view_fgal_path' => 'tiki_p_view_fgal_path',
                'tiki_p_view_file_gallery' => 'tiki_p_view_file_gallery',
            ],
        ],
        'none' => [
            'name' => 'none',
            'data' => [
            ],
        ],
    ];
}

/**
 * @return array
 */
function quickperms_get_generic()
{
    global $show_disabled_features;

    $userlib = TikiLib::lib('user');

    $databaseperms = $userlib->get_permissions(0, -1, 'permName_asc', '', $_REQUEST['permType'], '', $show_disabled_features);
    foreach ($databaseperms['data'] as $perm) {
        if ($perm['level'] == 'basic') {
            $quickperms_['basic'][$perm['permName']] = $perm['permName'];
        } elseif ($perm['level'] == 'registered') {
            $quickperms_['registered'][$perm['permName']] = $perm['permName'];
        } elseif ($perm['level'] == 'editors') {
            $quickperms_['editors'][$perm['permName']] = $perm['permName'];
        } elseif ($perm['level'] == 'admin') {
            $quickperms_['admin'][$perm['permName']] = $perm['permName'];
        }
    }

    if (! isset($quickperms_['basic'])) {
        $quickperms_['basic'] = [];
    }
    if (! isset($quickperms_['registered'])) {
        $quickperms_['registered'] = [];
    }
    if (! isset($quickperms_['editors'])) {
        $quickperms_['editors'] = [];
    }
    if (! isset($quickperms_['admin'])) {
        $quickperms_['admin'] = [];
    }

    $perms = [];
    $perms['basic']['name'] = 'basic';
    $perms['basic']['data'] = array_merge($quickperms_['basic']);
    $perms['registered']['name'] = 'registered';
    $perms['registered']['data'] = array_merge($quickperms_['basic'], $quickperms_['registered']);
    $perms['editors']['name'] = 'editors';

    $perms['editors']['data'] = array_merge(
        $quickperms_['basic'],
        $quickperms_['registered'],
        $quickperms_['editors']
    );

    $perms['admin']['name'] = 'admin';

    $perms['admin']['data'] = array_merge(
        $quickperms_['basic'],
        $quickperms_['registered'],
        $quickperms_['editors'],
        $quickperms_['admin']
    );
    $perms['none']['name'] = 'none';
    $perms['none']['data'] = [];

    return $perms;
}

/**
 * @return array|bool
 */
function perms_get_restrictions()
{
    $userlib = TikiLib::lib('user');
    $perms = Perms::get();

    if ($perms->admin_objects) {
        return false;
    }

    $masterPerms = $userlib->get_permissions(0, -1, 'permName_asc', '', $_REQUEST['permType']);
    $masterPerms = $masterPerms['data'];

    $allowed = [];
    // filter out non-admin's unavailable perms
    foreach ($masterPerms as $perm) {
        $name = $perm['permName'];

        if ($perms->$name) {
            $allowed[] = $name;
        }
    }

    return $allowed;
}

/**
 * @return mixed
 */
function get_displayed_permissions()
{
    global $objectFactory;
    $smarty = TikiLib::lib('smarty');

    $currentObject = $objectFactory->get($_REQUEST['objectType'], $_REQUEST['objectId'], $_REQUEST['parentId']);
    $displayedPermissions = $currentObject->getDirectPermissions();
    $globPerms = $objectFactory->get('global', null)->getDirectPermissions();   // global perms

    $comparator = new Perms_Reflection_PermissionComparator($displayedPermissions, new Perms_Reflection_PermissionSet());

    $smarty->assign('permissions_displayed', 'direct');
    if ($comparator->equal()) {
        $parent = $currentObject->getParentPermissions();                           // inherited perms (could be category ones)
        $comparator = new Perms_Reflection_PermissionComparator($globPerms, $parent);

        if ($comparator->equal()) {
            $smarty->assign('permissions_displayed', 'global');
        } else {                                                                    // parent not globals, check parent object or category
            $parentType = Perms::parentType($_REQUEST['objectType']);
            $parentObject = $objectFactory->get($parentType, $_REQUEST['parentId']);
            $parentPerms = $parentObject->getDirectPermissions();
            $comparator = new Perms_Reflection_PermissionComparator($parentPerms, $parent);
            if ($comparator->equal()) {
                $smarty->assign('permissions_displayed', 'parent');
                $smarty->assign('permissions_parent_id', $_REQUEST['parentId']);
                $smarty->assign('permissions_parent_type', $parentType);
                $smarty->assign('permissions_parent_name', TikiLib::lib('object')->get_title($parentType, $_REQUEST['parentId']));
            } else {
                $smarty->assign('permissions_displayed', 'category');
            }
        }
        $displayedPermissions = $parent;
    } else {                                                                        // direct object perms
        $comparator = new Perms_Reflection_PermissionComparator($globPerms, $displayedPermissions);
        $permissions_added = [];
        $permissions_removed = [];
        foreach ($comparator->getAdditions() as $p) {
            if (! isset($permissions_added[$p[0]])) {
                $permissions_added[$p[0]] = [];
            }
            $permissions_added[$p[0]][] = str_replace('tiki_p_', '', $p[1]);
        }
        foreach ($comparator->getRemovals() as $p) {
            if (! isset($permissions_removed[$p[0]])) {
                $permissions_removed[$p[0]] = [];
            }
            $permissions_removed[$p[0]][] = str_replace('tiki_p_', '', $p[1]);
        }
        $added = '';
        $removed = '';
        foreach ($permissions_added as $gp => $pm) {
            $added .= '<br />';
            $added .= '<strong>' . $gp . ':</strong> ' . implode(', ', $pm);
        }
        foreach ($permissions_removed as $gp => $pm) {
            $removed .= '<br />';
            $removed .= '<strong>' . $gp . ':</strong> ' . implode(', ', $pm);
        }
        $smarty->assign('permissions_added', $added);
        $smarty->assign('permissions_removed', $removed);

        TikiLib::lib('header')->add_jq_onready('
var permsAdded = ' . json_encode($permissions_added) . ';
var permsRemoved = ' . json_encode($permissions_removed) . ';
for (var group in permsAdded) {
    if (permsAdded.hasOwnProperty(group)) {
        for (var i = 0; i < permsAdded[group].length; i++) {
             $("input[name=\'perm[" + `group` + "][]\'][value=\'tiki_p_" + permsAdded[group][i] + "\']").parent().addClass("added");
        }
    }
}
for (var group in permsRemoved) {
    if (permsRemoved.hasOwnProperty(group)) {
        for (var i = 0; i < permsRemoved[group].length; i++) {
             $("input[name=\'perm[" + `group` + "][]\'][value=\'tiki_p_" + permsRemoved[group][i] + "\']").parent().addClass("removed");
        }
    }
}
');
    }

    return $displayedPermissions;
}
