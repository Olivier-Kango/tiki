{tikimodule error=$module_params.error title=$tpl_module_title name="reading time" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    <div>
        {if ($readingTimeMin < $module_params.minTimeThreshold) && ($module_params.minTimeThreshold neq 'none')} {* If minTimeThreshold has not being reached or neq 'none" *}
            {tr}{$module_params.minTimeThresholdText}{/tr}
        {elseif ($readingTimeMin > $module_params.maxTimeThreshold) && ($module_params.maxTimeThreshold neq 'none')} {* If maxTimeThreshold has not being reached or neq 'none" *}
            {tr}{$module_params.maxTimeThresholdText}{/tr}
        {else}
            {tr}{$module_params.timePrefixText}{/tr}
                {if $module_params.timeMinutesOnly eq 'y'}
                    {$readingTimeMin}
                {else}
                    {$readingTimeMin}'{$readingTimeSec}
                {/if}
            {tr}{$module_params.timePostfixText}{/tr}
        {/if}
    </div>
{/tikimodule}