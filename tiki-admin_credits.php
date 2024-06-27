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
        'staticKeyFilters'    => [
            'userfilter'      => 'striptags',        //post
            'new_credit_type' => 'striptags',        //post
            'display_text'    => 'striptags',        //post
            'unit_text'       => 'striptags',        //post
            'scaling_divisor' => 'striptags',        //post
            'save' => 'bool',                        //post
            'purge_credits'   => 'bool'              //post
        ],
    ],
];
require_once 'tiki-setup.php';
require_once('admin/include_credits.php');
$creditslib = TikiLib::lib('credits');
//get_strings tra('Admin credits')

if ($tiki_p_admin_users != 'y') {
    $smarty->assign('msg', tra('You do not have the permission that is needed to use this feature'));
    $smarty->display('error.tpl');
    die;
}

if (isset($_REQUEST['use_credit']) && $use_credit_userid = $tikilib->get_user_id($_POST['userfilter'])) {
    $creditslib->useCredits(
        $use_credit_userid,
        $_POST['use_credit_type'],
        $_POST['use_credit_amount']
    );

    header('Location: tiki-admin_credits.php?userfilter=' . urlencode($_REQUEST['userfilter']));
    exit;
}

if (isset($_REQUEST['restore_credit']) && $restore_credit_userid = $tikilib->get_user_id($_POST['userfilter'])) {
    $creditslib->restoreCredits(
        $restore_credit_userid,
        $_POST['restore_credit_type'],
        $_POST['restore_credit_amount']
    );

    header('Location: tiki-admin_credits.php?userfilter=' . urlencode($_REQUEST['userfilter']));
    exit;
}

if (isset($_REQUEST['purge_credits'])) {
    $creditslib->purgeCredits();
    header('Location: tiki-admin_credits.php');
    exit;
}

if (isset($_REQUEST['update_types'])) {
    foreach ($_POST['credit_types'] as $key => $values) {
        $creditslib->updateCreditType(
            $values['credit_type'],
            $values['display_text'],
            $values['unit_text'],
            $values['is_static_level'],
            $values['scaling_divisor']
        );
    }

    if (! empty($_POST['new_credit_type'])) {
        $creditslib->updateCreditType(
            $_POST['new_credit_type'],
            $_POST['display_text'],
            $_POST['unit_text'],
            $_POST['is_static_level'],
            $_POST['scaling_divisor']
        );
    }
}

list($creditTypes, $staticCreditTypes) = creditTypes();

if (isset($_REQUEST['userfilter'])) {
    $smarty->assign('userfilter', $_REQUEST['userfilter']);

    $editing = $userlib->get_user_info($_REQUEST['userfilter']);

    if ($editing) {
        $credits = userPlansAndCredits();

        // date values
        list($start_date, $end_date) = getStartDateFromRequest();

        $req_type = $_REQUEST['action_type'];
        $smarty->assign('act_type', $req_type);

        consumptionData();

        if (isset($_POST['save'], $_POST['credits'])) {
            foreach ($_POST['credits'] as $key => $values) {
                if (! isset($credits[$key])) {
                    die('Mismatch');
                }

                $same = true;
                $current = $credits[$key];
                foreach ($current as $field => $value) {
                    if ($field != 'creditId' && $value != $values[$field]) {
                        $same = false;
                        break;
                    }
                }

                if (! $same) {
                    $creditslib->replaceCredit(
                        $key,
                        $values['credit_type'],
                        $values['used_amount'],
                        $values['total_amount'],
                        $values['creation_date'],
                        $values['expiration_date']
                    );
                }
            }

            if (
                ! empty($_POST['credit_type'])
                        && ! empty($_POST['total_amount'])
                        && in_array($_POST['credit_type'], array_keys($creditTypes))
            ) {
                $creditslib->addCredits(
                    $editing['userId'],
                    $_POST['credit_type'],
                    $_POST['total_amount'],
                    $_POST['expiration_date'],
                    $_POST['creation_date']
                );
            }

            header('Location: tiki-admin_credits.php?userfilter=' . urlencode($_REQUEST['userfilter']));
            exit;
        }

        if (! empty($_POST['credit_type']) && ! empty($_POST['total_amount'])) {
            $creditslib->addCredits(
                $editing['userId'],
                $_POST['credit_type'],
                $_POST['total_amount'],
                $_POST['expiration_date'],
                $_POST['creation_date']
            );

            header('Location: tiki-admin_credits.php?userfilter=' . urlencode($_REQUEST['userfilter']));
            exit;
        }

        if (isset($_POST['confirm'], $_POST['delete'])) {
            foreach ($_POST['delete'] as $creditId) {
                if (isset($credits[$creditId])) {
                    $creditslib->removeCreditBlock($creditId);
                }
            }

            header('Location: tiki-admin_credits.php?userfilter=' . urlencode($_REQUEST['userfilter']));
            exit;
        }
    }
}

$smarty->assign('mid', 'tiki-admin_credits.tpl');
$smarty->display('tiki.tpl');
