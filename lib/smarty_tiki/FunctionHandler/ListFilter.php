<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * \brief JQuery Smarty function to filter list of results (by default table)
 *
 * Params
 *
 * @id              id of the input field
 * @size            size of the input field
 * @maxlength       max length of the input field in characters
 * @prefix          prefix text to be put before the input field
 * @selectors       CSS (jQuery) selector(s) for what to filter
 * @exclude         selector(s) for what to exclude from the text filter
 *                      (but still hide when parent is empty)
 * @query           key/field name for presetting filter box value from the URL
 *                      e.g. tiki-admin.php?page=textarea&filter=blog
 *                      (default="textFilter" - set to an empty string to disable)
 *
 * Mainly for treetable lists...
 * @parentSelector  CSS (jQuery) selector(s) for parent nodes of what to filter
 * @childPrefix = 'child-of-'   prefix for child class (to hide parent if all children are hidden by the filter)
 *
 * @return html string (with jQuery added to headerlib)
 */
class ListFilter extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs, $listfilter_id;
        $headerlib = \TikiLib::lib('header');

        if ($prefs['feature_jquery'] != 'y') {
            return '';
        } else {
            extract($params);
            $childPrefix = isset($childPrefix) ? $childPrefix : 'child-of-';
            $exclude = isset($exclude) ? $exclude : '';

            $input = ' <div class="form-horizontal my-2"><div class="tiki-form-group form-row"><div class="col"><div class="input-group"><div class="input-group-text" id="filter_label">';

            if (! isset($prefix)) {
                $input .= smarty_function_icon(['name' => 'search'], $template);
            } else {
                $input .= tra($prefix);
            }
            $input .= '</div><input type="text" class="form-control listfilter"';
            if (! isset($id)) {
                if (isset($listfilter_id)) {
                    $listfilter_id++;
                } else {
                    $listfilter_id = 1;
                }
                $id = "listfilter_$listfilter_id";
                $input .= " id='$id'";
            } else {
                $input .= " id='$id'";
            }
            $input .= 'aria-labelledby="filter_label"';
            if (isset($size)) {
                $input .= " size='$size'";
            }
            if (isset($maxlength)) {
                $input .= " maxlength='$maxlength'";
            }

            // value from url
            if (! isset($query)) {
                $query = 'textFilter';
            }
            if (! empty($query) && ! empty($_REQUEST[$query])) {
                $input .= ' value="' . $_REQUEST[$query] . '"';
            } elseif (! empty($editorId)) {
                $parentTabId = (empty($parentTabId) ? "" : $parentTabId);

                $headerlib->add_jq_onready(
                    "
                $(document).on('editHelpOpened', function() {
                    var text = getTASelection('#" . $editorId . "'),
                    possiblePlugin = text.split(/[ \(}]/)[0];
                    if (possiblePlugin.charAt(0) == '{') { //we have a plugin here
                        possiblePlugin = possiblePlugin.substring(1);
                        $('#$id')
                            .val(possiblePlugin)
                            .trigger('keyup');

                        var parentTabId = '" . $parentTabId . "';
                        if (parentTabId) {
                            $('#help_sections a[href=#$parentTabId]').trigger('click');
                            var pluginTr = $('.card.plugin').not(':hidden');

                            if (pluginTr.length == 1) {
                                pluginTr.find('a').first().trigger('click');
                            }
                        }
                    }
                });
            "
                );
            }

            $input .= ">";
            $input .= "<span class='input-group-text' role='button' area-label='Clear filter' onclick=\"\$('#$id').val('').trigger('focus').trigger('keyup');return false;\" title=':"
            . tr('Clear filter') . "' >" . smarty_function_icon(['name' => 'close'], $template) . "</span>";
            $input .= '</div></div></div></div>';

            if (! isset($selectors)) {
                $selectors = ".$id table tr";
            }

            $content = "
$('#$id').on('keyup', function() {
    var criterias = this.value.toLowerCase().split( /\s+/ );
    $('$selectors').each( function() {
        var text = $(this).text().toLowerCase();
        for( i = 0; criterias.length > i; ++i ) {
            word = criterias[i];
            if ( word.length > 0 && text.indexOf( word ) == -1 ) {
                $(this).not('$exclude').hide();    // don't search within excluded elements
                return;
            }
        }
        $(this).show();
    } );
";
            if (! empty($parentSelector)) {
                $content .= "
    \$('$parentSelector').show().each( function() {
        if (\$('{$selectors}[data-tt-parent-id=' + \$(this).data('tt-id') + ']:visible:not(\"$exclude\")').length == 0) {    // excluded things don't count
            \$(this).hide();
            \$('{$exclude}[data-tt-parent-id=' + \$(this).data('tt-id') + ']').hide();                            // but need hiding if the parent is 'empty'
        } else {
            \$(this).removeClass('collapsed').addClass('expanded');
        }
    });
";
            }
            $content .= '
} );    // end keyup
';
            if (! empty($query) && ! empty($_REQUEST[$query])) {
                $content .= "
setTimeout(function () {
    if ($('#$id').val() != '') {
        $('#$id').trigger('keyup');
    }
}, 1000);
";
            }

            $headerlib->add_jq_onready($content);
            return $input;
        }
    }
}
