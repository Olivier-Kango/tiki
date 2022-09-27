{* $Id$ *}
{strip}
    {tikimodule error=$module_params.error title=$tpl_module_title name="logo" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        <div {if $prefs.site_layout eq 'social' && $prefs.theme_navbar_fixed_topbar_offset ne ''} style="max-height: {$prefs.theme_navbar_fixed_topbar_offset}px"{/if}>
        <a class="navbar-brand d-flex {$module_params.class_image|escape}" href="{$module_params.link}" title="{$module_params.title_attr|escape}">
            {if $module_params.src}
                <img class="sitelogo-img img-fluid me-3" src="{$module_params.src}" alt="{$module_params.alt_attr|escape}" {if $prefs.site_layout eq 'social' && $prefs.theme_navbar_fixed_topbar_offset ne ''} style="height: calc({$prefs.theme_navbar_fixed_topbar_offset}px - ( 2 * var(--bs-navbar-padding-y) )); width: auto;"{/if}>
            {/if}
            {*{if $tiki_p_admin eq "y"}<a class="btn btn-primary btn-sm bottom mb-3 ms-1 me-1 mt-3 position-absolute opacity50 tips" href="tiki-admin.php?page=look&cookietab=2&highlight=sitelogo_src#feature_sitelogo_childcontainer" style="top: 0; right: 0" title="{tr}Change the logo:{/tr} {tr}Click to change or upload new logo{/tr}">{icon name="image"}</a>{/if}*}
            {if !empty($module_params.sitetitle) or !empty($module_params.sitesubtitle)}
                {if $prefs.site_layout neq 'social'}<div class="sitetitles"><div class="d-flex">{/if}
                {if !empty($module_params.sitetitle)}
                    <div class="sitetitle">{tr}{$module_params.sitetitle|escape}{/tr}</div>
                {/if}
                {if !empty($module_params.sitesubtitle)}
                    <div class="sitesubtitle">{tr}{$module_params.sitesubtitle|escape}{/tr}</div>
                {/if}
                {if $prefs.site_layout neq 'social'}</div></div>{/if}
            {/if}
        </a>
        </div>
    {/tikimodule}
{/strip}