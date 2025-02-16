{*param : $msgTrackerFilter, $line, $open, $iTrackerFilter, $trackerId, $filters(array(name, format, fieldId, selected, opts)), $showFieldId *}
<div class="trackerfilter_loader"></div>

{strip}
    {if isset($msgTrackerFilter) && $msgTrackerFilter}
        <div class="alert alert-danger">{$msgTrackerFilter|escape}</div>
    {/if}
    {if (!isset($line) || $line ne 'y') and $noflipflop ne 'y'}
        {button _text="{tr}Filters{/tr}" _flip_id="trackerFilter$iTrackerFilter"}
    {/if}
    <div id="trackerFilter{$iTrackerFilter}" class="trackerfilter" style="display:{if isset($open) && $open eq 'y'}block{else}none{/if}">
        {if empty($inForm)}
            {if empty($export_action)}
                <form action="{$smarty.server.SCRIPT_NAME}?{query}#trackerFilter{$iTrackerFilter}-result" method="post" id="form-filter">
            {else}
                {jq notonready=true}
                    function tf_export_submit(fm) {
                        $("input[name=export_filter]").attr("disabled", "disabled").css("opacity", 0.5);
                        setTimeout(function(){
                            $("input[name=export_filter]").attr("disabled", false).css("opacity", 1);
                        }, 2000);
                        return true;
                    }
                {/jq}
                <form action="tiki-export_tracker.php" method="post" onsubmit="tf_export_submit(this);">
                    {query _type='form_input' listfields=$export_fields showItemId=$export_itemid showStatus=$export_status showCreated=$export_created showLastModif=$export_modif encoding=$export_charset}
                    {if not empty($export_itemId)}<input type="hidden" name="itemId" value="{$export_itemId}">{/if}
                    {foreach from=$f_fields item=f_v key=f_k}
                        <input type="hidden" name="{$f_k}" value="{$f_v}">
                    {/foreach}
            {/if}
        {/if}
        {if isset($mapview) && $mapview}
            <input type="hidden" name="mapview" value="y">
        {else}
            <input type="hidden" name="mapview" value="n">
        {/if}
        <input type="hidden" name="trackerId" value="{$trackerId}">
        <input type="hidden" name="iTrackerFilter" value="{$iTrackerFilter}">
        {if !empty($count_item)}<input type="hidden" name="count_item" value="{$count_item}">{/if}
        <div class="table-responsive">
            <table class="table">
                {if isset($line) && $line eq 'y'}<tr>{/if}

                {$jscal = 0}
                {foreach from=$filters item=filter}
                    {if !isset($line) || $line ne 'y'}<tr>{/if}
                    <td class="tracker_filter_label">
                        {if $indrop ne 'y' or ($filter.format ne 'd' and $filter.format ne 'm')}<label for="f_{$filter.fieldId}">{$filter.name|tr_if}:&nbsp;</label>{/if}
                        {if $showFieldId eq 'y'} -- {$filter.fieldId}{/if}
                        {if !isset($line) || $line ne 'y'}</td><td class="tracker_filter_input tracker_field{$filter.fieldId}">{elseif $indrop ne 'y' or ($filter.format ne 'd' and $filter.format ne 'm')}{/if}
    {*------drop-down, multiple *}
                        {if $filter.format eq 'd' or $filter.format eq 'm'}
                            <select id="f_{$filter.fieldId}" name="f_{$filter.fieldId}{if $filter.format eq "m"}[]{/if}" {if $filter.format eq "m"} size="5" multiple="multiple"{/if} class="form-control">
                                {if $indrop eq 'y'}<option value="">--{$filter.name|tr_if}--</option>{/if}
                                <option value="">{tr}Any{/tr}</option>
                                {$last = ''}
                                {section name=io loop=$filter.opts}
                                    {if $last neq $filter.opts[io].name or $filter.field.type neq 'd'}{* hide repeated entries, used for defaults in other cases *}
                                        <option value="{$filter.opts[io].id}"{if $filter.opts[io].selected eq "y"} selected="selected"{/if}>
                                            {$filter.opts[io].name|tr_if|escape}
                                        </option>
                                    {/if}
                                    {$last = $filter.opts[io].name}
                                {/section}
                            </select>
                            {if $filter.format eq 'm' and $prefs.jquery_select2 neq 'y'}{remarksbox type='tip' title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}{/if}
    {*------<,> operator *}
                        {elseif $filter.format eq '<' or $filter.format eq '>' or $filter.format eq '<=' or $filter.format eq '>=' or $filter.format eq 'f' or $filter.format eq 'j'}
                            {if $filter.field.type eq 'f' or $filter.field.type eq 'j'}
                                {if $filter.format eq '<' or $filter.format eq '<='}
                                    {tr}Before:{/tr}&nbsp;
                                {elseif $filter.format eq '>' or $filter.format eq '>='}
                                    {tr}After:{/tr}&nbsp;
                                {/if}
                            {/if}
                            {trackerinput field=$filter.field inForm="y"}
    {*------range *}
                        {elseif $filter.format eq 'range'}
                            <div class="row">
                                <div class="col-sm-2">{tr}From:{/tr}</div>
                                <div class="col-sm-10">
                                    {trackerinput field=$filter.opts.from inForm="y"}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-2">{tr}To:{/tr}</div>
                                <div class="col-sm-10">
                                    {trackerinput field=$filter.opts.to inForm="y"}
                                </div>
                            </div>
    {*------text *}
                        {elseif $filter.format eq 't' or $filter.format eq 'T' or $filter.format eq 'i'}
                            {if $filter.format eq 'i'}
                                {capture name=i_f}f_{$filter.fieldId}{/capture}
                                {initials_filter_links _initial=$smarty.capture.i_f}
                            {/if}
                            <input id="f_{$filter.fieldId}" type="text" name="f_{$filter.fieldId}" value="{$filter.selected}" class="form-control">
    {*------sqlsearch *}
                        {elseif $filter.format eq 'sqlsearch'}
                            <input id="f_{$filter.fieldId}" type="text" name="f_{$filter.fieldId}" value="{$filter.selected}" class="form-control">
                            <a href="{bootstrap_modal controller=tracker action=search_help}">{icon name='help'}</a>
    {*------rating *}
                        {elseif $filter.format eq '*'}
                            <select id="f_{$filter.fieldId}" name="f_{$filter.fieldId}" class="form-control">
                                <option value="">{tr}Any{/tr}</option>
                                {foreach from=$filter.opts item=option}
                                    <option value="{$option.id|escape}"{if $option.selected eq 'y'} selected="selected"{/if}>{$option.name|escape}</option>
                                {/foreach}
                            </select>
    {*------relation *}
                        {elseif $filter.format eq 'REL'}
                            <textarea name="f_{$filter.fieldId}" class="d-none">
                                {$filter.opts.field_selection}
                            </textarea>
                            {object_selector_multi _id="object_filter_{$filter.fieldId}" _name="object_filter_{$filter.fieldId}" _filter=$filter.opts.field_filter _value=$filter.opts.field_selection _format=$filter.opts.field_format }
                            <div class="text-center mt-3 mb-3">{tr}Or{/tr}</div>
                            <select name="other_filter_{$filter.fieldId}" class="form-control">
                                <option value=''>{tr}-- Choose an option --{/tr}</option>
                                {foreach from=$filter.opts.other_options item=option}
                                    <option value="{$option.id|escape}"{if $option.selected eq 'y'} selected="selected"{/if}>{$option.name|escape}</option>
                                {/foreach}
                            </select>
                            {jq}
                                $('select[name="other_filter_{{$filter.fieldId}}"]').on('change', function() {

                                    var $container = $('#object_filter_{{$filter.fieldId}}').parent();
                                    // Reset Object Selector values
                                    $('textarea[name^="object_filter_{{$filter.fieldId}}"]').val('');
                                    $container.find('.results :checked').each(function(){
                                        $(this).prop('checked', false);
                                    });
                                    $container.find('option:selected').each(function(){
                                        $(this).prop('selected', false);
                                    });
                                    $container.find('select').trigger("change.select2");

                                    var val = $(this).val();
                                    $target = $('[name="f_{{$filter.fieldId}}"]').val(val);
                                });


                                $('textarea[name="object_filter_{{$filter.fieldId}}"]').on('change', function() {
                                    // Reset other values
                                    var $select = $('select[name="other_filter_{{$filter.fieldId}}"]')
                                    $select.val('');
                                    $select.trigger("change.select2");

                                    var val = $(this).val();
                                    $target = $('[name="f_{{$filter.fieldId}}"]').val(val);
                                });
                            {/jq}
    {*------checkbox, radio *}
                        {else}
                            <div class="form-check {if isset($line) && $line eq 'y'}form-check-inline{/if}">
                                <input class="form-check-input"
                                    id="f_{$filter.fieldId}"
                                    type="{if $filter.format eq "c"}checkbox{else}radio{/if}"
                                    name="f_{$filter.fieldId}{if $filter.format eq "c"}[]{/if}"
                                    value="" {if !$filter.selected} checked="checked"{/if}>
                                <label class="form-check-label" for="f_{$filter.fieldId}">{tr}Any{/tr}</label>
                            </div>
                            {section name=io loop=$filter.opts}
                            <div class="form-check {if isset($line) && $line eq 'y'}form-check-inline{/if}">
                                <input class="form-check-input"
                                    id="f_{$filter.fieldId}_{$smarty.section.io.index}"
                                    type="{if $filter.format eq "c"}checkbox{else}radio{/if}"
                                    name="f_{$filter.fieldId}{if $filter.format eq "c"}[]{/if}"
                                    value="{$filter.opts[io].id|regex_replace:"/=.*/":""|escape:url}"
                                    {if $filter.opts[io].selected eq "y"} checked="checked"{/if}>
                                <label class="form-check-label" for="f_{$filter.fieldId}_{$smarty.section.io.index}">{$filter.opts[io].name|regex_replace:"/^[^=]*=/":""|tr_if}</label>
                            </div>
                            {/section}
                        {/if}
                    </td>
                    {if !isset($line) || $line ne 'y'}</tr>{else} {/if}
                    {if $filter.format eq 'j'}{$jscal = 1}{/if}
                {/foreach}
                {if (!isset($line) || $line ne 'y') and (!isset($action) || $action neq " ")}<tr>{/if}
                {if (!isset($action) || $action neq " ") or !empty($export_action)}
                    <td>&nbsp;</td>
                    <td>
                        <div id="trackerFilter{$iTrackerFilter}-result"></div>
                        {if !empty($export_action)}
                            <input class="button submit btn btn-primary" type="submit" name="export_filter" value="{tr}{$export_action}{/tr}">
                        {elseif $action and $action neq " "}
                            <input class="button submit btn btn-primary me-1 mb-1" type="submit" name="filter" value="{if empty($action)}{tr}Filter{/tr}{else}{tr}{$action}{/tr}{/if}">
                            <input class="button submit btn btn-primary me-1 mb-1" type="submit" name="reset_filter" value="{tr}Reset{/tr}">
                        {else}
                            &nbsp;
                        {/if}
                        {if $mapButtons && $mapButtons eq 'y'}
                            {if isset($mapview) && $mapview}
                            <br><input class="button submit btn btn-primary" type="submit" name="searchlist" value="{tr}List View{/tr}">
                            {else}
                            <br><input class="button submit btn btn-primary" type="submit" name="searchmap" value="{tr}Map View{/tr}">
                            {/if}
                        {/if}
                    </td>
                {/if}
                {if !empty($sortchoice)}
                    {if $line ne 'y'}<tr>{/if}
                    <td>{tr}Sort{/tr}</td>
                    <td>{include file='tracker_sort_input.tpl' iTRACKERLIST=$iTrackerFilter}
                    {if !isset($line) || $line ne 'y'}</tr>{/if}
                {/if}
                {if (!isset($line) || $line ne 'y' ) and $action}</tr>{/if}
            </table>
        </div>
        {if empty($inForm)}</form>{/if}
    </div>
    {if !empty($dataRes)}<div class="trackerfilter-result">{$dataRes}</div>{/if}
{/strip}
{if $jscal}
    {js_insert_icon type="jscalendar"}
{/if}
