{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form action="{service controller='calendar_availability' action='create'}" method="post">
        {include file="calendar_availability/form.tpl"}
        <div class="submit">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}" onclick="needToConfirm=false">
        </div>
    </form>
{/block}
