{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    <form action="{service controller='edit' action='convert_syntax'}" method="post" id="editor-settings" data-area-id="{$domId}" class="no-ajax">
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
        {if $prefs.markdown_enabled eq 'y'}
            <div class="mb-3">
                <label for="syntax-select" class="form-label">{tr}Syntax{/tr}</label>
                <select class="form-select noselect2" aria-label="{tr}Tiki or Markdown{/tr}" id="syntax-select">
                    <option value="tiki">{tr}Tiki{/tr}</option>
                    <option value="markdown">{tr}Markdown{/tr}</option>
                </select>
            </div>
        {/if}
        <div class="submit">
            <button type="submit" class="btn btn-primary">{tr}Save{/tr}</button>
        </div>
    </form>

    {jq}

    const $form = $("#{{$domId}}").parents('form');
    const $editorSelect = $("#editor-select");
    const $syntaxSelect = $("#syntax-select");
    const $wysiwygInput = $form.find("input[name=wysiwyg]");
    const $syntaxInput = $form.find("input[name=syntax]");
    
    const initialEditorType = $wysiwygInput.val() === "y" ? "wysiwyg" : "plain";
    const initialSyntax = $syntaxInput.val();

    $editorSelect.val(initialEditorType).trigger("change");
    $syntaxSelect.val(initialSyntax).trigger("change");

    {/jq}
{/block}
