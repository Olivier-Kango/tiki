<form action="#" method="get" class="d-flex flex-row flex-wrap align-items-center">
    <input type="hidden" name="trackerId" value="{$trackerId|escape}">
    {if $status}<input type="hidden" name="status" value="{$status}">{/if}
    {if $sort_mode}<input type="hidden" name="sort_mode" value="{$sort_mode}">{/if}
    <div class="search_container mb-3">
        {if ($tracker_info.showStatus|default:null eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')) and $showstatus|default:null ne 'n'}
            {foreach key=st item=stdata from=$status_types}
                <div style="display:inline-block;">
                    <div class="{$stdata.class}">
                        {if $prefs.feature_sefurl === 'y'}{$sep = '?'}{else}{$sep = '&amp;'}{/if}
                        <a href="{$trackerId|sefurl:tracker}{$sep}status={$stdata.statuslink}{if $filtervalue and !$filtervalue|is_array}&amp;filtervalue={$filtervalue|escape:"url"}{/if}{if $filtervalue|is_array}{$filtervalueencoded}{/if}{if $filterfield}&amp;filterfield={$filterfield|escape:"url"}{/if}{if $sort_mode}&amp;sort_mode={$sort_mode}{/if}">
                            {icon name="{$stdata.iconname}" ititle=":{tr}Toggle{/tr} {$stdata.label}" iclass='tips'}
                        </a>
                    </div>
                </div>
            {/foreach}
        {/if}

        <div style="display:inline-block;padding: 4px 10px;vertical-align:middle">
            {if $show_filters eq 'y'}
                {jq}
                    fields = [];
                    {{assign var=c value=0}}
                    {{foreach key=fid item=field from=$listfields}
                        {if $field.isSearchable eq 'y' and $field.type ne 'f' and $field.type ne 'j' and $field.type ne 'i'}
                            fields[{$c}] = '{$fid}';
                            {assign var=c value=$c+1}
                        {/if}
                    {/foreach}}
                {/jq}
                <select name="filterfield" class="form-select" data-placeholder="{tr}Choose a filter{/tr}" onchange="this.form.submit(); {literal}showit = 'show_filterbutton'; if(this.selectedIndex == 0){document.getElementById('filterbutton').style.display = 'none';setSessionVar(showit,'n');}else{ document.getElementById('filterbutton').style.display = 'block'; setSessionVar(showit,'y');}{/literal}">
                    <option value="">{tr}Choose a filter{/tr}</option>
                    {foreach key=fid item=field from=$listfields}
                        {if $field.isSearchable eq 'y' and $field.type ne 'f' and $field.type ne 'j' and $field.type ne 'i' and ($field.isHidden ne 'y' or $tiki_p_admin_trackers eq 'y')}
                            <option value="{$fid}"{if $fid eq $filterfield} selected="selected"{/if}>{tr}{$field.name|truncate:65|escape}{/tr}</option>
                            {assign var=filter_button value='y'}
                        {/if}
                    {/foreach}
                </select>
            {/if}
        </div>
        <div style="display:inline-block" class="mb-3 row">
            {assign var=cnt value=0}
            {foreach key=fid item=field from=$listfields}
                {if $field.isSearchable eq 'y' and $field.type ne 'f' and $field.type ne 'j' and $field.type ne 'i'}
                    {if $field.type eq 'c'}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            <select name="filtervalue[{$fid}]" class="form-select">
                                <option value="y"{if $filtervalue eq 'y'} selected="selected"{/if}>{tr}Yes{/tr}</option>
                                <option value="n"{if $filtervalue eq 'n'} selected="selected"{/if}>{tr}No{/tr}</option>
                            </select>
                        </div>
                    {elseif $field.type eq 'd' or $field.type eq 'D'}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            <select name="filtervalue[{$fid}]" class="form-select">
                                {if $field.type eq 'D'}<option value="" />{/if}
                                {foreach from=$field.possibilities key=dropdown_key item=dropdown_value}
                                    <option value="{$dropdown_key|escape}" {if $fid == $filterfield}{if $filtervalue eq $dropdown_key}{assign var=gotit value=y}selected="selected"{/if}{/if}>{$dropdown_value|tr_if}</option>
                                {/foreach}
                            </select>
                            {if $field.type eq 'D'}
                                <input class="form-control" type="text" name="filtervalue_other"{if $gotit ne 'y'} value="{if $fid == $filterfield}{$filtervalue}{/if}"{/if}>
                            {/if}
                        </div>

                    {elseif $field.type eq 'R'}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            {foreach from=$field.possibilities key=radio_key item=radio_value}
                                <input type="radio" name="filtervalue[{$fid}]" value="{$radio_key|escape}" {if $fid == $filterfield}{if $filtervalue eq $radio_key}checked="checked"{/if}{/if}>{$radio_value|escape}
                            {/foreach}
                        </div>

                    {elseif $field.type eq 'M'}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            {if empty($field.options_map.inputtype)}
                                {foreach from=$field.possibilities key=value item=label}
                                    <label>
                                        <input type="checkbox" class="form-check-input" name="filtervalue[{$fid}][]" value="{$value|escape}" {if $fid == $filterfield and is_array($filtervalue) and in_array($value, $filtervalue)}checked="checked"{/if}>
                                        {$label|tr_if|escape}
                                    </label>
                                {/foreach}
                            {elseif $field.options_map.inputtype eq 'm'}
                                {if $prefs.jquery_select2 neq 'y'}<small>{tr}Hold "Ctrl" in order to select multiple values{/tr}</small><br>{/if}
                                <select name="filtervalue[{$fid}][]" multiple="multiple" class="form-select">
                                    {foreach key=ku from=$field.possibilities key=value item=label}
                                        <option value="{$value|escape}" {if is_array($filtervalue) and in_array($value, $filtervalue)}selected="selected"{/if}>{$label|escape}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        </div>

                    {elseif $field.type eq 'e'}{* category *}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}" class="card-body">
                            {if count($field.list) gt $prefs.maxRecords}
                                <select name="filtervalue[{$fid}][]" class="form-control" multiple>
                                    {foreach key=ku item=iu from=$field.list name=eforeach}
                                        <option value="{$iu.categId}"{if $fid == $filterfield && is_array($filtervalue) && in_array($iu.categId,$filtervalue)} selected="selected"{/if}>
                                            {$iu.categpath|escape}
                                        </option>
                                    {/foreach}
                                </select>
                            {else}
                                <ul class="list-unstyled">
                                    {foreach key=ku item=iu from=$field.list name=eforeach}
                                        <li class="form-check justify-content-start">
                                            <input type="checkbox" class="form-check-input" name="filtervalue[{$fid}][]" value="{$iu.categId}" id="cat{$iu.categId}"
                                                {if $fid == $filterfield && is_array($filtervalue) && in_array($iu.categId,$filtervalue)} checked="checked"{/if}>
                                            <label for="cat{$iu.categId}" class="form-check-label">{$iu.categpath|escape}</label>
                                        </li>
                                    {/foreach}
                                </ul>
                            {/if}
                        </div>
                    {elseif $field.type eq 'u'}{* user with autocomplete *}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            <input type="text" class="form-control" name="filtervalue[{$fid}]" value="{if $fid == $filterfield}{$filtervalue}{/if}" id="filter-username">
                        </div>
                        {autocomplete element='#filter-username' type='username'}
                    {else}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            <input type="text" class="form-control" name="filtervalue[{$fid}]" value="{if $fid == $filterfield}{$filtervalue}{/if}">
                        </div>
                    {/if}
                    {assign var=cnt value=$cnt+1}
                {/if}
            {/foreach}
        </div>
        {if isset($filter_button) && $filter_button eq 'y'}
            <div style="display:inline-block" class="mb-3 row">
                <input id="filterbutton" type="submit" class="btn btn-primary" name="filter" value="{tr}Filter{/tr}" style="display:{if $filterfield}inline{else}none{/if}">
            </div>
        {/if}
    </div>
</form>
