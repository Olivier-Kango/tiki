{if $prefs.feature_socialnetworks eq 'y'}
    {if ! empty($module_params.rows)}
        {$rows = $module_params.rows}
    {else}
        {$rows = ''}
    {/if}
    {tikimodule error=$module_params.error title=$tpl_module_title name="last_tweets" flip=$module_params.flip rows=$rows decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {modules_list list=$timeline nonums=$nonums}
        {section name=ix loop=$timeline }
            <li>
                {if ! empty($module_params.showuser) && $module_params.showuser eq 'y' && ! empty($timeline[ix].fromName)}<span class="fb-name">{$timeline[ix].fromName}</span>{/if}
                <span class="fb-text fb-{$timeline[ix].type}">{$timeline[ix].message}</span>
                <span class="fb-date"><a href="{$timeline[ix].link}">{$timeline[ix].created_time|tiki_short_datetime}</a></span>
            </li>
        {/section}
    {/modules_list}
    {/tikimodule}
{/if}
