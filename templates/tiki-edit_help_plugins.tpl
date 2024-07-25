{* \brief Show plugins help
 * included by tiki-show_help.tpl via smarty_block_add_help() *}

{if count($plugins) ne 0}

    <div class="help_section">
        {remarksbox type="info"}
        {tr}Note: plugin arguments can be enclosed with double quotes (<code>&quot;</code>). This allows them to contain <code>,</code> or <code>=</code> or <code>&gt;</code>.{/tr} {if $prefs.feature_help eq 'y'}{tr}More information:{/tr} <a href="{$prefs.helpurl}Plugins" target="tikihelp" class="tikihelp alert-link" title="{tr}Plugins:{/tr}{tr}Wiki plugins extend the function of wiki syntax with more specialized commands.{/tr}">
                    Plugins {icon name="link-external" istyle="font-size: 70%"}</a>.{/if}
        {/remarksbox}

        {if $tiki_p_admin eq 'y'}
            <a href="tiki-admin.php?page=textarea&amp;cookietab=2" target="tikihelp" class="tikihelp">
                {tr}Activate/deactivate plugins{/tr}
            </a>
        {/if}

        {if isset($editorId)}
            {listfilter editorId=$editorId parentTabId="plugin_help" selectors=".card.plugin"}
        {else}
            {listfilter parentTabId="plugin_help" selectors=".card.plugin"}
        {/if}
        <div class="d-flex gap-2 flex-wrap">
            {section name=i loop=$plugins}
                {if !empty($plugins[i])}
                    {$plugins[i].help} {* $plugins[i].help is generated from the tiki-plugin_help.tpl template *}
                {/if}
            {/section}
        </div>
    </div>
{else}
    <div class="help_section">
        {remarksbox type="info"}
        {tr}No plugins available.{/tr}
        {/remarksbox}
    </div>
{/if}
