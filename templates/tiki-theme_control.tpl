{title help="Theme Control"}{tr}Theme Control:{/tr} {tr}Categories{/tr}{/title}
<div class="t_navbar btn-group mb-4">
    {button href="tiki-theme_control_objects.php" class="btn btn-primary" _text="{tr}Control by Objects{/tr}"}
    {button href="tiki-theme_control_sections.php" class="btn btn-primary" _text="{tr}Control by Sections{/tr}"}
</div>
<h2>{tr}Assign themes to categories{/tr}</h2>
<form action="tiki-theme_control.php" method="post" class="mb-2">
    {ticket}
    <div class="mb-3 row">
        <div class="col-sm-5">
            <label for="categoryId" class="me-2">{tr}Category{/tr}</label>
            <select name="categoryId" id="categoryId" class="form-control form-control-sm me-2">
                {foreach from=$categories key=categoryId item=category}
                    <option value="{$categoryId|escape}">
                        {$category.name|escape} (Id:{$categoryId})
                    </option>
                {/foreach}
            </select>
        </div>
        <div class="col-sm-5">
            <label for="theme" class="me-2">{tr}Theme{/tr}</label>
            <select name="theme" id="theme" class="form-control form-control-sm me-2">
                {foreach from=$themes key=theme item=theme_name}
                    <option value="{$theme|escape}">{$theme_name}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="col-sm-12">
        <input type="submit" class="btn btn-primary btn-sm" name="assign" value="{tr}Assign{/tr}">
    </div>
</form>
<h2>{tr}Assigned categories{/tr}</h2>
{include file='find.tpl'}
<form action="tiki-theme_control.php" method="post" class="form">
    {ticket}
    <div class="table-responsive themecat-table">
        <table class="table">
            <tr>
                <td> {* th changed to td to prevent ARIA empty header error *}
                    <button type="submit" class="btn btn-danger btn-sm" name="delete" title="{tr}Delete selected{/tr}" {if !$channels}disabled{/if}>
                        {icon name="delete"}
                    </button>
                </td>
                <th>
                    <a href="tiki-theme_control.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">
                        {tr}Category{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-theme_control.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'theme_desc'}theme_asc{else}theme_desc{/if}">
                        {tr}Theme{/tr}
                    </a>
                </th>
            </tr>
            {section name=user loop=$channels}
                <tr>
                    <td class="checkbox-cell">
                        <input type="checkbox" name="categoryIds[{$channels[user].categId}]" class="form-check-input" aria-label="{tr}Select{/tr}">
                    </td>
                    <td class="text">
                        {$channels[user].name|escape} (Id:{$channels[user].categId})
                    </td>
                    <td class="text">
                        {$channels[user].theme}
                    </td>
                </tr>
            {/section}
        </table>
    </div>
</form>
{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
