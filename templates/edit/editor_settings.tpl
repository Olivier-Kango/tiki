{* modal used by JavaScript generated in smarty_block_textarea *}
<div class="modal fade" id="editor-settings" aria-hidden="true" aria-labelledby="editor-settings-title" tabindex="-1" style="z-index=2000">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="editor-settings-title">{tr}Editor Settings{/tr}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{tr}Close{/tr}"></button>
            </div>
            <div class="modal-body">
                {if $prefs.feature_wysiwyg eq 'y' and $prefs.wysiwyg_optional eq 'y'}
                    <div class="mb-3">
                        <label for="editor-select" class="form-label">{tr}Editor Type{/tr}</label>
                        <select class="form-select" aria-label="{tr}Plain or WYSIWYG{/tr}" id="editor-select">
                            <option value="plain">{tr}Plain{/tr}</option>
                            <option value="wysiwyg">{tr}WYSIWYG{/tr}</option>
                        </select>
                    </div>
                {/if}
                <div class="mb-3">
                    <label for="syntax-select" class="form-label">{tr}Syntax{/tr}</label>
                    <select class="form-select" aria-label="{tr}Tiki or Markdown{/tr}" id="syntax-select">
                        <option value="tiki">{tr}Tiki{/tr}</option>
                        <option value="markdown">{tr}Markdown{/tr}</option>
                    </select>
                </div>
                <div class="mb-5"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">{tr}Save{/tr}</button>
            </div>
        </div>
    </div>
</div>
