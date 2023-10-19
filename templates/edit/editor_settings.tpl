{* modal used by JavaScript generated in smarty_block_textarea *}
<div class="d-none" id="editor-settings">
    <input type="hidden" name="page" value="{$page}">
    {if $prefs.feature_wysiwyg eq 'y' and $prefs.wysiwyg_optional eq 'y'}
        <div class="mb-3">
            <label for="editor-select" class="form-label">{tr}Editor Type{/tr}</label>
            <select class="form-select noselect2" aria-label="{tr}Plain or WYSIWYG{/tr}" id="editor-select">
                <option value="plain">{tr}Plain{/tr}</option>
                <option value="wysiwyg">{tr}WYSIWYG{/tr}</option>
            </select>
        </div>
    {/if}
    <div class="mb-3">
        <label for="syntax-select" class="form-label">{tr}Syntax{/tr}</label>
        <select class="form-select noselect2" aria-label="{tr}Tiki or Markdown{/tr}" id="syntax-select">
            <option value="tiki">{tr}Tiki{/tr}</option>
            <option value="markdown">{tr}Markdown{/tr}</option>
        </select>
    </div>
</div>
