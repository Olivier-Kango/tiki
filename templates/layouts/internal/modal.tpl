<!DOCTYPE html>
<div class="modal-content-storage">
    {if ! isset($noheader) || $noheader !== 'y'}
        <div class="title">{$title|escape}</div>
    {/if}
    <div class="body">
        {block name=content}{/block}
        {if $headerlib}
            {$headerlib->output_js_config()}
            {$headerlib->output_js_files()}
            {$headerlib->output_js()}
        {/if}
        {if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
            {debug}
        {/if}
    </div>
</div>
