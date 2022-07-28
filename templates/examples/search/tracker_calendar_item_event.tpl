{*
 * Demonstration of the use of Date & Time (Calendar Item) Tracker Field
 * https://doc.tiki.org/Date-Tracker-Fields#Date_REAL_AMP_Time_Calendar_Item_Tracker_Field
 *
 * See notes in templates/examples/search/tracker_calendar_item_event_list.tpl
 *
 * This example uses tracker #11 and calendar #2
 *
 * Wiki page markup:
 * Note: you can't filter for the tracker_id or calendar_id directly so
 *       if you have other trackers and calendars you need to do this "and not 42" thing
 *       to filter them all out (must be a better way?)
 *       You only need to do the type="trackeritem,calendaritem" filter if you need to mixing
 *       tracker items with and without recurring events attached
 ***********************************************************************************

{PARAM(name="itemId")}
  {LIST()}
    {filter type="trackeritem"}
    {filter field="tracker_id" content="11"}
    {filter field="object_id" content="{{itemId}}"}
    {output template="templates/examples/search/tracker_calendar_item_event.tpl"}
  {LIST}
{ELSE}
   {LIST()}
    {filter type="trackeritem,calendaritem"}
    {filter field="calendar_id" content="not 1 and not 3"}
    {filter field="tracker_id" content="not 1 and not 2 and not 3 and not 4 and not 5 and not 6 not 7 and not 8 and not 9 and not 10 and not 12 and not 13 and not 14 and not 16 and not 17 and not 18 and not 19 and not 20 and not 21 and not 22 and not 23 and not 24 and not 25 and not 26 and not 27 and not 28 and not 29 and not 30 and not 31 and not 32 and not 33"}
    {output template="templates/examples/search/tracker_calendar_item_event_list.tpl"}
  {LIST}
{PARAM}

 ***********************************************************************************
 * Tracker Fields Export
 ***********************************************************************************

[FIELD123]
fieldId = 123
name = Title
permName = calTestTitle
position = 10
type = t
options =
isMain = y
isTblVisible = y
isSearchable = n
isPublic = y
isHidden = n
isMandatory = y
description =
descriptionIsParsed = n
rules =
[FIELD234]
fieldId = 234
name = Date
permName = calTestDate
position = 20
type = CAL
options = {"calendarId":2,"showEventIdInput":1,"useNow":0,"notBefore":0,"notAfter":0,"datetime":"dt","startyear":"","endyear":"","blankdate":"","useTimeAgo":0,"isItemDateField":1}
isMain = n
isTblVisible = n
isSearchable = n
isPublic = y
isHidden = n
isMandatory = n
description =
descriptionIsParsed = n
rules =

 ***********************************************************************************
 * Tracker Fields Export End
 ***********************************************************************************
 *
 *
 *}


{$row = $results[0]}

<div class="row">
    <div class="col-sm-12">
        <h1>{$row.title}</h1>

        {if not empty($row.tracker_field_calTestDate_recurrenceId)}
            {wikiplugin _name='list'}{literal}
                {list max="100"}
                {filter type="calendaritem"}
                {filter field="calendar_id" content="2"}
                {filter field="recurrence_id" content="{/literal}{$row.tracker_field_calTestDate_recurrenceId}{literal}"}
                {sort mode="start_date_nasc"}
                {OUTPUT()}* [tiki-calendar_edit_item.php?viewcalitemId={display name="object_id"}|{display name="start_date" format="date"}]
{OUTPUT}
                {ALTERNATE()}^No recurring calendar event found^{ALTERNATE}
            {/literal}{/wikiplugin}
        {elseif not empty($row.tracker_field_calTestDate_calitemid)}
            {wikiplugin _name='list'}{literal}
                {list max="100"}
                {filter type="calendaritem"}
                {filter field="calendar_id" content="2"}
                {filter field="object_id" content="{/literal}{$row.tracker_field_calTestDate_calitemid}{literal}"}
                {sort mode="start_date_nasc"}
                {OUTPUT()}* [tiki-calendar_edit_item.php?viewcalitemId={display name="object_id"}|{display name="start_date" format="date"}]
{OUTPUT}
                {ALTERNATE()}^No calendar event found^{ALTERNATE}
            {/literal}{/wikiplugin}
        {else}
            {$row.tracker_field_calTestDate|tiki_long_datetime}
            <span class="text-muted">{$row.tracker_field_eventDate|tiki_short_datetime}</span>
        {/if}

        <a href="{$smarty.request.page}">List</a>
    </div>
</div>


