{tikimodule error=$module_params.error title=$tpl_module_title name="reading time" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
{$wordCount=$parsed|strip_tags|count_words} {* the calculation is based on words only *}
{$readingTime=($wordCount/$module_params.wordsPerMinutes)|number_format:2} {* the number of words is divided by wordsPerMinutes human can read and rounded *}
    <div>
        {if ($readingTime < $module_params.minTimeThreshold) && ($module_params.minTimeThreshold neq 'none')} {* If minTimeThreshold has not being reached or neq 'none" *}
            {tr}{$module_params.minTimeThresholdText}{/tr}
        {elseif ($readingTime > $module_params.maxTimeThreshold) && ($module_params.maxTimeThreshold neq 'none')} {* If maxTimeThreshold has not being reached or neq 'none" *}
            {tr}{$module_params.maxTimeThresholdText}{/tr}
        {else}
            {tr}{$module_params.timePrefixText}{/tr}
                {if $module_params.timeMinutesOnly eq 'y'} {* minutes only, we don't need seconds *}
                    {$readingTime|number_format:0}
                {else} {* minutes and seconds are displayed, seconds are converted from numerical value to time format *}
                    {$readingTimeMin=$readingTime|regex_replace:'/[.,][0-9]+/':''}
                    {$readingTimeSec=$readingTime-$readingTimeMin}
                    {$readingTimeSec=($readingTimeSec*60)|number_format:0}
                    {$readingTimeMin}'{$readingTimeSec}
                {/if}
            {tr}{$module_params.timePostfixText}{/tr}
        {/if}
    </div>
{/tikimodule}