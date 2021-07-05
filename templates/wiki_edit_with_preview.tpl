<div class="edit-preview-zone">
    {tabset name='preview_'|cat:$textarea_id}
        {tab name="{tr}Edit{/tr}"}
            {$edit_form}
        {/tab}
        {tab name="{tr}Preview{/tr}"}
            <div id="preview_div_{$textarea_id}" class="textarea-preview card card-body"></div>
        {/tab}
    {/tabset}
</div>
