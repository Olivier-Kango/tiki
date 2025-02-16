{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$h5p_title|escape}{/title}
{/block}

{block name="content"}
    <form class="content-form no-ajax tiki-h5p-edit" enctype="multipart/form-data" action="{service controller='h5p' action='edit' fileId=$fileId}" method="post" accept-charset="UTF-8">
        <input type="hidden" name="library" value="{$library|escape}">
        <input type="hidden" name="parameters" value="{$parameters|escape}">
        <input type="hidden" name="index" value="{$index|escape}">
        <input type="hidden" name="page" value="{$page|escape}">
        {ticket mode='confirm'}
        <div>
            <div class="form-item form-type-textfield form-item-title">
                <label for="edit-title">Title
                    <span class="form-required" title="This field is required.">*</span></label>
                <input type="text" id="edit-title" name="title" value="{$h5p_title|escape}" size="60" maxlength="128" class="form-control required">
            </div>
            <br>
            <div>
                <div class="h5p-create"><div class="h5p-editor">{$loading}</div></div>
            </div>
            <br>
            <div class="form-actions form-wrapper submit" id="edit-actions">
                {if $fileId}
                    <input type="submit" id="edit-delete" name="op" value="Delete" class="btn btn-outline-danger confirm">
                {/if}
                <input type="submit" id="edit-submit" name="op" value="Save" class="btn btn-primary">
            </div>
            <br>
        </div>
    </form>
{/block}
