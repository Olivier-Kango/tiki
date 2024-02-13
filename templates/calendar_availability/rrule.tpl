{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form action="{service controller="calendar_availability" action="rrule_save"}" method="POST" class="no-ajax rrule-form">
        <input type="hidden" name="uid" value="{$uid|escape}">
        {include file='calendar/recurrence.tpl'}
        <div class="submit">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Update{/tr}" onclick="needToConfirm=false">
        </div>
    </form>
{/block}
