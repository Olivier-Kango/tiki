{extends 'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <h2>{$calitem.parsedName}</h2>
    <div class="summary">
        {$calendar.name|escape}
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
    <div class="small mt-3">
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
                    <td>{if $calitem.priority neq ''}{$calitem.priority|escape}{else}<em class="text-secondary">{tr}No priority set for this event{/tr}</em>{/if}</td>
                </tr>
            {/if}
            {if $calendar.customcategories eq 'y'}
                <tr class="category">
                    <th>{tr}Classification:{/tr}</th>
                    <td>{if $calitem.categoryName neq ''}{$calitem.categoryName|escape}{else}<em class="text-secondary">{tr}No classification added to this event{/tr}</em>{/if}</td>
                </tr>
            {/if}
            {if $calendar.customlocations eq 'y'}
                <tr class="location">
                    <th>{tr}Location:{/tr}</th>
                    <td>{if $calitem.locationName neq ''}{$calitem.locationName|escape}{else}<em class="text-secondary">{tr}No location added to this event{/tr}</em>{/if}</td>
                </tr>
            {/if}
            {if $calendar.customurl eq 'y'}
                <tr class="url">
                    <th>{tr}Custom url:{/tr}</th>
                    <td>
                        {if $calitem.customurl neq ''}
                            <a class="url" href="{$calitem.url}">
                                {$calitem.url|escape}
                            </a>
                        {else}
                            <em class="text-secondary">{tr}No custom url added{/tr}</em>
                        {/if}
                    </td>
                </tr>
            {/if}
            {if $calendar.customlanguages eq 'y'}
                <tr class="language">
                    <th>{tr}Language:{/tr}</th>
                    <td>{if $calitem.lang neq ''}{$calitem.lang|langname}{else}<em class="text-secondary">{tr}No language added to this event{/tr}</em>{/if}</td>
                </tr>
            {/if}
            {if $calendar.customparticipants eq 'y'}
                <tr class="organizers">
                    <th>{tr}Organizers:{/tr}</th>
                    <td>
                        {if ! empty($calitem.organizers) && ($calitem.organizers|@count)}
                            <ul>
                            {foreach $calitem.organizers as $organizer}
                                <li>{$organizer|userlink}</li>
                            {/foreach}
                            </ul>
                        {else}
                            <em class="text-secondary">{tr}No organizers{/tr}</em>
                        {/if}
                    </td>
                </tr>
                <tr class="participants">
                    {$particiapting = false}
                    <th>{tr}Participants:{/tr}</th>
                    <td>
                        <p>
                            {if $calitem.participants|@count}
                                <ul>
                                {foreach $calitem.participants as $person}
                                    <li>
                                        {$person.username|userlink}
                                        {if $listroles[$person.role]} ({$listroles[$person.role]}){/if}
                                    </li>
                                    {if $person.username eq $user}{$particiapting = true}{/if}
                                {/foreach}
                                </ul>
                            {else}
                                <em class="text-secondary">{tr}No participants{/tr}</em>
                            {/if}
                        </p>
                        {if not $preview and $tiki_p_calendar_add_my_particip eq 'y'}
                            {if $particiapting}
                                {button _text="{tr}Withdraw me from the list of participants{/tr}" href="{service controller='calendar' action='del_me' calitemId=$calitem.calitemId}" _class='btn-sm'}
                            {else}
                                {button _text="{tr}Add me to the list of participants{/tr}" href="{service controller='calendar' action='add_me' calitemId=$calitem.calitemId}" _class='btn-sm'}
                            {/if}
                        {/if}
                    </td>
                </tr>
            {/if}
        </table>
    </div>
    {if not $preview}
        {permission name='change_events' type='calendaritem' object=$calitem.calitemId}
            <a href="{service controller='calendar' action='edit_item' calitemId=$calitem.calitemId|escape}" class="btn btn-primary edit-calendar-item-btn">
                {tr}Edit{/tr}
            </a>
        {/permission}
    {/if}
{/block}
