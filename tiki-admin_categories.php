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
        'staticKeyFilters' => [
            'save'         => 'bool',                //post
            'parentId'     => 'int',                 //get
            'cookietab'    => 'int',                 //get
            'sort_mode'    => 'striptags',           //get
            'name'         => 'striptags',           //post
            'description'  => 'xss',                 //post
        ],
    ],
];
require_once('tiki-setup.php');
$categlib = TikiLib::lib('categ');
$rolesRepo = TikiLib::lib('roles');

$roles = TikiLib::lib('user')->list_role_groups();
if (! empty($_REQUEST["categId"])) {
    $info = $categlib->get_category($_REQUEST["categId"]);
} else {
    $_REQUEST["categId"] = 0;
    $info["name"] = '';
    $info["description"] = '';
}

@ini_set('max_execution_time', 0);    // as pagination is broken and almost every object gets fully loaded on this page
@ini_set('memory_limit', -1);        // at least try and avoid WSoD on large sites (TODO better still - see r30064)

$access->check_feature('feature_categories');
// Check for parent category or set to 0 if not present
if (! empty($_REQUEST['parentId']) && ! ($infoParent = $categlib->get_category($_REQUEST['parentId']))) {
    Feedback::error(tr('No such category with parentID %0'), (int)$_REQUEST['parentId']);
}

if (! isset($_REQUEST['parentId'])) {
    $_REQUEST['parentId'] = 0;
}
$smarty->assign('parentId', $_REQUEST['parentId']);

$categories = $categlib->getCategories(null, false, true, true, 'admin_categories');

if (empty($_REQUEST['parentId'])) {
    if (empty($categories) && $tiki_p_admin_categories !== 'y') {
        $access->check_permission('tiki_p_admin_categories');
    }
} else {
    $access->check_permission('tiki_p_admin_categories', '', 'category', $_REQUEST['parentId']);
}

