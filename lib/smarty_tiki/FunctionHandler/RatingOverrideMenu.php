<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

class RatingOverrideMenu extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $headerlib = TikiLib::lib('header');
        $ratinglib = TikiLib::lib('rating');

        $menu = '';
        $options = $ratinglib->override_array($params['type'], true, true);
        $optionsLength = count($options);
        $backgroundsSets = $ratinglib->get_options_smiles_backgrounds($params['type']);

        foreach ($options as $i => $option) {
            if ($prefs['rating_smileys'] == 'y') {
                $images = '';

                foreach ($backgroundsSets[$i] as $i => $background) {
                    $images .= '<img src="' . $background . '"/>';
                }

                $html = count($option) . ' ' . $images;
            } else {
                $html = implode(',', $option);
            }

            $menu .= "<div class='deliberationConfigureItemRating ui-widget ui-state-default ui-corner-all ui-button-text-only' data-val='$i'>" . $html . "</div>";
        }

        //<select class='rating_override_selector' style='width: 250px;' name='rating_override[]'>" . $menu . "</select>";

        return
            "<div class='deliberationItemRatings' style='display:none;'>$menu</div>
        <span class='deliberationConfigureItemRatings button' style='float:right;'><a href='#'>" . tr('Configure Ratings') . "</a></span>
        <input class='deliberatioRatingOverrideSelector' type='hidden' name='rating_override[]' value='" . ($optionsLength) . "'/>";
    }
}
