{* this script (un/)checks all checkboxes with id 'banning-section' *}
{jq notonready=true}
    function CheckAll() {
        for (var i = 0; i < document.banningform.elements.length; i++) {
            var e = document.banningform.elements[i];
            if (e.type == 'checkbox' && e.id == 'banning-section' && e.name != 'checkall') {
                e.checked = document.banningform.checkall.checked;
            }
        }
    }
{/jq}

{* this script (un/)checks all checkboxes with id 'multi-banning-section' *}
{jq notonready=true}
    function CheckMultiIP() {
        for (var i = 0; i < document.banningform.elements.length; i++) {
            var e = document.banningform.elements[i];
            if (e.type == 'checkbox' && e.id == 'multi-banning-section' && e.name != 'checkall') {
                e.checked = document.banningform.checkmultiip.checked;
            }
        }
    }
{/jq}

{title help="Banning"}{tr}Banning system{/tr}{/title}

<h2 id="Import_rules_as_CSV">{tr}Banning constraint rule{/tr}</h2>
<div class="mb-3 row">
    <label class="col-sm-4 col-form-label" for="csv">{tr}Attempts number{/tr}</label>
    <label class="col-sm-8">{$prefs.feature_banning_attempts} {tr}times{/tr}</label>
</div>
<div class="mb-3 row">
    <label class="col-sm-4 col-form-label" for="csv">{tr}Banning period{/tr}</label>
    <label class="col-sm-8">{$prefs.feature_banning_duration} {tr}minutes{/tr}</label>
</div>

<div class="t_navbar">
    <form action="tiki-admin_banning.php" method="post">
        {ticket}
        <input type="hidden" name="export" value="y">
        <button name="csv" type="submit" class="btn btn-primary">
            {icon name="export"} {tr}Export as CSV{/tr}
        </button>
        {button _text="{tr}Import as CSV{/tr}" _icon_name="import" href="#Import_rules_as_CSV" class="btn btn-primary"}
    </form>
</div>

{if $updated}
    {remarksbox type="note" title="{tr}Note:{/tr}"}
        <strong>{tr}Banning rules have been updated{/tr}</strong>
    {/remarksbox}
{/if}

<h2>{tr}Add or edit rules{/tr}</h2>
<form action="tiki-admin_banning.php" name="banningform" method="post">
    {ticket}
    <input type="hidden" name="banId" value="{$banId|escape}">
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="banning-title">{tr}Rule title{/tr}</label>
        <div class="col-sm-8">
            <input type="text" name="title" id="banning-title" value="{$info.title|escape}" maxlength="200" class="form-control">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="banning-userregex">
            {if $prefs.feature_banning_email eq 'y'}
                {tr}Username or email regex matching{/tr}
            {else}
                {tr}Username regex matching{/tr}
            {/if}
        </label>
        <div class="col-sm-1">
            <input type="radio" name="mode" value="user" {if $info.mode eq 'user'}checked="checked"{/if}>
        </div>
        <div class="col-sm-7">
            <input type="text" name="userreg" id="banning-userregex" value="{$info.user|escape}" onfocus="$('input[name=mode]').val(['user']);" class="form-control">
        </div>
    </div>
    {if isset($mass_ban_ip)}
        <div class="mb-3 row">
            <label class="col-sm-4 col-form-label" for="banning-ipregex">{tr}Multiple IP regex matching{/tr}</label>
            <div class="col-sm-8 alert-warning">
                <input type="radio" name="mode" value="mass_ban_ip" {if $info.mode eq 'mass_ban_ip'}checked="checked"{/if}>
                <br>
                <input type="checkbox" name="checkmultiip" checked="checked" onclick="CheckMultiIP();">
                <label for="sectionswitch">{tr}Check / Uncheck All{/tr}</label><br>
                {foreach key=ip item=comment from=$ban_comments_list}
                    {foreach key=id item=user from=$comment}
                        <input type="checkbox" name="multi_banned_ip[{$ip|escape}]" id="multi-banning-section" checked="checked">
                        <label>{$user.userName|escape}</label>
                        <label for="multi-banning-section">{$ip|escape}</label>
                    {/foreach}
                {/foreach}
            </div>
        </div>
    {else}
        <div class="mb-3 row">
            <label class="col-sm-4 col-form-label" for="banning-ipregex">{tr}IP regex matching{/tr}</label>
            <div class="col-sm-1">
                <input type="radio" name="mode" value="ip" {if $info.mode eq 'ip'}checked="checked"{/if}>
            </div>
            <div class="col-sm-5">
                <div class="d-flex flex-row">
                    <div>
                        <input type="text" name="ip1" id="banning-ipregex" value="{$info.ip1|escape}" onfocus="$('input[name=mode]').val(['ip']);" class="form-control">
                    </div>
                    <div class="px-1">.</div>
                    <div>
                        <input type="text" name="ip2" value="{$info.ip2|escape}" class="form-control">
                    </div>
                    <div class="px-1">.</div>
                    <div>
                        <input type="text" name="ip3" value="{$info.ip3|escape}" class="form-control">
                    </div>
                    <div class="px-1">.</div>
                    <div>
                        <input type="text" name="ip4" value="{$info.ip4|escape}" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    {/if}
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="banning-section">{tr}Banned from sections{/tr}</label>
        <div class="col-sm-8">
            <input type="checkbox" name="checkall" {if (!$banId)}checked="checked"{/if} onclick="CheckAll();">
            <label for="sectionswitch">{tr}Check / Uncheck All{/tr}</label><br>
            {foreach key=sec name=ix item=it from=$sections}
                <label class="col-form-label" for="banning-section"><input type="checkbox" name="section[{$sec}]" id="banning-section" {if ((!$banId) || in_array($sec,$info.sections))}checked="checked"{/if}>
                    {tr}{$sec}{/tr}
                </label>
                {if $smarty.foreach.ix.index mod 2}
                {/if}
            {/foreach}
        </div>
        <div class="col-sm-8">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="banning-actdates">{tr}Rule activated by dates{/tr}</label>
        <div class="col-sm-8">
            <input type="checkbox" name="use_dates" id="banning-actdates" {if $info.use_dates eq 'y'}checked="checked"{/if}
                   onclick="if ($(this).is(':checked')) { $('.usedates').show(); } else { $('.usedates').hide();}">
        </div>
    </div>
    <div class="mb-3 row usedates" style="display: {if $info.use_dates eq 'y'}block{else}none{/if};" >
        <label class="col-sm-4 col-form-label" for="">{tr}Rule active from{/tr}</label>
        <div class="col-sm-8">
            {html_select_date prefix="date_from" time=$info.date_from field_order=$prefs.display_field_order}
        </div>
    </div>
    <div class="mb-3 row usedates" style="display: {if $info.use_dates eq 'y'}block{else}none{/if};">
        <label class="col-sm-4 col-form-label" for="">{tr}Rule active until{/tr}</label>
        <div class="col-sm-8">
            {html_select_date prefix="date_to" time=$info.date_to end_year="+10" field_order=$prefs.display_field_order}
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="banning-mess">{tr}Custom message to the user{/tr}</label>
        <div class="col-sm-8">
                <textarea rows="4" class="form-control" name="message">{$info.message|escape}</textarea>
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-8 offset-sm-4">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
        </div>
    </div>
