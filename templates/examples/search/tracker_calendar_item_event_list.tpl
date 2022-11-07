{*
 * Demonstration of the use of Date & Time (Calendar Item) Tracker Field *
 * See notes in templates/examples/search/tracker_calendar_item_event.tpl
 *}


{foreach $results as $row}
    {*    {if isset($smarty.request.debug)}<pre style="display:none;" class="row-dump">{$row|d}</pre>{/if}*}
    {if $row.object_type eq 'calendaritem'}
        {$calendarItem = $row}
        {if isset($smarty.request.debug)}<pre style="display:none;" class="calendar-item-dump">{$calendarItem|d}</pre>{/if}

        {* calendar items linked to a tracker item have two extra fields:
            trackeritems: array of tracker itemIds
            recurrence_id: the id of the recurrence series if it is a one of a series of recurring events
        *}
        {if not empty($calendarItem.trackeritems)}
            {* skip the first one that has the tracker item set in the calendar event *}
            {continue}
        {/if}
        {if not empty($calendarItem.recurrence_id)}
            {* we're only interested in the recurrences becasue we have what we need on the trackerItem *}
            {capture assign='jsonval'}{strip}{wikiplugin _name='list'}{literal}
                {list max="1"}
                {filter type="trackeritem"}
                {filter field="tracker_field_calTestDate_recurrenceId" content="{/literal}{$calendarItem.recurrence_id}{literal}"}
                {pagination offset_arg="nooffset"}
                {output template="json"}
                {FORMAT(name="desc")}{display name="tracker_field_calTestDescription" format="snippet" length="100" default="No description provided"}%%%{display name="tracker_field_eventCost" default="No price provided"}{FORMAT}
                {ALTERNATE()}empty{ALTERNATE}
            {/literal}{/wikiplugin}{/strip}{/capture}
            {if $jsonval eq 'empty'}
                <!-- error: tracker items not found for recurrence id {$calendarItem.recurrence_id} on cal item {$calendarItem.object_id} -->
{*                {continue}*}
            {/if}
            {$trackerItem = $jsonval|json_decode:true}
            {$trackerItem = $trackerItem.result[0]}
        {/if}
        {if isset($smarty.request.debug)}Calendar Item{/if}
    {else}
        {$calendarItem = []}
        {$trackerItem = $row}
        {if isset($smarty.request.debug)}Tracker Item{/if}
    {/if}
    {if isset($smarty.request.debug)}<pre style="display:none;" class="tracker-item-dump">{$trackerItem|d}</pre>{/if}

    <div class="col-sm-12">
        <h3>
            <a href="{"`$smarty.request.page|slug`?itemId=`$trackerItem.object_id`"}">{$row.title}</a>
        </h3>
        <div class="event-date">
            {if $calendarItem}{icon name='calendar'}{/if}
            {if not empty($calendarItem.start_date)}
                {$calendarItem.start_date|tiki_long_datetime}
            {elseif $row.tracker_field_eventDate}
                {$row.tracker_field_calTestDate|tiki_long_datetime}
            {else}
                Something went wrong - no date?
            {/if}
        </div>
    </div>
{/foreach}
