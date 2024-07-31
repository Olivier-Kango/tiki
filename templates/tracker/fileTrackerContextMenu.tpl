{strip}
    {if $file.filetype|truncate:6:'':true eq 'image/'}
        <a href="{$file.fileId|sefurl:display}" data-box="box-{$file.fileId}">
            {icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
        </a>
    {/if}
    <a href="{$file.fileId|sefurl:display}">
        {icon name='eye' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Browser display{/tr} ({tr}Raw{/tr} / {tr}Download{/tr})"}
    </a>
    {if $prefs.fgal_display_properties eq 'y'}
        <a href="tiki-upload_file.php?fileId={$file.fileId}">
            {icon _menu_text=$menu_text _menu_icon=$menu_icon name='edit' alt="{tr}Edit properties{/tr}"}
        </a>
        <a href="tiki-list_file_gallery.php?fileId={$file.fileId}&action=refresh_metadata" onclick="confirmPopup('{tr}Refresh metadata?{/tr}', '{ticket mode=get}')">
            {icon _menu_text=$menu_text _menu_icon=$menu_icon name='tag' alt="{tr}Refresh metadata{/tr}"}
        </a>
        {if $view != 'page'}
            <a href="tiki-list_file_gallery.php?fileId={$file.fileId}&view=page">
                {icon _menu_text=$menu_text _menu_icon=$menu_icon name='textfile' alt="{tr}Page view{/tr}"}
            </a>
        {/if}
    {/if}
    {if $canAssignPerms}
        <div class="iconmenu">
            {permission_link mode=text type="file gallery" permType="file galleries" id=$file.fileId title=$file.name}
        </div>
    {/if}
    {if $prefs.feature_webdav eq 'y'}
        {assign var=virtual_path value=$file.fileId|virtual_path}

        {assign var=link_label value=($prefs.feature_file_galleries_save_draft eq 'y') ? "Open your draft in WebDAV" : "Open in WebDAV"}
        {self_link _icon_name="file-archive-open" _menu_text=$menu_text _menu_icon=$menu_icon _onclick="javascript:open_webdav('$virtual_path')" _noauto="y" _ajax="n"}
            {tr}{$link_label}{/tr}
        {/self_link}
    {/if}
{/strip}
