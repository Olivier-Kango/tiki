<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

class RatingResult extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $ratinglib = TikiLib::lib('rating');
        $votings = $ratinglib->votings($params['id'], $params['type']);
        $smiles = ($prefs['rating_smileys'] == 'y' ? $ratinglib->get_options_smiles($params['type'], $params['id'], true) : null);
        $tableBody = "";

        if ($prefs['rating_results_detailed'] == 'y') {
            foreach ($votings as $vote => $voting) {
                if ($prefs['rating_results_detailed_percent'] == 'y') {
                    $extra_info = '/' . $voting['percent'] . '%)';
                } else {
                    $extra_info = ')';
                }
                $tableBody .= '<td style="width:' . $voting['percent'] . '%; text-align: center;">
                <div class="ui-widget-content">' .
                    ($prefs['rating_smileys'] == 'y' ? '<img src="' . $smiles[$vote]['img'] . '"/> ' : '<b>' . $vote . '</b> ') .
                    '(' . $voting['votes'] . $extra_info .
                    ($prefs['rating_smileys'] == 'y' ? '<div style="background-color: ' . $smiles[$vote]['color'] . ';">&nbsp;</div>' : '') .
                    '</div>
            </td>';
            }
        } elseif ($votings) {
            include_once('lib/smarty_tiki/function.rating_result_avg.php');
            $smarty = TikiLib::lib('smarty');
            return smarty_function_rating_result_avg($params, $smarty->getEmptyInternalTemplate());
        }

        return "<table class='ratingDeliberationResultTable' style='width:100%;'><tr>" . $tableBody . "</tr></table>";
    }
}
