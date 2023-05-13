{if empty($module_params.silent)}
    {tikimodule error=$module_params.error title=$tpl_module_title name="current_activity" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        {if $count}
            <ol>
                {foreach from=$results item=result key=value}
                    <li>{tr _0=$result['type']}User editing %0:{/tr} <b>{$value}</b></li>
                    {foreach from=$result item=res}
                    <ul>
                    {if is_array($res)}
                        {foreach from=$res item=user}
                        <li>{$user}</li>
                        {/foreach}
                    {/if}
                    </ul>
                    {/foreach}
                {/foreach}
            </ol>
        {/if}
    {/tikimodule}
{/if}
