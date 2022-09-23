{* $Id$ *}

{remarksbox type="note" title="{tr}Tip{/tr}"}{tr}<b>Orphan preferences </b> are preferences that exist in previous versions of Tiki but for various reasons have been removed. This page allows you to view the values you have configured for these preferences and gives you the option to clear the data if necessary.{/tr}
{/remarksbox}

<div class="text-center mb-4">
    <a href="tiki-admin.php?page=orphanprefs&clear=all" class="btn btn-primary" title="{tr}Delete all{/tr}">{icon name="trash"} {tr}Clear all data{/tr}</a>
</div>
    
<table class="table table-striped table-hover">
    <thead>
        <tr class="bg-info d-flex">
            <th class="col-4">{tr}Preferences{/tr}</th>
            <th class="col-7">{tr}Values{/tr}</th>
            <th class="col-1"></th>
        </tr>
    </thead>
    <tbody>
        {if ! (empty($orphanPrefs))}
            {foreach $orphanPrefs as $pref}
                <tr class="d-flex">
                    <td class="col-4"><b>{$pref.name|escape}</br></td>
                    <td class="col-7">{$pref.value|truncate:55}</td>
                    <td class="col-1"><a href="tiki-admin.php?page=orphanprefs&clear={$pref.name|escape}" class="tips" title=":{tr}Delete{/tr}">{icon name="trash"}</a></td>
                </tr>
            {/foreach}
        {else}
            <td class="col text-center"><b>{tr}You have no orphan preferences. All is well !{/tr}</br></td>
        {/if}
    </tbody>
</table>
<br>

