{* $Id$ *}

{if $prefs.feature_file_galleries eq 'y'}
    {if !isset($tpl_module_title)}
        {if isset($module_rows) && $module_rows gt 0}
            {capture assign=tpl_module_title}{tr _0=$module_rows}Last %0 Podcasts{/tr}{/capture}
        {else}
            {assign value="{tr}Newest Podcasts{/tr}" var="tpl_module_title"}
        {/if}
    {/if}
    {tikimodule error=$module_params.error title=$tpl_module_title name="last_podcasts" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

        {if $nonums != 'y'}<ol>{else}<ul>{/if}
            {section name=ix loop=$modLastFiles}
            <li>
                <div class="module">
                    <a class="linkmodule" href="tiki-download_file.php?fileId={$modLastFiles[ix].fileId}" {if $verbose eq 'n'}title="{$modLastFiles[ix].description|escape:'html'}"{/if} onclick="return false;">
                        {$modLastFiles[ix].name|escape:'html'}
                    </a>
                </div>
            </li>
            {/section}
        {if $nonums != 'y'}</ol>{else}</ul>{/if}

        {if $link_url neq ""}
            <div class="lastlinkmodule" >
                <a class="linkmodule" href="{$link_url}" >{if $link_text neq ""}{tr}{$link_text}{/tr}{else}{$link_url}{/if}</a>
            </div>
        {/if}

    {/tikimodule}
{/if}