if (isset($_REQUEST["addpage"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a page
    // add multiple pages at once
    $totalRows = 0;
    if (! empty($_REQUEST['pageName'])) {
        foreach ($_REQUEST['pageName'] as $value) {
            $result = $categlib->categorize_any('wiki page', $value, $_REQUEST["parentId"]);
            if ($result) {
                $totalRows++;
            }
            $category = $categlib->get_category($_REQUEST["parentId"]);
            $categorizedObject = $categlib->get_categorized_object('wiki page', $value);
            // Notify the users watching this category.
            $values = [
                "categoryId" => $_REQUEST["parentId"],
                "categoryName" => $category['name'],
                "categoryPath" => $categlib->get_category_path_string_with_root($_REQUEST["parentId"]),
                "description" => $category['description'],
                "parentId" => $category['parentId'],
                "parentName" => $categlib->get_category_name($category['parentId']),
                "action" => "object entered category",
                "objectName" => $categorizedObject['name'],
                "objectType" => $categorizedObject['type'],
                "objectUrl" => $categorizedObject['href'],
            ];
            $categlib->notify($values);
        }
        if ($totalRows) {
            $msg = $totalRows === 1 ? tr('One page added to category')
                : tr('%0 pages added to category', $totalRows);
            Feedback::success($msg);
        } else {
            Feedback::error(tr('No pages added to category'));
        }
    } else {
        Feedback::error(tr('No page has been selected, please choose at least one page.'));
    }
}
if (isset($_REQUEST["addpoll"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a poll
    $categlib->categorize_any('poll', $_REQUEST["pollId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('poll', $_REQUEST["pollId"]);
}
if (isset($_REQUEST["addfaq"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a faq
    $categlib->categorize_any('faq', $_REQUEST["faqId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('faq', $_REQUEST["faqId"]);
}
if (isset($_REQUEST["addtracker"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a tracker
    $categlib->categorize_any('tracker', $_REQUEST["trackerId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('tracker', $_REQUEST["trackerId"]);
}
if (isset($_REQUEST["addquiz"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a quiz
    $categlib->categorize_any('quiz', $_REQUEST["quizId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('quiz', $_REQUEST["quizId"]);
}
if (isset($_REQUEST["addforum"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a forum
    $categlib->categorize_any('forum', $_REQUEST["forumId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('forum', $_REQUEST["forumId"]);
}
if (isset($_REQUEST["addgallery"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize an image gallery
    $categlib->categorize_any('image gallery', $_REQUEST["galleryId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('image gallery', $_REQUEST["galleryId"]);
}
if (isset($_REQUEST["addfilegallery"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a file gallery
    $categlib->categorize_any('file gallery', $_REQUEST["file_galleryId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('file gallery', $_REQUEST["file_galleryId"]);
}
if (isset($_REQUEST["addarticle"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize an article
    $categlib->categorize_any('article', $_REQUEST["articleId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('article', $_REQUEST["articleId"]);
}
if (isset($_REQUEST["addblog"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a blog
    $categlib->categorize_any('blog', $_REQUEST["blogId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('blog', $_REQUEST["blogId"]);
}
if (isset($_REQUEST["adddirectory"]) && $_REQUEST["parentId"] != 0 && $access->checkCsrf()) {
    // Here we categorize a directory category
    $categlib->categorize_any('directory', $_REQUEST["directoryId"], $_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object('directory', $_REQUEST["directoryId"]);
}
if (isset($categorizedObject) && ! isset($_REQUEST["addpage"])) {
    $category = $categlib->get_category($_REQUEST["parentId"]);
    // Notify the users watching this category.
    $values = [
        "categoryId" => $_REQUEST["parentId"],
        "categoryName" => $category['name'],
        "categoryPath" => $categlib->get_category_path_string_with_root($_REQUEST["parentId"]),
        "description" => $category['description'],
        "parentId" => $category['parentId'],
        "parentName" => $categlib->get_category_name($category['parentId']),
        "action" => "object entered category",
        "objectName" => $categorizedObject['name'],
        "objectType" => $categorizedObject['type'],
        "objectUrl" => $categorizedObject['href'],
    ];
    $categlib->notify($values);
}

if (isset($_REQUEST["removeObject"]) && $access->checkCsrf(true)) {
    $category = $categlib->get_category($_REQUEST["parentId"]);
    $categorizedObject = $categlib->get_categorized_object_via_category_object_id($_REQUEST["removeObject"]);
    $result = $categlib->remove_object_from_category($_REQUEST["removeObject"], $_REQUEST["parentId"]);

    if (! empty($result) && $result->numRows()) {
        // Notify the users watching this category.
        $values = [
            "categoryId" => $_REQUEST["parentId"],
            "categoryName" => $category['name'],
            "categoryPath" => $categlib->get_category_path_string_with_root($_REQUEST["parentId"]),
            "description" => $category['description'],
            "parentId" => $category['parentId'],
            "parentName" => $categlib->get_category_name($category['parentId']),
            "action" => "object leaved category",
            "objectName" => $categorizedObject['name'],
            "objectType" => $categorizedObject['type'],
            "objectUrl" => $categorizedObject['href'],
        ];
        $categlib->notify($values);// update search index if required
        require_once 'lib/search/refresh-functions.php';
        refresh_index($categorizedObject['type'], $categorizedObject['itemId']);
        $ucfirstType = ucfirst($categorizedObject['type']);
        Feedback::success(tr(
            '%0 %1 removed from category %2',
            $ucfirstType,
            $categorizedObject['name'],
            $category['name']
        ));
    } else {
        $ucfirstType = ucfirst($categorizedObject['type']);
        Feedback::error(tr(
            '%0 %1 not removed from category %2',
            $ucfirstType,
            $categorizedObject['name'],
            $category['name']
        ));
    }
}
if (
    isset($_REQUEST["removeCat"]) && $categlib->get_category($_REQUEST['removeCat'])
        && $access->checkCsrf(true)
) {
    $access->check_permission('tiki_p_admin_categories', '', 'category', $_REQUEST['removeCat']);
    $result = $categlib->remove_category($_REQUEST["removeCat"]);
    if (! empty($result) && $result->numRows()) {
        Feedback::success(tr('Category ID %0 and descendants deleted', (int) $_REQUEST['removeCat']));
    } else {
        Feedback::error(tr('Category ID %0 not deleted', (int) $_REQUEST['removeCat']));
    }
}
if (isset($_REQUEST["save"]) && isset($_REQUEST["name"]) && strlen($_REQUEST["name"]) > 0 && $access->checkCsrf()) {
    // Save
    if (! empty($_REQUEST["tplGroupContainer"]) && strpos($_REQUEST["tplGroupPattern"], '--groupname--') === false) {
        Feedback::error(tra('A pattern that does not contain "--groupname--" is not allowed'));
    }
    if ($_REQUEST["categId"]) {
        if ($_REQUEST['parentId'] == $_REQUEST['categId']) {
            Feedback::error(tra('Category cannot be parent of itself - no changes made'));
        } else {
            try {
                $categlib->update_category(
                    $_REQUEST["categId"],
                    $_REQUEST["name"],
                    $_REQUEST["description"],
                    $_REQUEST["parentId"],
                    $_REQUEST["tplGroupContainer"] ?? 0,
                    $_REQUEST["tplGroupPattern"] ?? null
                );
                if ($tiki_p_admin_categories == 'y' && ! empty($_REQUEST['parentPerms'])) {
                    $userlib->remove_object_permission('', $_REQUEST['categId'], 'category', '');
                    $userlib->copy_object_permissions($_REQUEST['parentId'], $_REQUEST['categId'], 'category');
                }
                Feedback::success(tr('Category %0 updated', htmlspecialchars($_REQUEST["name"])));
            } catch (Exception $e) {
                $errors['mes'] = $e->getMessage();
            }
        }
    } else {
        try {
            $newcategId = $categlib->add_category(
                $_REQUEST["parentId"],
                $_REQUEST["name"],
                $_REQUEST["description"],
                $_REQUEST["tplGroupContainer"] ?? null,
                $_REQUEST["tplGroupPattern"] ?? null
            );
            $_REQUEST["categId"] = $newcategId;
            if ($tiki_p_admin_categories != 'y' || ! empty($_REQUEST['parentPerms'])) {
                $userlib->copy_object_permissions($_REQUEST['parentId'], $newcategId, 'category');
                Perms::getInstance()->clear();
            }
            Feedback::success(tr('Category %0 created', htmlspecialchars($_REQUEST["name"])));
        } catch (Exception $e) {
            $errors['mes'] = $e->getMessage();
        }
    }

    $cRolesInput = isset($_REQUEST["categoryRole"]) ? $_REQUEST["categoryRole"] : [];
    $rolesToSave = [];
    if (isset($cRolesInput) && ! empty($cRolesInput)) {
        $rolesRepo->deleteSelectedCategoryRoleNotUsed($_REQUEST["categId"]);
        $rolesToSave = [];
        foreach ($cRolesInput as $selects) {
            $categId = array_keys($selects)[0];
            $value = array_values($selects)[0];
            $categRoleId = array_keys($value)[0];
            $groupRoleId = array_keys($value[$categRoleId])[0];
            $groupId = empty($value[$categRoleId][$groupRoleId]) ? 0 : $value[$categRoleId][$groupRoleId];
            $rolesRepo->insertOrUpdateSelectedCategoryRole($categId, $categRoleId, $groupRoleId, $groupId);
        }
    }
    if (! empty($_REQUEST['applyRoles']) && $_REQUEST['applyRoles'] == "on" && ! empty($_REQUEST['rolesToApply'])) {
        $rolesRepo->applyRoles($_REQUEST["categId"], $_REQUEST['rolesToApply']);
    } else {
        $rolesRepo->applyRoles($_REQUEST["categId"], []);
    }
    $rolesRepo->deleteRolesWithoutParent($_REQUEST["categId"]);

    if (isset($_REQUEST["tplGroupContainer"]) && ! empty($_REQUEST["tplGroupContainer"]) && ! empty($_REQUEST["categId"])) {
        $categlib->manage_sub_categories($_REQUEST["categId"]);
    }
    $cookietab = 1;
}
if (isset($_REQUEST['import']) && ! empty($_FILES['csvlist']['tmp_name']) && $access->checkCsrf()) {
    $fhandle = fopen($_FILES['csvlist']['tmp_name'], 'r');
    if (! $fhandle) {
        Feedback::error(tr("The file has incorrect syntax or is not a CSV file"));
    } else {
        $fields = fgetcsv($fhandle, 1000, escape: "");
        if (! $fields[0]) {
            Feedback::error(tr("The file has incorrect syntax or is not a CSV file"));
        } else {
            $bom = "\xef\xbb\xbf";
            if (($fields[0] !== 'category' && $fields[0] !== $bom . 'category') || $fields[1] !== 'description' || $fields[2] !== 'parent') {
                Feedback::error(tr('The file does not have the required header:') . ' category, description, parent');
            } else {
                while (! feof($fhandle)) {
                    $success = false;
                    $data = fgetcsv($fhandle, 1000, escape: "");
                    if (! empty($data)) {
                        $temp_max = count($fields);
                        $getCategory = true;
                        if ($temp_max > 1 && strtolower($data[2]) != 'top' && ! empty($data[2])) {
                            $parentId = $categlib->get_category_id($data[2]);
                            if (empty($parentId)) {
                                Feedback::error(tr('Incorrect parameter %0', $data[2]));
                                $getCategory = false;
                            } else {
                                $access->check_permission('tiki_p_admin_categories', '', 'category', $parentId);
                            }
                        } else {
                            $access->check_permission('tiki_p_admin_categories');
                            $parentId = 0;
                        }
                        if (! empty($getCategory)) {
                            if (! $categlib->exist_child_category($parentId, $data[0])) {
                                $newcategId = $categlib->add_category($parentId, $data[0], $data[1]);
                                if (empty($newcategId)) {
                                    Feedback::error(tr('Incorrect parameter %0', $data[0]));
                                } else {
                                    $success = true;
                                    if ($tiki_p_admin_categories != 'y') {
                                        $userlib->copy_object_permissions($parentId, $newcategId, 'category');
                                    }
                                }
                            }
                        }
                    }
                }
                if ($success) {
                    Feedback::success(tr('Categories imported'));
                }
            }
        }
    }
}
$smarty->assign('categId', $_REQUEST["categId"]);
$smarty->assign('categoryName', $info["name"]);
$smarty->assign('description', $info["description"]);
if (isset($info["tplGroupContainerId"])) {
    $smarty->assign('tplGroupContainerId', $info["tplGroupContainerId"]);
}
if (isset($info["tplGroupPattern"])) {
    $smarty->assign('tplGroupPattern', $info["tplGroupPattern"]);
}
// If the parent category is not zero get the category path
if ($_REQUEST["parentId"]) {
    $p_info = $categlib->get_category($_REQUEST["parentId"]);
    $father = $p_info["parentId"];
    if (! $p_info) {
        Feedback::error(tr('Invalid category'));
    }
    $smarty->assign('categ_name', $p_info['name']);
    $smarty->assign('path', $p_info['tepath']);
} else {
    $father = 0;
}
$smarty->assign('father', $father);

// ---------------------------------------------------

$categories = $categlib->getCategories(null, false, true, true, 'admin_categories');
if (empty($categories) && $tiki_p_admin_categories != 'y') {
    $access->check_permission('tiki_p_admin_categories');
}
$smarty->assign('categories', $categories);

$treeNodes = [];

$fetchCountIcon = smarty_function_icon(
    [
        'name'       => 'calculator',
        '_menu_text' => 'n',
        '_menu_icon' => 'n',
        'alt'        => tra('Fetch count'),
    ],
    $smarty->getEmptyInternalTemplate()
);

foreach ($categories as $category) {
    $perms = Perms::get(['type' => 'category', 'object' => $category['categId']]);
    if ($perms->admin_categories) {
        $data = '<a href="tiki-admin_categories.php?parentId='
            . $category['parentId']
            . '&amp;categId='
            . $category['categId'] . '&cookietab=2">'
            . smarty_function_icon(
                [
                    'name' => 'edit',
                    '_menu_text' => 'y',
                    '_menu_icon' => 'y',
                    'alt' => tra('Edit'),
                ],
                $smarty->getEmptyInternalTemplate()
            )
            . '</a>';
        $data .= '<a href="tiki-admin_categories.php?parentId='
            . $category['parentId']
            . '&amp;removeCat='
            . $category['categId']
            . '" onclick="confirmPopup(\'' . tr('Delete category?') . '\', \''
            . smarty_function_ticket(['mode' => 'get'], $smarty->getEmptyInternalTemplate()) . '\')">'
            . smarty_function_icon(
                [
                    'name' => 'remove',
                    '_menu_text' => 'y',
                    '_menu_icon' => 'y',
                    'alt' => tra('Delete'),
                ],
                $smarty->getEmptyInternalTemplate()
            )
            . '</a>';

        // check for global perm admin perms because the object perm admin_categories grants assign_perm_category
        if ($tiki_p_assign_perm_category === 'y') {
            if ($userlib->object_has_one_permission($category['categId'], 'category')) {
                $title = tra('Edit permissions for this category');
            } else {
                $title = tra('Assign permissions');
            }
            $data .= smarty_function_permission_link(
                [
                    'id' => $category['categId'],
                    'type' => 'category',
                    'mode' => 'text',
                ],
                $smarty->getEmptyInternalTemplate()
            );
        }
        $popupparams = ['trigger' => 'click', 'fullhtml' => 1, 'center' => true, 'text' => $data];
        $newdata = '<a class="tips" title="'
            . tra('Actions')
            . '" href="#" '
            . smarty_function_popup(
                $popupparams,
                $smarty->getEmptyInternalTemplate()
            )
            . 'style="padding:0; margin:0; border:0">' . smarty_function_icon(['name' => 'wrench'], $smarty->getEmptyInternalTemplate()) . '</a>';




        $catlink = '<a class="catname" href="tiki-admin_categories.php?parentId=' . $category["categId"] .
            '&cookietab=3" style="margin-left:5px">' . smarty_modifier_escape($category['name']) . '</a> ';

        if ($category['tplGroupContainerId'] > 0) {
            $catlink .= '
             <sup class="tikihelp" data-ori data-bs-original-title="' . tra('Managed by Templated Group') . '" data-bs-content="' . tra('Child categories will automatically be generated and managed for children of the selected Templated Groups Container.') . '">
                                        T
                                    </sup>
            ';
        }

        if ($category['num_roles'] > 0) {
            $catlink .= '
             <sup class="tikihelp" title="' . tra('Apply Role Permissions') . '" data-bs-content="' . tra('Roles permissions will automatically be applied to child categories.') . '">
                                        R
                                    </sup>
            ';
        }

        if (isset($prefs['category_browse_show_categids']) && $prefs['category_browse_show_categids'] === 'y') {
            $catid = "<span class='badge text-bg-light text-muted float-end'>ID: {$category["categId"]}</span>";
        } else {
            $catid = '';
        }

        if (isset($prefs['category_browse_show_categids']) && $prefs['category_browse_count_objects'] === 'y') {
            $objectcount = $categlib->list_category_objects(
                $category['categId'],
                0,
                0,
                ''
            );
            $countString = '<span class="object-count badge badge-pill badge-info bg-info float-end">' .
                $objectcount['cant'] . '</span>';
        } elseif ($prefs['feature_search'] === 'y') {
            // fall back to unified search if not category_browse_count_objects
            $countString = '<a class="object-count badge badge-pill badge-info bg-info float-end" data-categid="' .
                $category['categId'] . '">' . $fetchCountIcon . '</a>';

            $headerlib->add_jq_onready(
                /** @lang JavaScript */
                '$(".object-count").on("click", function () {
                    let $this = $(this).tikiModal("*");
                    $.getJSON(
                        $.service("search", "lookup"),
                        {
                        	filter: {
                                deep_categories: $this.data("categid"),
                                object_type: "not activity and not category",
                                searchable: "y"
                            },
                            maxRecords: 1
                        }).done(function (data) {
                        	$this.text(data.resultset.count).tikiModal();
                        	// now click all the child category badges
                        	$this.parent().find("ul .object-count").eachAsync({
                                bulk: 10,
                                delay: 500,
                                loop: function() { $(this).trigger("click"); }
                            });
                        });
                });
            '
            );
        }
        $desc = '<small class="d-block text-muted">' . smarty_modifier_escape($category['description']) . '</small>';

        $treeNodes[] = [
            'id' => $category['categId'],
            'parent' => $category['parentId'],
            'data' => $newdata . $catlink . $countString . $catid . $desc,
        ];
    }
}
include_once('lib/tree/BrowseTreeMaker.php');
$treeMaker = new BrowseTreeMaker('categ');
$smarty->assign('tree', $treeMaker->make_tree(0, $treeNodes));

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'name_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign('sort_mode', $sort_mode);
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
if (isset($_REQUEST["find_objects"])) {
    $find_objects = $_REQUEST["find_objects"];
} else {
    $find_objects = '';
}

// ---------------------------------------------------
if ($prefs['feature_search'] !== 'y' || $prefs['unified_add_to_categ_search'] !== 'y') {    // no unified search
    @ini_set(
        'max_execution_time',
        0
    );    // as pagination is broken and almost every object gets fully loaded on this page
    @ini_set('memory_limit', -1);        // at least try and avoid WSoD on large sites (TODO better still - see r30064)

    /**
     * @param $max
     * @param $data_key
     * @param null $data
     */
    function admin_categ_assign(&$max, $data_key, $data = null)
    {
        $smarty = TikiLib::lib('smarty');

        if (is_null($data)) {
            $data = ['data' => [], 'cant' => 0];
        }

        $smarty->assign($data_key, $data['data']);
        $smarty->assign('cant_' . $data_key, $data['cant']);

        $max = max($max, $data['cant']);
    }

    $articles = $galleries = $file_galleries = $forums = $polls = $blogs = $pages = $faqs = $quizzes = $trackers = $directories = $objects = null;

    $maxRecords = $prefs['maxRecords'];

    $smarty->assign('find_objects', $find_objects);
    $smarty->assign('sort_mode', $sort_mode);
    $smarty->assign('find', $find);

    $objects = $categlib->list_category_objects(
        $_REQUEST["parentId"],
        $offset,
        $maxRecords,
        $sort_mode,
        '',
        $find,
        false
    );

    if ($prefs['feature_file_galleries'] == 'y') {
        $filegallib = TikiLib::lib('filegal');
        $file_galleries = $filegallib->list_file_galleries(
            $offset,
            -1,
            'name_desc',
            'admin',
            $find_objects,
            $prefs['fgal_root_id']
        );
    }

    if ($prefs['feature_forums'] == 'y') {
        $commentslib = TikiLib::lib('comments');
        $forums = $commentslib->list_forums($offset, -1, 'name_asc', $find_objects);
    }

    if ($prefs['feature_polls'] == 'y') {
        $polllib = TikiLib::lib('poll');
        $polls = $polllib->list_polls($offset, $maxRecords, 'title_asc', $find_objects);
    }

    if ($prefs['feature_blogs'] == 'y') {
        $bloglib = TikiLib::lib('blog');
        $blogs = $bloglib->list_blogs($offset, -1, 'title_asc', $find_objects);
    }

    if ($prefs['feature_wiki'] == 'y') {
        $pages = $tikilib->list_pageNames($offset, -1, 'pageName_asc', $find_objects);
        //TODO for all other object types
        $pages_not_in_cat = [];
        foreach ($pages['data'] as $pg) {
            $found = false;
            foreach ($objects['data'] as $obj) {
                if ($obj['type'] == 'wiki page' && $obj['itemId'] == $pg['pageName']) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $pages_not_in_cat[] = $pg;
            }
        }
        $pages['cant'] = $pages['cant'] - count($pages['data']) + count($pages_not_in_cat);
        $pages['data'] = $pages_not_in_cat;
    }

    if ($prefs['feature_faqs'] == 'y') {
        $faqlib = TikiLib::lib('faq');
        $faqs = $faqlib->list_faqs($offset, -1, 'title_asc', $find_objects);
    }

    if ($prefs['feature_quizzes'] == 'y') {
        $quizzes = TikiLib::lib('quiz')->list_quizzes($offset, -1, 'name_asc', $find_objects);
    }

    if ($prefs['feature_trackers'] == 'y') {
        $trklib = TikiLib::lib('trk');
        $trackers = $trklib->list_trackers($offset, -1, 'name_asc', $find_objects);
    }

    if ($prefs['feature_articles'] == 'y') {
        $artlib = TikiLib::lib('art');
        $articles = $artlib->list_articles($offset, -1, 'title_asc', $find_objects, '', '', $user, '', '', 'n');
    }

    if ($prefs['feature_directory'] == 'y') {
        include_once('lib/directory/dirlib.php');
        $directories = $dirlib->dir_list_all_categories($offset, $maxRecords, 'name_asc', $find_objects);
    }

    $maximum = 0;
    admin_categ_assign($maximum, 'objects', $objects);
    admin_categ_assign($maximum, 'file_galleries', $file_galleries);
    admin_categ_assign($maximum, 'forums', $forums);
    admin_categ_assign($maximum, 'polls', $polls);
    admin_categ_assign($maximum, 'blogs', $blogs);
    admin_categ_assign($maximum, 'pages', $pages);
    admin_categ_assign($maximum, 'faqs', $faqs);
    admin_categ_assign($maximum, 'quizzes', $quizzes);
    admin_categ_assign($maximum, 'trackers', $trackers);
    admin_categ_assign($maximum, 'articles', $articles);
    admin_categ_assign($maximum, 'directories', $directories);

    $smarty->assign('maxRecords', $maxRecords);
    $smarty->assign('offset', $offset);
    $smarty->assign('maximum', $maximum);
} else {    // unified search
    $objects = $categlib->list_category_objects(
        $_REQUEST["parentId"],
        $offset,
        $prefs['maxRecords'],
        $sort_mode,
        '',
        $find,
        false
    );
    $smarty->assign('objects', $objects['data']);
    $smarty->assign('cant_objects', $objects['cant']);
    $objectlib = TikiLib::lib('object');
    $supportedTypes = array_intersect(
        TikiLib::lib('unifiedsearch')->getSupportedTypes(),
        $objectlib::get_supported_types()
    );
    $smarty->assign('types', $supportedTypes);
}

if (! empty($errors)) {
    Feedback::warning($errors);
}

if (! empty($_REQUEST["categId"])) {
    $access->check_permission('tiki_p_admin_categories', '', 'category', $_REQUEST['categId']);
    $availableIds = $rolesRepo->getAvailableCategoriesRolesIds($_REQUEST["categId"]);
    $smarty->assign('availableIds', $availableIds);

    if ($_REQUEST['parentId']) {
        $parentCategory = $categlib->get_category(intval($_REQUEST["parentId"]));
        $tikiDb = TikiDb::get();
        $groupsWithPerms = $rolesRepo->getAvailableCategoriesRoles($parentCategory['categId']);
    } else {
        $groupsWithPerms = [];
    }

    $catRolesListSelected = $rolesRepo->getSelectedCategoryRoles($_REQUEST["categId"]);
    $groupList = TikiLib::lib('user')->list_regular_groups();
    $groupListIndexed = [];
    foreach ($groupList as $group) {
        $groupListIndexed[$group['id']] = $group['groupName'];
    }
    //fetch selected groups
    $selectedGroups = [];
    $catRolesList = [];
    foreach ($groupsWithPerms as $group) {
        $selectedGroups[$group['id']] = [];
        $catRolesList[] = [
            'categId' => $info['categId'], //category Id
            'categRoleId' => $info['parentId'], //parent category Id
            'groupRoleId' => $group['id'], //role group
            'groupId' => '', //group selected
            'groupRoleName' => $group['groupName'],
        ];
    }
    foreach ($catRolesListSelected as $item) {
        $selectedGroups[$item["groupRoleId"]] = $item["groupId"];
    }

    $tplGroups = TikiLib::lib('user')->get_template_groups_containers();

    $smarty->assign('role_groups', $catRolesList);
    $smarty->assign('selected_groups', $selectedGroups);
    $smarty->assign('templatedGroups', $tplGroups["data"]);
}

$smarty->assign('roles', $roles);
$smarty->assign('group_list', $groupList);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_categories.tpl');
$smarty->display("tiki.tpl");
