<div class="integrated-page">
    {$data}
</div>

<hr>
<div class="t_navbar">
    {assign var=thisrepID value=$repID|escape}
    {if $cached eq 'y'}
        {if $file neq null and strlen($file) gt 0}
            {button href="tiki-integrator.php?repID=$thisrepID&amp;file=$file&amp;clear_cache" class="btn btn-primary" _title="{tr}Clear cached version and refresh cache{/tr}" _text="{tr}Refresh{/tr}"}
        {else}
            {button href="tiki-integrator.php?repID=$thisrepID&amp;clear_cache" class="btn btn-primary" _title="{tr}Clear cached version and refresh cache{/tr}" _text="{tr}Refresh{/tr}"}
        {/if}
    {/if}

    {button href="tiki-list_integrator_repositories.php" _text="{tr}List Repositories{/tr}" _class="btn-info"}

    {* Show config buttons only for admins *}
    {if $tiki_p_admin eq 'y' or $tiki_p_admin_integrator eq 'y'}
        {assign var=thisfile value=$file|escape}
        <div class="btn-group">
            {button href="tiki-admin_integrator_rules.php?repID=$thisrepID&amp;file=$thisfile" class="btn btn-primary" _text="{tr}configure rules{/tr}"}
            {button href="tiki-admin_integrator.php?action=edit&amp;repID=$thisrepID" class="btn btn-primary" _text="{tr}Edit Repository{/tr}"}
        </div>
    {/if}
</div>
