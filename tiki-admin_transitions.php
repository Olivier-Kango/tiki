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
        'staticKeyFilters'          => [
            'from'              => 'bool',           //post
            'to'                  => 'bool',         //post
            'label'                => 'string',      //post
            'preserve'             => 'bool',        //post
            'transition_mode'            => 'bool'  //post
          ],
        'staticKeyFiltersForArrays' => [
            'cat_categories'        => 'word',       //post
        ],
    ],
];
require_once 'tiki-setup.php';
$categlib = TikiLib::lib('categ');
require_once 'lib/transitionlib.php';

$auto_query_args = [];

$access->check_permission('tiki_p_admin');

// Init
if (isset($_SESSION['transition'])) {
    $transition_mode = $_SESSION['transition']['mode'];
    $available_states = $_SESSION['transition']['states'];
} else {
    $transition_mode = 'category';
    $available_states = [];
}

$selected_transition = null;
$to_add = [];

// Action handling

switch ($jitRequest->action->alpha()) {
    case 'subset':
        if (isset($_REQUEST['transition_mode'])) {
            $transition_mode = $_REQUEST['transition_mode'];
        }

        if ($transition_mode == 'category') {
            $jitPost->replaceFilter('cat_categories', 'int');
            if ($selection = $jitPost->asArray('cat_categories')) {
                $available_states = array_combine(
                    $selection,
                    array_map([ $categlib, 'get_category_name' ], $selection)
                );
            } else {
                $available_states = [];
            }
        } else {
            $jitPost->replaceFilter('groups', 'groupname');
            if ($selection = $jitPost->asArray('groups')) {
                $available_states = array_combine($selection, $selection);
            } else {
                $available_states = [];
            }
        }
        break;
    case 'new':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $transitionlib = new TransitionLib($transition_mode);
            $transitionlib->addTransition(
                $_REQUEST['from'],
                $_REQUEST['to'],
                $_REQUEST['label'],
                isset($_REQUEST['preserve'])
            );
        }
        break;
    case 'edit':
        $transitionlib = new TransitionLib($transition_mode);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $transitionlib->updateTransition(
                $_REQUEST['transitionId'],
                $_REQUEST['from'],
                $_REQUEST['to'],
                $_REQUEST['label'],
                isset($_REQUEST['preserve'])
            );
        } else {
            $selected_transition = $transitionlib->getTransition((int) $_REQUEST['transitionId']);
        }
        break;
    case 'addguard':
        $transitionlib = new TransitionLib($transition_mode);
        $selected_transition = $transitionlib->getTransition((int) $_REQUEST['transitionId']);

        if ($selection = $jitPost->asArray('states')) {
            $selected_transition['guards'][] = [
                        $_REQUEST['type'],
                        (int) $_REQUEST['count'],
                        $selection,
            ];
            $transitionlib->updateGuards((int) $selected_transition['transitionId'], $selected_transition['guards']);
        }
        break;
    case 'removeguard':
        $transitionlib = new TransitionLib($transition_mode);
        $selected_transition = $transitionlib->getTransition((int) $_REQUEST['transitionId']);

        unset($selected_transition['guards'][ (int) $_REQUEST['guard'] ]);
        $selected_transition['guards'] = array_values($selected_transition['guards']);
        $transitionlib->updateGuards((int) $selected_transition['transitionId'], $selected_transition['guards']);
        break;
    case 'remove':
        $transitionlib = new TransitionLib($transition_mode);
        $access->checkCsrf();

        $transitionlib->removeTransition($_REQUEST['transitionId']);
        break;
}

// Obtain data
$categories = $categlib->getCategories();
$cat_tree = $categlib->generate_cat_tree($categories, true, array_keys($available_states));

$transitionlib = new TransitionLib($transition_mode);
$transitions = $transitionlib->listTransitions(array_keys($available_states));

if ($selected_transition) {
    // When a transition is selected, make sure all of its endpoints are listed in the edit panel
    $to_add = [ $selected_transition['from'], $selected_transition['to'] ];

    foreach ($selected_transition['guards'] as $guard) {
        $to_add = array_merge($to_add, $guard[2]);
    }
}

foreach ($transitions as & $trans) {
    $trans['from_label'] = transition_label_finder($trans['from']);
    $trans['to_label'] = transition_label_finder($trans['to']);
}

// Setup Smarty & Session
$_SESSION['transition'] = [
                'mode' => $transition_mode,
                'states' => $available_states,
];

foreach ($to_add as $v) {
    $available_states[ $v ] = transition_label_finder($v);
}

$guards = [];
if ($selected_transition) {
    foreach ($selected_transition['guards'] as $guard) {
        $guards[] = [
                        'type' => $guard[0],
                        'count' => $guard[1],
                        'members' => array_map('transition_label_finder', $guard[2]),
        ];
    }
}

$smarty->assign('transition_mode', $transition_mode);
$smarty->assign('available_states', $available_states);
$smarty->assign('transitions', $transitions);
$smarty->assign('guards', $guards);
$smarty->assign('selected_transition', $selected_transition);

$smarty->assign('cat_tree', $cat_tree);

// Graph setup
if (count($available_states) > 0) {
    $edges = [];
    foreach ($transitions as $tr) {
        $edges[] = [
                        'from' => $tr['from_label'],
                        'to' => $tr['to_label'],
                        'label' => $tr['name'],
                        'preserve' => (bool) $tr['preserve']
        ];
    }

    $smarty->assign('graph_nodes', json_encode(array_values($available_states)));
    $smarty->assign('graph_edges', json_encode($edges));
}

$smarty->assign('mid', 'tiki-admin_transitions.tpl');
$smarty->display('tiki.tpl');

/**
 * @param $state
 * @return mixed|string
 */
function transition_label_finder($state)
{
    global $available_states, $transition_mode;
    $categlib = TikiLib::lib('categ');

    if (isset($available_states[$state])) {
        return $available_states[$state];
    } elseif ($transition_mode == 'category') {
        return $categlib->get_category_name($state);
    } else {
        return $state;
    }
}
