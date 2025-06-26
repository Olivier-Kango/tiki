{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <form method="post" action="tiki-view_tracker_item.php?trackerId={$trackerId}&itemId={$itemId}">
        {ticket}
        <div class="modal-body">
            <div class="mb-3">
                <label for="master_password" class="form-label">{tr}Master Password{/tr}</label>
                <input type="password" name="master_password" id="master_password" class="form-control" autofocus>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{tr}Cancel{/tr}</button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-unlock"></i> {tr}Unlock{/tr}
            </button>
        </div>
    </form>
{/block}

