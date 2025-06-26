{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <form method="post" action="tiki-view_tracker_item.php?trackerId={$trackerId}&itemId={$itemId}">
        {ticket}
            <div class="mb-3">
                <label for="master_password" class="form-label">{tr}Master Password{/tr}</label>
                <input type="password" name="master_password" id="master_password" class="form-control" autofocus>
            </div>
            <div class="submit">
                <input type="submit" class="btn btn-primary" value="{tr}Unlock{/tr}" onclick="needToConfirm=false;">
            </div>
    </form>
{/block}

