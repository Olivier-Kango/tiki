<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class RatingResultAvg extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $ratinglib = \TikiLib::lib('rating');
        $votings = $ratinglib->votings($params['id'], $params['type']);
        $options = $ratinglib->get_options($params['type'], $params['id']);

        $vote_sum = 0;
        $vote_count_total = 0;
        // if there are no votes yet, don't show zero to avoid confusion with users voting 0 for the article; show dash instead ('-')
        $vote_avg = '-';

        if (count($votings)) {
            foreach ($votings as $vote => $voting) {
                if ($vote != 0) {
                    $vote_sum += $vote * $voting['votes'];
                } else {
                    continue;
                }
                $vote_count_total += $voting['votes'];
            }

            if ($vote_count_total != 0) {
                $vote_avg = number_format($vote_sum / $vote_count_total, 1);
                // if the average has zero as decimal, do not show the decimal.
                if (floor($vote_avg) == $vote_avg) {
                    $vote_avg = $vote_sum / $vote_count_total;
                }
            }
        }
        //Why $vote_collect yields a different value than $vote_avg?
        //$vote_collect = $ratinglib->collect($params['type'], $params['id'], 'avg', array_filter($votings));

        if (isset($options[0]) && $options[0] == "0") {
            unset($options[0]);
        }

        if ($vote_avg != 0) {
            return "<span class='score'>" . $vote_avg . " / " . max($options) . "</span>";
        } else {
            return "<span class='score'>" . "-" . " / " . max($options) . "</span>";
        }
    }
}
