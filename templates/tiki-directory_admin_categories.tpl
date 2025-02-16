{title help="Directory Categories" url="tiki-directory_admin_categories.php?parent=$parent"}{tr}Admin directory categories{/tr}{/title}

{* Display the title using parent *}
{include file='tiki-directory_admin_bar.tpl'}

{* Navigation bar to admin, admin related, etc *}
<h2>{tr}Parent directory category:{/tr}</h2>
{* Display the path adding manually the top category id=0 *}
<form name="path" method="post" action="tiki-directory_admin_categories.php">
    {ticket}
    <br>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="parent">
            {tr}Parent directory category{/tr}
        </label>
        <div class="col-sm-7">
            <select name="parent" id="parent" onchange="javascript:path.submit();" class="form-control">
                <option value="0">{tr}Top{/tr}</option>
                {section name=ix loop=$categs}
                    <option value="{$categs[ix].categId|escape}" {if $parent eq $categs[ix].categId}selected="selected"{/if}>{$categs[ix].path|escape}</option>
                {/section}
            </select>
        </div>
        <div class="col-sm-7 offset-sm-4 mt-2">
            <input type="submit" class="btn btn-primary btn-sm" name="go" value="{tr}Go{/tr}">
        </div>
    </div>
</form>

{* Dislay a form to add or edit a category *} <br>
{if $categId eq 0}
    <h2>{tr}Add a directory category{/tr}</h2>
{else}
    <h2>{tr}Edit this directory category:{/tr} {$info.name}</h2>
    <a href="tiki-directory_admin_categories.php">{tr}Add a Directory Category{/tr}</a>
{/if}
<form action="tiki-directory_admin_categories.php" method="post">
    {ticket}
    <input type="hidden" name="parent" value="{$parent|escape}">
    <input type="hidden" name="categId" value="{$categId|escape}">

    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="name">{tr}Name{/tr}</label>
        <div class="col-sm-7">
            <input type="text" name="name" id="name" value="{$info.name|escape}" class="form-control">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="description">{tr}Description{/tr}</label>
        <div class="col-sm-7">
            <textarea rows="5" cols="60" name="description" id="description" class="form-control">{$info.description|escape}</textarea>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="childrenType">{tr}Child type{/tr}</label>
        <div class="col-sm-7">
            <select name="childrenType" id="childrenType" class="form-control">
                <option value='c' {if $info.childrenType eq 'c'}selected="selected"{/if}>{tr}Most visited directory sub-categories{/tr}</option>
                <option value='d' {if $info.childrenType eq 'd'}selected="selected"{/if}>{tr}Directory category description{/tr}</option>
                <option value='r' {if $info.childrenType eq 'r'}selected="selected"{/if}>{tr}Random directory sub-categories{/tr}</option>
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="viewableChildren">{tr}Maximum number of child directory categories to show{/tr}</label>
        <div class="col-sm-7">
            <select name="viewableChildren" id="viewableChildren" class="form-control">
                <option value="0" {if $info.viewableChildren eq 0}selected="selected"{/if}>{tr}none{/tr}</option>
                <option value="1" {if $info.viewableChildren eq 1}selected="selected"{/if}>1</option>
                <option value="2" {if $info.viewableChildren eq 2}selected="selected"{/if}>2</option>
                <option value="3" {if $info.viewableChildren eq 3}selected="selected"{/if}>3</option>
                <option value="4" {if $info.viewableChildren eq 4}selected="selected"{/if}>4</option>
                <option value="5" {if $info.viewableChildren eq 5}selected="selected"{/if}>5</option>
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="allowSites">{tr}Allow sites in this directory category{/tr}</label>
        <div class="col-sm-7">
            <input name="allowSites" id="allowSites" type="checkbox" {if $info.allowSites eq 'y'}checked="checked"{/if} >
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="showCount">{tr}Show number of sites in this directory category{/tr}</label>
        <div class="col-sm-7">
            <input name="showCount" id="showCount" type="checkbox" {if $info.showCount eq 'y'}checked="checked"{/if}>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="editorGroup">{tr}Editor group{/tr}</label>
        <div class="col-sm-7">
            <select name="editorGroup" id="editorGroup" class="form-control">
                <option value="">{tr}None{/tr}</option>
                {section name=ux loop=$groups}
                    <option value="{$groups[ux]|escape}" {if $editorGroup eq $groups[ux]}selected="selected"{/if}>{$groups[ux]}</option>
                {/section}
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-7 offset-sm-4">
            <input class="btn btn-primary" type="submit" name="save" value="{tr}Save{/tr}">
        </div>
    </div>
    {include file='categorize.tpl'}
</form>
<br>

<h2>{tr}Directory Subcategories{/tr}</h2>
{* Display the list of categories (items) using pagination *}
{* Links to edit, remove, browse the categories *}
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table table-striped table-hover">
        <tr>
            <th><a href="tiki-directory_admin_categories.php?parent={$parent}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Name{/tr}</a></th>
            <th><a href="tiki-directory_admin_categories.php?parent={$parent}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'childrenType_desc'}childrenType_asc{else}childrenType_desc{/if}">{tr}cType{/tr}</a></th>
            <th><a href="tiki-directory_admin_categories.php?parent={$parent}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'viewableChildren_desc'}viewableChildren_asc{else}viewableChildren_desc{/if}">{tr}View{/tr}</a></th>
            <th><a href="tiki-directory_admin_categories.php?parent={$parent}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'allowSites_desc'}allowSites_asc{else}allowSites_desc{/if}">{tr}allow{/tr}</a></th>
            <th><a href="tiki-directory_admin_categories.php?parent={$parent}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'showCount_desc'}showCount_asc{else}showCount_desc{/if}">{tr}count{/tr}</a></th>
            <th><a href="tiki-directory_admin_categories.php?parent={$parent}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'editorGroup_desc'}editorGroup_asc{else}editorGroup_desc{/if}">{tr}editor{/tr}</a></th>
            <td></td> {* Changed from th to avoid ARIA empty header error *}
        </tr>

        {section name=user loop=$items}
            <tr>
                <td class="text"><a class="tablename" href="tiki-directory_admin_categories.php?parent={$items[user].categId}">{$items[user].name|escape}</a></td>
                <td class="text">{$items[user].childrenType}</td>
                <td class="text">{$items[user].viewableChildren}</td>
                <td class="text">{if $items[user].allowSites eq 'y'} Yes ({$items[user].sites}){else} No {/if}</td>
                <td class="text">{if $items[user].showCount eq 'y'} Yes {else} No {/if}</td>
                <td class="text">{$items[user].editorGroup}</td>
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                <a href="tiki-directory_admin_related.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;categId={$items[user].categId}">
                                    {icon name='move' _menu_text='y' _menu_icon='y' alt="{tr}Relate{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="tiki-directory_admin_categories.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;parent={$parent}&amp;categId={$items[user].categId}">
                                    {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <form action="tiki-directory_admin_categories.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="parent" value="{$parent}">
                                    <input type="hidden" name="offset" value="{$offset}">
                                    <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                    <input type="hidden" name="remove" value="{$items[user].categId}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup()">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                            </action>
                        {/strip}
                    {/actions}
                </td>
            </tr>
        {sectionelse}
            {norecords _colspan=7}
        {/section}
    </table>
</div>
{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
