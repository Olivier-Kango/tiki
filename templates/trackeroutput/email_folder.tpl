<div id="display_f{$field.fieldId|escape}" class="email-folder-field display_f{$field.fieldId|escape}">
    {if $data.count eq 0}
        {tr}Emails can be copied or moved here via the Webmail interface.{/tr}
    {elseif $field.options_map.useFolders}
        {foreach from=$data.emails key=folder item=emails}
            <div><a href="#" class="email-folder-switcher" data-folder="{$folder}">{$field.options_map["{$folder}Name"]}</a></div>
            <div class="email-folder-contents folder-{$folder}" style="display: none">
                {include file='trackeroutput/email_single_folder.tpl' emails=$emails}
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

