{extends 'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <h2>{$calitem.parsedName}</h2>
    <div class="summary">
        {$calendars[$calitem.calendarId].name|escape}
    </div>
    {if $recurrent}
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
    <div class="small">
        <table class="table table-borderless table-sm{if $preview} table-secondary{/if}">
            {* custom properties *}
            {if $calendar.customstatus eq 'y'}
                <tr>
                    <td colspan="2">
                        {if $calitem.status eq 0}
                            <label class="badge bg-secondary mb-1">{tr}Tentative{/tr}</label>
                        {elseif $calitem.status eq 1}
                            <label class="badge bg-success mb-1">{tr}Confirmed{/tr}</label>
                        {elseif $calitem.status eq 2}
                            <label class="badge bg-danger mb-1">{tr}Cancelled{/tr}</label>
                        {/if}
                    </td>
                </tr>
            {/if}
            {if $calendar.custompriorities eq 'y'}
                <tr class="priority">
                    <th style="background-color:#{$listprioritycolors[$calitem.priority]}">
                        {tr}Priority:{/tr}
                    </th>
                    <td>{$calitem.priority|escape}</td>
                </tr>
            {/if}
            {if $calendar.customcategories eq 'y'}
                <tr class="category">
                    <th>{tr}Classification:{/tr}</th>
                    <td>{$calitem.categoryName|escape}</td>
                </tr>
            {/if}
            {if $calendar.customlocations eq 'y'}
                <tr class="location">
                    <th>{tr}Location:{/tr}</th>
                    <td>{$calitem.locationName|escape}</td>
                </tr>
            {/if}
            {if $calendar.customurl ne 'n'}
                <tr class="url">
                    <th></th>
                    <td>
                        <a class="url" href="{$calitem.url}">
                            {$calitem.url|escape}
                        </a>
                    </td>
                </tr>
            {/if}
            {if $calendar.customlanguages eq 'y'}
                <tr class="language">
                    <th>{tr}Language:{/tr}</th>
                    <td>{$calitem.lang|langname}</td>
                </tr>
            {/if}
            {if $calendar.customparticipants eq 'y'}
                <tr class="organizers">
                    <th>{tr}Organizers:{/tr}</th>
                    <td>
                        <ul>
                        {foreach $calitem.organizers as $organizer}
                            <li>{$organizer|userlink}</li>
                        {/foreach}
                        </ul>
                    </td>
                </tr>
                <tr class="participants">
                    {$particiapting = false}
                    <th>{tr}Participants:{/tr}</th>
                    <td>
                        <ul>
                            {foreach $calitem.participants as $person}
                                <li>
                                    {$person.username|userlink}
                                    {if $listroles[$person.role]} ({$listroles[$person.role]}){/if}
                                </li>
                                {if $person.username eq $user}{$particiapting = true}{/if}
                            {/foreach}
                        </ul>
                        {if not $preview and $tiki_p_calendar_add_my_particip eq 'y'}
                            {if $particiapting}
                                {button _text="{tr}Withdraw me from the list of participants{/tr}" href="?del_me=y&viewcalitemId=$calitemId" _class='btn-sm'}
                            {else}
                                {button _text="{tr}Add me to the list of participants{/tr}" href="?add_me=y&viewcalitemId=$calitemId" _class='btn-sm'}
                            {/if}
                        {/if}
                    </td>
                </tr>
            {/if}
        </table>
    </div>
    {if not $preview}
        {permission name='change_events' type='calendaritem' object=$calitem.calitemId}
            <a href="{service controller='calendar' action='edit_item' calitemId=$calitem.calitemId|escape redirect='calendar'}" class="btn btn-primary click-modal">
                {tr}Edit{/tr}
            </a>
        {/permission}
    {/if}
{/block}
