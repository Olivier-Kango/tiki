{* $Id$ *}

{title admpage="calendar"}
    {if $displayedcals|@count eq 1}
        {tr}Calendar:{/tr} {assign var=x value=$displayedcals[0]}{$infocals[$x].name}
    {else}
        {tr}Calendar{/tr}
    {/if}
{/title}
<div id="calscreen">
    <div class="t_navbar mb-4">
        <div class="btn-group float-end">
            {if ! $js}<ul class="cssmenu_horiz"><li>{/if}
            <a class="btn btn-link" data-bs-toggle="dropdown" data-hover="dropdown" href="#">
                {icon name='menu-extra'}
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-title">
                    {tr}Monitoring{/tr}
                </li>
                <li class="dropdown-divider"></li>
                {if $displayedcals|@count eq 1 and $user and $prefs.feature_user_watches eq 'y'}
                    <li class="dropdown-item">
                        {if $user_watching eq 'y'}
                            <a href="tiki-calendar.php?watch_event=calendar_changed&amp;watch_action=remove" hspace="1">
                                {icon name="stop-watching"} {tr}Stop monitoring{/tr}
                            </a>
                        {else}
                            <a href="tiki-calendar.php?watch_event=calendar_changed&amp;watch_action=add" hspace="1">
                                {icon name="watch"} {tr}Monitor{/tr}
                            </a>
                        {/if}
                    </li>
                {/if}
                {if $displayedcals|@count eq 1 and $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
                    <li class="dropdown-item">
                        <a href="tiki-object_watches.php?objectId={$displayedcals[0]|escape:"url"}&amp;watch_event=calendar_changed&amp;objectType=calendar&amp;objectName={$infocals[$x].name|escape:"url"}&amp;objectHref={'tiki-calendar.php?calIds[]='|cat:$displayedcals[0]|escape:"url"}" hspace="1">
                            {icon name="watch-group"} {tr}Group Monitor{/tr}
                        </a>
                    </li>
                {/if}
            </ul>
            {if ! $js}</li></ul>{/if}
        </div>
        {if $tiki_p_admin_calendar eq 'y' or $tiki_p_admin eq 'y'}
            {if $displayedcals|@count eq 1}
                {button href="tiki-admin_calendars.php?calendarId={$displayedcals[0]}" _type="link" _text="{tr}Edit{/tr}" _icon_name="edit"}
            {/if}
            {button href="tiki-admin_calendars.php?cookietab=1" _type="link" _text="{tr}Admin{/tr}" _icon_name="admin"}
        {/if}

{* avoid Add Event being shown if no calendar is displayed *}
        {if $tiki_p_add_events eq 'y'}
            {button href="tiki-calendar_edit_item.php" _type="link" _text="{tr}Add Event{/tr}" _icon_name="create"}
        {/if}

        {if $viewlist eq 'list'}
            {capture name=href}?viewlist=table{if $smarty.request.todate}&amp;todate={$smarty.request.todate}{/if}{/capture}
            {button href=$smarty.capture.href _text="{tr}Calendar View{/tr}" _icon_name="calendar"}
        {else}
            {capture name=href}?viewlist=list{if $smarty.request.todate}&amp;todate={$smarty.request.todate}{/if}{/capture}
            {button href=$smarty.capture.href _text="{tr}List View{/tr}" _icon_name="list"}
        {/if}

        {if count($listcals) >= 1}
            {button href="#" _onclick="toggle('filtercal');return false;" _text="{tr}Calendars{/tr}" _icon_name="eye"}
            <div class="d-inline-block">
                <form class="card" id="filtercal" method="get" action="{$myurl}" name="f" style="display:none;">
                    <div class="card-header caltitle py-1 px-2">
                        <strong>{tr}Calendars{/tr}</strong>
                        <button type="button" class="btn-close"  onclick="toggle('filtercal')" aria-hidden="true"></button>
                    </div>
                    <ul class="list-group list-group-flush list-unstyled mt-2">
                        <li class="caltoggle">
                            {select_all checkbox_names='calIds[]' label="{tr}Check / Uncheck All{/tr}"}
                        </li>
                        {foreach item=k from=$listcals}
                            <li class="calcheckbox">
                                <input type="checkbox" name="calIds[]" value="{$k|escape}" id="groupcal_{$k}" {if $thiscal.$k}checked="checked"{/if}>
                                <label for="groupcal_{$k}" class="calId{$k}">{$infocals.$k.name|escape} (id #{$k})</label>
                            </li>
                        {/foreach}
                        <li class="calinput">
                            <input type="hidden" name="todate" value="{$focusdate}">
                            <input type="submit" class="btn btn-primary btn-sm" name="refresh" value="{tr}Refresh{/tr}">
                        </li>
                    </ul>
                </form>
                {jq}// handle calendar switcher form submit
$("#filtercal").submit(function () {
    if ($("input[type=checkbox]:not(#clickall):not(:checked)", this).length === 0) {
        location.href = (jqueryTiki.sefurl ? "calendar" : "tiki-calendar.php") + "?allCals=y";
        return false;
    } else {
        return true;
    }
});
{/jq}
            </div>

            {if $tiki_p_view_events eq 'y' and $prefs.calendar_export eq 'y'}
                {button href="#" _onclick="toggle('exportcal');return false;" _text="{tr}Export{/tr}" _icon_name="export"}
                <div class="d-inline-block">
                    <form id="exportcal" class="card" method="post" action="{$exportUrl}" name="f" style="display:none;">
                        <input type="hidden" name="export" value="y">
                        <div class="card-header caltitle py-1 px-2">{tr}Export calendars{/tr}</div>
                        <div class="caltoggle">
                            {select_all checkbox_names='calendarIds[]' label="{tr}Check / Uncheck All{/tr}"}
                        </div>
                        {foreach item=k from=$listcals}
                            <div class="calcheckbox">
                                <input type="checkbox" name="calendarIds[]" value="{$k|escape}" id="groupexcal_{$k}" {if $thiscal.$k}checked="checked"{/if}>
                                <label for="groupexcal_{$k}" class="calId{$k}">{$infocals.$k.name|escape}</label>
                            </div>
                        {/foreach}
                        <div class="calcheckbox">
                            <a href="{$iCalAdvParamsUrl}">{tr}advanced parameters{/tr}</a>
                        </div>
                        <div class="calinput">
                            <input type="submit" class="btn btn-primary btn-sm" name="ical" value="{tr}Export as iCal{/tr}">
                            <input type="submit" class="btn btn-primary btn-sm" name="csv" value="{tr}Export as CSV{/tr}">
                        </div>
                    </form>
                </div>
            {/if}

            {if count($thiscal)}
                <div id="configlinks" class="mb-3 text-end">
                    {assign var='maxCalsForButton' value=20}
                    {if count($checkedCals) > $maxCalsForButton}<select size="5">{/if}
                    {foreach item=k from=$listcals name=listc}
                        {if $thiscal.$k}
                            {assign var=thiscustombgcolor value=$infocals.$k.custombgcolor}
                            {assign var=thiscustomfgcolor value=$infocals.$k.customfgcolor}
                            {assign var=thisinfocalsname value=$infocals.$k.name|escape}
                            {if count($checkedCals) > $maxCalsForButton}
                                <option style="background:#{$thiscustombgcolor};color:#{$thiscustomfgcolor};" onclick="toggle('filtercal')">{$thisinfocalsname}</option>
                            {else}
                                {button href="{$k|sefurl:'calendar'}" _style="background:#$thiscustombgcolor;color:#$thiscustomfgcolor;border:1px solid #$thiscustomfgcolor;" _text="$thisinfocalsname" _class='btn btn-sm mr-2'}
                            {/if}
                        {/if}
                    {/foreach}
                    {if count($checkedCals) > $maxCalsForButton}</select>{/if}
                </div>
            {else}
                {button href="" _style="background-color:#fff;padding:0 4px;" _text="{tr}None{/tr}"}
            {/if}
        {/if}
    </div>
    {* show jscalendar if set *}
    {if $prefs.feature_jscalendar eq 'y'}
        <div class="jscalrow" style="display: inline-block">
            <form action="{$myurl}" method="post" name="f">
                {jscalendar date="$focusdate" id="trig" goto="$jscal_url" align="Bc"}
            </form>
        </div>
    {/if}

    {if $user and $prefs.feature_user_watches eq 'y' and isset($category_watched) and $category_watched eq 'y'}
    <div class="categbar">
        {tr}Watched by categories:{/tr}
        {section name=i loop=$watching_categories}
            {assign var=thiswatchingcateg value=$watching_categories[i].categId}
            {button href="tiki-browse_categories.php?parentId=$thiswatchingcateg" _text=$watching_categories[i].name|escape}
            &nbsp;
        {/section}
    </div>
    {/if}

    {if $prefs.display_12hr_clock eq 'y'}
        {assign var="timeFormat" value=true}
    {else}
        {assign var="timeFormat" value=false}
    {/if}
    {if $viewlist eq 'list'}
        {include file='tiki-calendar_listmode.tpl'}
    {else}
        {jq}
            var year = {{$viewyear}};
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                themeSystem: 'bootstrap5',
                eventTimeFormat: {
                  hour: 'numeric',
                  minute: '2-digit',
                  meridiem: '{{$timeFormat}}',
                  hour12: '{{$timeFormat}}'
                },
                timeZone: '{{$prefs.display_timezone}}',
                locale: '{{$prefs.language}}',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'year,semester,quarter,dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: true,
                selectable:true,
                events: 'tiki-calendar_json.php',
                slotMinTime: '{{$minHourOfDay}}',
                slotMaxTime: '{{$maxHourOfDay}}',
                buttonText: {
                    today: "{tr}today{/tr}",
                    year: "{tr}year{/tr}",
                    semester: "{tr}semester{/tr}",
                    quarter: "{tr}quarter{/tr}",
                    month: "{tr}month{/tr}",
                    week: "{tr}week{/tr}",
                    day: "{tr}day{/tr}"
                },
                allDayText: '{tr}all-day{/tr}',
                firstDay: {{$firstDayofWeek}},
                slotDuration: '00:{{if $prefs.calendar_timespan|count_characters == 1}}0{{/if}}{{$prefs.calendar_timespan}}',
                initialView: {{if $prefs.calendar_view_mode === 'week'}}'timeGridWeek'{{elseif $prefs.calendar_view_mode === 'day'}}'timeGridDay'{{elseif $prefs.calendar_view_mode === 'month'}}'dayGridMonth'{{else}}'{{$prefs.calendar_view_mode}}'{{/if}},
                views: {
                    quarter: {
                        type: 'dayGrid',
                        duration: { months: 3 },
                        buttonText: 'quarter',
                        dayCellContent: function(dayCell) {
                            return moment(dayCell.date).format('M/D');
                        },
                        visibleRange: function(currentDate) {
                            return {
                                start: moment(currentDate).startOf('month').toDate(),
                                end: moment(currentDate).add('2', 'months').endOf('month').toDate()
                            };
                        }
                    },
                    semester: {
                        type: 'dayGrid',
                        duration: { months: 6 },
                        buttonText: 'semester',
                        dayCellContent: function(dayCell) {
                            return moment(dayCell.date).format('M/D');
                        },
                        visibleRange: function(currentDate) {
                            return {
                                start: moment(currentDate).startOf('month').toDate(),
                                end: moment(currentDate).add('5', 'months').endOf('month').toDate()
                            };
                        }
                    },
                    year: {
                        type: 'dayGrid',
                        buttonText: '{tr}year{/tr}',
                        dayCellContent: function($x) {
                            return moment($x.date).format('M/D');
                        },
                        visibleRange: function(currentDate) {
                            return {
                                start: moment(currentDate).startOf('year').toDate(),
                                end: moment(currentDate).startOf('year').add('11', 'months').endOf('month').toDate()
                            };
                        }
                    }
                },
                eventDataTransform: function(event) {
                    if (event.allDay) {
                        // show all day events as including the end date day
                        event.end = moment(event.end).add(1, 'days').format('YYYY-MM-DD HH:mm:SSZ')
                    }
                    return event;
                },
                eventDidMount: function(arg) {
                    var event = arg.event;
                    var element = $(arg.el);
                    var dayGrid = $('.fc-daygrid-event').length;
                    if (dayGrid > 0) {
                        var backgroundColor = event._def.ui.backgroundColor;
                        var textColor = event._def.ui.textColor;
                        var eventDotElement = element.find('.fc-daygrid-event-dot'), defaultBackgroundColor;
                        if (eventDotElement.length === 0) {
                            eventDotElement = element;
                        }
                        var styleDot = getComputedStyle(eventDotElement[0]);
                        var borderCol = styleDot.border || styleDot.borderColor || styleDot.borderTopColor || styleDot.borderTopColor;
                        var matches = String(borderCol).match(/(rgb\(\d+,\s*\d+,\s*\d+\))/i) || ["rgb(55, 136, 216)"];
                        var defaultBackgroundColor = matches[0];
                        if (eventDotElement !== element) {
                            $(eventDotElement[0]).remove();
                        }
                        var titleElement = element.find('.fc-event-title');

                        var styleElement = getComputedStyle(titleElement[0]);
                        var defaultTextColor = styleElement.color;
                        if (backgroundColor == '#') {
                            backgroundColor = defaultBackgroundColor
                        }
                        if (textColor == '#') {
                            textColor = defaultTextColor;
                        }
                        $(element).attr('style', 'background-color: ' + backgroundColor + '; border: 1px solid ' + textColor);
                        $(element).children('.fc-event-time').attr('style', 'color: ' + textColor);
                        $(element).children('.fc-event-title').attr('style', 'color: ' + textColor);
                    }
                    element.attr('title', event.title);
                    element.data('content', event.extendedProps.description);
                    element.popover({ trigger: 'hover', html: true, 'container': 'body', placement: 'bottom'});
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    let $this = $(info.el).tikiModal(" ");
                    var event = info.event;
                    if (event.url) {
                        $.ajax({
                            dataType: 'html',
                            url: event.url + '&fullcalendar=y&modal=1',
                            success: function(data){
                                var $dialog = $('#calendar_dialog');
                                var $options = {
                                    backdrop:'static',
                                    focus:true
                                };
                                $('#calendar_dialog_content', $dialog ).html(data);
                                $dialog.find(".modal-dialog").addClass("modal-lg");
                                $dialog.appendTo('body');
                                $this.tikiModal();
                                var tikiModalShow=new bootstrap.Modal($dialog,$options);
                                tikiModalShow.show();
                            }
                        });
                    }
                },
                dateClick: function(info) {
                    var timestamp=Math.floor(new Date(info.dateStr).getTime())/1000;
                    let $this = $(info.dayEl).tikiModal(" "); //the html reference of the clicked date is no longer info.el but info.dayEl
                    $.ajax({
                        dataType: 'html',
                        url: 'tiki-calendar_edit_item.php?fullcalendar=y&todate=' + timestamp + '&tzoffset=' + (new Date()).getTimezoneOffset() + '&modal=1',
                        success: function(data) {
                            var $dialog = $('#calendar_dialog');
                            var $options = {
                                backdrop:'static',
                                focus:true
                            };
                            $('#calendar_dialog_content', $dialog ).html(data);
                            $dialog.find(".modal-dialog").addClass("modal-lg");
                            $dialog.appendTo('body');
                            $this.tikiModal();
                            var tikiModalShow=new bootstrap.Modal($dialog,$options);
                            tikiModalShow.show();
                        }
                    });
                },
                eventResize: function(info) {
                    $.post($.service('calendar', 'resize'), {
                        calitemId: info.event.id,
                        delta: info.endDelta
                    });
                },
                eventDrop: function(info) {
                    $.post($.service('calendar', 'move'), {
                        calitemId: info.event.id,
                        delta: info.delta
                    });
                },
                height: 'auto'
            });
            calendar.render();
            {{if $prefs.print_pdf_from_url neq 'none'}addFullCalendarPrint('#calendar', '#calendar-pdf-btn', calendar);{/if}}
        {/jq}
    {/if}
    {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
        <a id="calendar-pdf-btn"  href="#" class="text-end d-none">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
    {/if}
    <div id="test"></div>
    <style type='text/css'>
        /* Fix pb with DatePicker */
        .ui-datepicker {
            z-index:9999 !important;
        }
        .fc .fc-scrollgrid, .fc .fc-scrollgrid table,
        .fc .fc-daygrid-body {
            width: 100% !important;
        }
        .fc-daygrid-event-harness {
            border-radius: 4px;
            margin: 0px 3px 0px;
        }
        .fc-event {
            display: block;
            white-space: break-spaces;
        }
        .fc-daygrid-day-events .fc-event-time {
            color: #ffffff;
            font-weight: bold;
        }
        .fc-daygrid-day-events .fc-event-title {
            color: #ffffff;
            font-weight: normal;
        }
        .fc-timegrid-event .fc-event-time {
            font-weight: bold;
        }
        .fc-timegrid-event .fc-event-title {
            font-weight: normal;
        }
        @media only screen and (max-width: 767px) {
            .fc-header-toolbar {
                display: block !important;
            }
            .fc-header-toolbar .fc-toolbar-chunk .btn-group .btn {
                padding: 0.375rem 0.1rem;
            }
        }
        @media print {
            .fc .fc-daygrid-day-top {
                border-bottom: 1px solid #dee2e6;
            }
        }
    </style>
    <div id='calendar'></div>

    <!--<div id='calendar_dialog'></div>-->

    <div id="calendar_dialog" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content" id="calendar_dialog_content">
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <p>&nbsp;</p>
</div>
{if $prefs.feature_jscalendar eq 'y' and $prefs.javascript_enabled eq 'y'}
    {js_insert_icon type="jscalendar"}
{/if}
