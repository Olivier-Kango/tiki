{capture name=actions}{strip}
    {if $tiki_p_view_peertube eq 'y' or $tiki_p_admin_peertube eq 'y' or $tiki_p_admin eq 'y'}
        <a href="tiki-peertube_video.php?videoId={$item->uuid}" title="{tr}View{/tr}">
            {icon name="view" _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
        </a>
    {/if}
    {if $tiki_p_download_peertube eq 'y' or $tiki_p_admin_peertube eq 'y' or $tiki_p_admin eq 'y'}
        <a href="{$item->downloadUrl}" title="{tr}Download{/tr}">
            {icon name="download" _menu_text='y' _menu_icon='y' alt="{tr}Download{/tr}"}
        </a>
    {/if}
    {if $tiki_p_edit_peertube eq 'y' or $tiki_p_admin_peertube eq 'y' or $tiki_p_admin eq 'y'}
        <a href="tiki-peertube_video.php?videoId={$item->uuid}&action=edit" title="{tr}Change Details{/tr}">
            {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Change Details{/tr}"}
        </a>
    {/if}
    {if $tiki_p_delete_peertube eq 'y' or $tiki_p_admin_peertube eq 'y' or $tiki_p_admin eq 'y'}
        <a href="tiki-peertube_video.php?videoId={$item->uuid}&action=delete" class="iconmenu text-danger" title="{tr}Delete{/tr}">
            {icon name="delete" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
        </a>
    {/if}
{/strip}{/capture}
