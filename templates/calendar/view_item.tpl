{extends 'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <h2>{$calitem.parsedName}</h2>
    <div class="summary">
        {$calendars[$calitem.calendarId].name|escape}
    </div>
    {if $recurrence.nbRecurrences > 0}
        {if $recurrence.nbRecurrences eq 1}
            {tr}Event occurs once on{/tr}&nbsp;{$recurrence.startPeriod|tiki_long_date}
        {elseif $recurrence.nbRecurrences gt 1 or $recurrence.endPeriod gt 0}
            {tr}Event is repeated{/tr}&nbsp;
            {if $recurrence.nbRecurrences gt 1}
                {$recurrence.nbRecurrences} {tr}times,{/tr}&nbsp;
            {/if}
            {if $recurrence.weekly}
                    {tr}on{/tr}&nbsp;
                    {foreach $recurrence.weekdays as $day}{strip}
                        {if $day@iteration eq $day@total and not $day@first}
                            &nbsp;{tr}and{/tr}&nbsp;
                        {elseif not $day@last and not $day@first}
                            ,&nbsp;
                        {/if}
                        {$daynames[$day]}
                    {/strip}{/foreach}
            {elseif $recurrence.monthly}
                {tr}on{/tr}&nbsp;{$recurrence.dayOfMonth} {tr}of every month{/tr}
            {else}
                {tr}on each{/tr}&nbsp;{$recurrence.dateOfYear_day} {tr}of{/tr} {$monthnames[$recurrence.dateOfYear_month]}
            {/if}
            <br>
            {tr}starting{/tr} {$recurrence.startPeriod|tiki_long_date}
            {if $recurrence.endPeriod gt 0}
                , {tr}ending{/tr}&nbsp;{$recurrence.endPeriod|tiki_long_date}
            {/if}.
        {/if}
    {/if}
    <div class="summary">
        {$calitem.display_datetimes}
    </div>
    <div class="description">
        {$calitem.parsed|default:"<em>{tr}No description{/tr}</em>"}
    </div>
    {if $calitem.status eq 0}
        <label class="badge bg-secondary">{tr}Tentative{/tr}</label>
    {elseif $calitem.status eq 1}
        <label class="badge bg-success">{tr}Confirmed{/tr}</label>
    {elseif $calitem.status eq 2}
        <label class="badge bg-danger">{tr}Cancelled{/tr}</label>
    {/if}
    <div style="background-color:#{$listprioritycolors[$calitem.priority]}">
        {$calitem.priority}
    </div>
    <div class="category">
        {$calitem.categoryName|escape}
    </div>
    <div class="location">
        {$calitem.locationName|escape}
    </div>
    {if $calendar.customurl ne 'n'}
        <a class="url" href="{$calitem.url}">
            {$calitem.url|escape}
        </a>
    {/if}
    {$calitem.lang|langname}
    {foreach $calitem.organizers as $organizer}
        {$organizer|userlink}
        <br>
    {/foreach}
    {assign var='in_particip' value='n'}
    {foreach item=ppl from=$calitem.participants}
        {$ppl.username|userlink}
        {if $listroles[$ppl.role]}
            ({$listroles[$ppl.role]})
        {/if}
        <br>
        {if $ppl.username eq $user}
            {assign var='in_particip' value='y'}
        {/if}
    {/foreach}
    {if not $preview and $tiki_p_calendar_add_my_particip eq 'y'}
        {if $in_particip eq 'y'}
            {button _text="{tr}Withdraw me from the list of participants{/tr}" href="?del_me=y&viewcalitemId=$calitemId"}
        {else}
            {button _text="{tr}Add me to the list of participants{/tr}" href="?add_me=y&viewcalitemId=$calitemId"}
        {/if}
    {/if}
{/block}
