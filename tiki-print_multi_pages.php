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
        'staticKeyFilters'         => [
        'printpages'               => 'string',             //get
        'printstructures'          => 'string',             //get
        'print'                    => 'int',                //get
        'display'                  => 'string',             //get
        ],
    ],
];
require_once('tiki-setup.php');

$structlib = TikiLib::lib('struct');

$access->check_feature('feature_wiki_multiprint');

if (! isset($_REQUEST['printpages']) && ! isset($_REQUEST['printstructures'])) {
    $smarty->assign('msg', tra("No pages indicated"));
    $smarty->display("error.tpl");
    die;
} else {
    if (isset($_REQUEST['printpages'])) {
        $printpages = json_decode(urldecode($_REQUEST['printpages']));
    } else {
        $printpages = [];
    }
    if (isset($_REQUEST['printstructures'])) {
        $printstructures = json_decode(urldecode($_REQUEST['printstructures']));
    } else {
        $printstructures = [];
    }
}
if (isset($_REQUEST["print"]) || isset($_REQUEST["display"])) {
    $access->checkCsrf();
    // Create XMLRPC object
    $pages = [];
    $prefs['feature_jquery_tablesorter'] = 'n';
    foreach ($printpages as $page) {
        // If the page doesn't exist then display an error
        if (! $tikilib->page_exists($page)) {
            $smarty->assign('msg', tra("Page cannot be found"));
            $smarty->display("error.tpl");
            die;
        }
        // Now check permissions to access this page
        if (! $tikilib->user_has_perm_on_object($user, $page, 'wiki page', 'tiki_p_view')) {
            $smarty->assign('errortype', 401);
            $smarty->assign('msg', tra("You do not have permission to view this page."));
            $smarty->display("error.tpl");
            die;
        }
        // check if user can print or export pdf
        if (isset($_REQUEST["display"]) && $_REQUEST["display"] == 'pdf') {
            if (! $tikilib->user_has_perm_on_object($user, $page, 'wiki page', 'tiki_p_export_pdf')) {
                $smarty->assign('errortype', 401);
                $smarty->assign('msg', tra("You do not have permission to export to pdf this page."));
                $smarty->display("error.tpl");
                die;
            }
        } else {
            if (! $tikilib->user_has_perm_on_object($user, $page, 'wiki page', 'tiki_p_print')) {
                $smarty->assign('errortype', 401);
                $smarty->assign('msg', tra("You do not have permission to view the print version of this page."));
                $smarty->display("error.tpl");
                die;
            }
        }
        $pages[] = $tikilib->get_page_print_info($page);
    }
    foreach ($printstructures as $structureId) {
        $struct = $structlib->get_subtree($structureId);
        foreach ($struct as $struct_page) {
            global $page_ref_id;
            $page_ref_id = $struct_page['page_ref_id']; //to interpret {toc}
            if ($struct_page['pos'] != '' && $struct_page['last'] == 1) {
                continue;
            }
            $page_info = $tikilib->get_page_print_info($struct_page['pageName']);

            // Use the alias as the display name, if an alias is defined
            if (isset($struct_page['page_alias'])) {
                $page_info['pageName'] = $struct_page['page_alias'];
            }

            // Get the page_id from the page_ref_id
            try {
                $page_info['id'] = $tikilib->getOne("select `page_id` from `tiki_structures` where `page_ref_id` = ?", [$page_ref_id]);
            } catch (Exception $e) {
                $page_info['id'] = -1;
            }

            $page_info['pos'] = $struct_page['pos'];
            $page_info['h'] = empty($struct_page['pos']) ? 0 : count(explode('.', $struct_page['pos']));
            $h = $page_info['h'] + 5;
            if ($prefs['feature_page_title'] == 'y') {
                ++$h;
            }
            $page_info['parsed'] = preg_replace("/<(\/?)h6/i", "<\\1h$h", $page_info['parsed']);
            --$h;
            $page_info['parsed'] = preg_replace("/<(\/?)h5/i", "<\\1h$h", $page_info['parsed']);
            --$h;
            $page_info['parsed'] = preg_replace("/<(\/?)h4/i", "<\\1h$h", $page_info['parsed']);
            --$h;
            $page_info['parsed'] = preg_replace("/<(\/?)h3/i", "<\\1h$h", $page_info['parsed']);
            --$h;
            $page_info['parsed'] = preg_replace("/<(\/?)h2/i", "<\\1h$h", $page_info['parsed']);
            --$h;
            $page_info['parsed'] = preg_replace("/<(\/?)h1/i", "<\\1h$h", $page_info['parsed']);
            --$h;
            $pages[] = $page_info;
        }
    }
}

$smarty->assign_by_ref('pages', $pages);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('print_page', 'y');
if (isset($_REQUEST['display'])) {
    $smarty->assign('display', $_REQUEST['display']);
}
// Allow PDF export by installing a Mod that define an appropriate function
if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'pdf') {
    require_once 'lib/pdflib.php';
    $generator = new PdfGenerator();
    $pdf = $pdfname = '';

    if (! empty($printpages)) {
        $pdf = $generator->getPdf('tiki-print_multi_pages.php', ['print' => 'print', 'printpages' => $printpages, 'pages' => $pages]);
        $pdfname = implode(', ', $printpages);
    } elseif (! empty($printstructures)) {
        $pdf = $generator->getPdf('tiki-print_multi_pages.php', ['print' => 'print', 'printstructures' => $printstructures, 'pages' => $pages ]);
        $pdfname = implode(', ', $printstructures);
    } else {
        $smarty->display("tiki-print_multi_pages.tpl");
        die;
    }

    if (empty($generator->error)) {
        header('Cache-Control: private, must-revalidate');
        header('Pragma: private');
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename="' . $pdfname . '.pdf"');
        header("Content-Type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    } else {
        Feedback::error($generator->error);
        $tab = '';
        if (strpos($_SERVER['HTTP_REFERER'], 'tiki-print_pages.php') !== false && ! empty($printpages)) {
            $tab = '#contenttabs_print_pages-2';
        }
        $access->redirect($_SERVER['HTTP_REFERER'] . $tab);
    }
} else {
    $smarty->display("tiki-print_multi_pages.tpl");
}