</form>

<h2 id="Import_rules_as_CSV">{tr}Import rules as CSV{/tr}</h2>

<form method="post" action="tiki-admin_banning.php" enctype="multipart/form-data">
    {ticket}
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="csv">{tr}CSV File{/tr}
            {capture name=help}{tr}Column names on the first line:{/tr}<br>banId,mode,title,ip1,ip2,ip3,ip4,user,date_from,date_to,use_dates,created,created_readable,message,sections<br>{tr}Sections format:{/tr} {tr}section names are splitted by pipes (vertical bars). To see an example and use it as template, add one rule by hand, and export it as csv{/tr}<br>{tr}Date format:{/tr} {tr}See:{/tr} https://doc.tiki.org/Date-and-Time-Features{/capture}
            <a title="{tr}Help{/tr}" {popup text=$smarty.capture.help|escape}>{icon name='help'}</a>
        </label>
        <div class="col-sm-8">
            <input type="file" accept=".csv" name="fileCSV" class="form-control">
            <label class="col-form-label" for="import_as_new">
                <input type="checkbox" name="import_as_new" id="import_as_new">
                {tr}Import as new rules{/tr}
            </label>
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-8 offset-sm-4">
            <input type="submit" class="btn btn-primary btn-sm" name="import" value="{tr}Import{/tr}">
        </div>
    </div>
 </form>

{if $items}
    <h2>{tr}Find{/tr}</h2>
    <form method="post" action="tiki-admin_banning.php">
        {ticket}
        <input type="hidden" name="offset" value="{$offset|escape}">
        <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
        <label for="banning-find">{tr}Find:{/tr}</label><input type="text" name="find" id="banning-find" value="{$find|escape}">
    </form>
{/if}
<h2>{tr}Current rules{/tr}</h2>
<form method="post" id="banning_rules_list" action="tiki-admin_banning.php">
    {ticket}
    <input type="hidden" name="offset" value="{$offset|escape}">
    <input type="hidden" name="find" value="{$find|escape}">
    <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
    <div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    {if $items|count > 0}
                        <input
                            type="submit"
                            class="btn btn-warning btn-sm tips"
                            name="del" value="{tr}x{/tr}"
                            title=":{tr}Remove{/tr}"
                            onclick="confirmPopup('{tr}Delete selected banning rules?{/tr}')"
                        >
                    {/if}
                </th>
                <th>{tr}Title{/tr}</th>
                <th>{tr}User/IP{/tr}</th>
                <th>{tr}Sections{/tr}</th>
                <th></th>
            </tr>

            {section name=user loop=$items}
                <tr>
                    <td>
                        <input type="checkbox" name="delsec[{$items[user].banId}]">
                    </td>
                    <td class="text">
                        <a href="tiki-admin_banning.php?banId={$items[user].banId}" class="link">{$items[user].title|escape}</a>
                    </td>
                    <td>
                        {if $items[user].mode eq 'user'}
                            {$items[user].user|escape}
                        {else}
                            {$items[user].ip1}.{$items[user].ip2}.{$items[user].ip3}.{$items[user].ip4}
                        {/if}
                    </td>
                    <td>
                        {section name=ix loop=$items[user].sections}
                            {$items[user].sections[ix].section}{if not $smarty.section.ix.last},{/if}
                        {/section}
                    </td>
                    <td>
                        {actions}
                            {strip}
                                <action>
                                    <a href="tiki-admin_banning.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;find={$find}&amp;banId={$items[user].banId}">
                                        {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-admin_banning.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;find={$find}&amp;delsec[{$items[user].banId}]=y&amp;del=y" onclick="confirmPopup('{tr}Delete banning rule?{/tr}', '{ticket mode=get}', );">
                                        {icon name='delete' _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                    </a>
                                </action>
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {sectionelse}
                {norecords _colspan=5 _text="{tr}No rules found{/tr}"}
            {/section}
        </table>
    </div>
</form>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
