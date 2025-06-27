{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
{if $unlock_error}
    <div class="alert alert-danger">
        {tr}Invalid password. Please try again.{/tr}
    </div>
{/if}

{if $unlock_success}
    <div class="alert alert-success">
        {tr}Item unlocked successfully. You can now edit it.{/tr}
        <script>
            setTimeout(function() {
                $('#bootstrap-modal').modal('hide');
                window.location.reload();
            }, 2000);
        </script>
    </div>
{else}
    {* Message d'information *}
    <div class="alert alert-info">
        {tr}This item is locked. Please enter the master password to unlock it for editing.{/tr}
    </div>
    
    {* Formulaire d'unlock *}
    <form method="post" action="tiki-view_tracker_item.php?trackerId={$trackerId}&itemId={$itemId}">
        {ticket}
            <div class="mb-3">
                <label for="master_password" class="form-label">{tr}Master Password{/tr}</label>
                <input type="password" name="master_password" id="master_password" class="form-control" autofocus required placeholder="{tr}Enter master password{/tr}">
            </div>
            <div class="submit">
                <input type="submit" class="btn btn-primary" value="{tr}Unlock{/tr}" onclick="needToConfirm=false;">
            </div>
    </form>
        {* Note d'information *}
    <div class="mt-3 small text-muted">
        {tr}Note: Unlocking will be valid for 1 hour or until you logout.{/tr}
    </div>
{/if}
{/block}

