{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    <div class="d-flex flex-column gap-3">
        {foreach from=$files item=file}
            <div class="d-flex align-items-center p-2 gap-2 rounded bg-light file-row text-secondary" role="button" data-file-id="{$file.fileId}">
                {icon name="file"}
                <div class="filename">{$file.name}</div>
            </div>
        {foreachelse}
            <div class="text-center text-muted bg-light rounded-pill p-5 w-50 mx-auto">
                {icon name="folder-open" size="3x"}
                <div>{tr}No files found{/tr}</div>
            </div>
        {/foreach}
    </div>
    <style>
    .file-row:hover {
        background-color: var(--bs-secondary-bg-subtle);
    }
    </style>
    <script>
        $('.file-row').on('click', function() {
            const areaId = '{$areaId}';
            {literal}
                insertAt(areaId, `((${$(this).data('file-id')}|file))`);
                $.closeModal();
            {/literal}
        });
    </script>
{/block}
