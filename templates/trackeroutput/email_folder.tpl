<div id="display_f{$field.fieldId|escape}" class="email-folder-field display_f{$field.fieldId|escape}">
    <a href="{$data.compose_path}">{tr}Compose{/tr}</a>
    {if $data.count eq 0}
        {tr}Emails can be copied or moved here via the Webmail interface.{/tr}
    {elseif $field.options_map.useFolders}
        {foreach from=$data.folders key=folder item=folderName}
            <div><a href="#" class="email-folder-switcher" data-folder="{$folder}">{$folderName} ({$data.emails[$folder]|count})</a></div>
            <div class="email-folder-contents folder-{$folder}" style="display: {if in_array($folder, $data.opened)}block{else}none{/if}">
                {include file='trackeroutput/email_single_folder.tpl' emails=$data.emails[$folder]}
            </div>
        {/foreach}
        {jq}
            $(".email-folder-switcher").on('click', function(e){
                e.preventDefault();
                $(this).closest('.email-folder-field').find(".email-folder-contents.folder-"+$(this).data('folder')).toggle();
                return false;
            });
        {/jq}
    {else}
        {include file='trackeroutput/email_single_folder.tpl' emails=$data.emails.inbox}
    {/if}
</div>

