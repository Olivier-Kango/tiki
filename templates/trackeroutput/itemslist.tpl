{* Note that when there in only one item it needs to be unformatted as it is often used inline in pretty trackers *}
<div class="itemslist-field display_f{$field.fieldId|escape}"
        data-trackerid="{$field.trackerId}" data-itemid="{$data.parentItemId}" data-fieldId="{$field.fieldId}" data-listmode="{$context.list_mode}">
    {if $data.num > 1}
        <ul class="list-unstyled">
            {foreach from=$data.items key=id item=label}
                <li class="d-flex justify-content-between">
                    <span class="d-flex justify-content-start">
                        {if $data.links}
                            {object_link type=trackeritem id=$id title=$label}
                        {elseif $data.raw}
                            {$label}
                        {else}
                            {$label|escape}
                        {/if}
                    </span>
                    <span class="d-flex justify-content-end">
                        {if $field.options_map.editItem || $field.options_map.deleteItem}
                            {if $field.options_map.editItem && $data.itemPermissions[$id].can_modify}
                                <a class="itemslist-btn px-1" href="{service controller=tracker action=update_item trackerId=$field.options_map.trackerId itemId=$id}">
                                    {icon name="edit" ititle='{tr}Edit item{/tr}'}
                                </a>
                            {/if}
                            {if $field.options_map.deleteItem && $data.itemPermissions[$id].can_remove}
                                <a class="text-danger itemslist-btn px-1" href="{service controller=tracker action=remove_item trackerId=$field.options_map.trackerId itemId=$id}">
                                    {icon name="remove" ititle='{tr}Delete item{/tr}'}
                                </a>
                            {/if}
                        {/if}
                    </span>
                </li>
            {/foreach}
        </ul>
    {elseif $data.num eq 1}{strip}
        {foreach from=$data.items key=id item=label}
            {if $data.links}
                {object_link type=trackeritem id=$id title=$label}
            {elseif $data.raw}
                {$label}
            {else}
                {$label|escape}
            {/if}
        {/foreach}
        {if $field.options_map.editItem && $data.itemPermissions[$id].can_modify}
            <a class="itemslist-btn px-1" href="{service controller=tracker action=update_item trackerId=$field.options_map.trackerId itemId=$id}">
                {icon name="edit" ititle='{tr}Edit item{/tr}'}
            </a>
        {/if}
        {if $field.options_map.deleteItem && $data.itemPermissions[$id].can_remove}
            <a class="text-danger itemslist-btn px-1" href="{service controller=tracker action=remove_item trackerId=$field.options_map.trackerId itemId=$id}">
                {icon name="remove" ititle='{tr}Delete item{/tr}'}
            </a>
        {/if}
    {/strip}{/if}
    {if $data.addItemText}
        {$forcedParam[$data.otherFieldPermName]=$data.parentItemId}
        <a class="float-left itemslist-btn" href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId forced=$forcedParam}">
            {icon name='create' _menu_text='y' _menu_icon='y' ititle="{$data.addItemText}" alt="{$data.addItemText}"}
        </a>
        {jq}
            // a custom handler to reload the data in this field on success
            $(document).on("click", "a.itemslist-btn", $.clickModal({
                button: this,
                backdrop: "static",
                success: function (data) {
                    let $itemsList = $(this).closest(".itemslist-field");
                    let url = $.service("tracker", "fetch_item_field", {
                        trackerId: $itemsList.data("trackerid"),
                        itemId: $itemsList.data("itemid"),
                        fieldId: $itemsList.data("fieldid"),
                        listMode: $itemsList.data("list_mode"),
                        mode: "output"
                    })
                    $.closeModal();

                    $itemsList.tikiModal(tr("Loading...")).load(url.replace(/&amp;/g, "&"), function () {$itemsList.tikiModal();});
                }
            }));
        {/jq}
    {/if}
</div>