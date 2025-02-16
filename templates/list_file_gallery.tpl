{if (! empty($tree)
    && $tiki_p_list_file_galleries != 'n'
    && $fgal_options.show_explorer.value eq 'y'
    && $tiki_p_view_fgal_explorer eq 'y' )
    or ( !empty($gallery_path) && $fgal_options.show_path.value eq 'y' && $tiki_p_view_fgal_path eq 'y' )
}
    <div class="fgal_top_bar mb-3">
        {if !empty($tree) && $tiki_p_list_file_galleries != 'n' && $fgal_options.show_explorer.value eq 'y' && $tiki_p_view_fgal_explorer eq 'y'}
            <div id="fgalexplorer_close" style="float:left; vertical-align:middle; display:{if !isset($smarty.session.tiki_cookie_jar.show_fgalexplorer) or $smarty.session.tiki_cookie_jar.show_fgalexplorer eq 'y'}none{else}inline{/if};">
                <a href="#"
                    class="tips"
                    title=":{tr}Show Tree{/tr}"
                    aria-label="{tr}Show Tree{/tr}"
                    onclick="flip('fgalexplorer','');hide('fgalexplorer_close',false);show('fgalexplorer_open',false);return false;">
                    {icon name='file-archive'}
                </a>
            </div>
            <div id="fgalexplorer_open" style="float:left; vertical-align:middle; display:{if isset($smarty.session.tiki_cookie_jar.show_fgalexplorer) and $smarty.session.tiki_cookie_jar.show_fgalexplorer neq 'y'}none{else}inline{/if};">
                <a href="#"
                    class="tips"
                    title=":{tr}Hide Tree{/tr}"
                    aria-label="{tr}Hide Tree{/tr}"
                    onclick="flip('fgalexplorer','');hide('fgalexplorer_open',false);show('fgalexplorer_close',false);return false;">
                    {icon name='file-archive-open'}
                </a>
            </div>
        {/if}
    </div>
{/if}
<div class="row">
    {if ! empty($tree) && $tiki_p_list_file_galleries != 'n'
        && $fgal_options.show_explorer.value eq 'y' && $tiki_p_view_fgal_explorer eq 'y' && $view neq 'page'}
        <div class="col-sm-3 fgalexplorer" id="fgalexplorer" style="{if ( isset($smarty.session.tiki_cookie_jar.show_fgalexplorer) and $smarty.session.tiki_cookie_jar.show_fgalexplorer neq 'y') and ( ! isset($smarty.request.show_fgalexplorer) or $smarty.request.show_fgalexplorer neq 'y' )}display:none;{/if}">
            {$tree}
        </div>
        <div class="col-sm-9 fgallisting explorerHidden">
    {else}
        <div class="col-sm-12 fgallisting explorerDisplayed">
    {/if}
    <div>
        {if $maxRecords > 20 and $cant > $maxRecords}
            <div class="clearboth" style="margin-bottom: 3px;">
                {pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
            </div>
        {/if}
        <form name="fgalformid" id="fgalform" method="post" action="{if !empty($filegals_manager)}{query _type='relative' filegals_manager=$filegals_manager|escape}{else}{query _type='relative'}{/if}" enctype="multipart/form-data">
            {ticket}
            <input type="hidden" name="galleryId" value="{$gal_info.galleryId|escape}">
            <input type="hidden" name="find" value="{$find|escape}">
            {if !empty($show_details)}<input type="hidden" name="show_details" value="{$show_details}">{/if}
            {if $prefs.fgal_asynchronous_indexing eq 'y'}<input type="hidden" name="fast" value="y">{/if}
            {if !empty($sort_mode)}<input type="hidden" name="sort_mode" value="{$sort_mode|escape}">{/if}
            {if isset($file_info)}<input type="hidden" name="fileId" value="{$file_info.fileId|escape}">{/if}
            {if isset($page)}<input type="hidden" name="page" value="{$page|escape}">{/if}
            {if isset($view)}<input type="hidden" name="view" value="{$view|escape}">{/if}
            {assign var=nbCols value=0}
            {assign var=other_columns value=''}
            {assign var=other_columns_selected value=''}
            {if $view eq 'browse' or $view eq 'page'}
                {assign var=show_infos value='y'}
                {if $view eq 'page'}
                    {include file='fgal_view_page.tpl'}
                {else}
                    {include file='browse_file_gallery.tpl'}
                {/if}
            {else}
                {assign var=show_infos value='n'}
                {include file='list_file_gallery_content.tpl'}
            {/if}
            {if (isset($files) && $files
                and $gal_info.show_checked neq 'n'
                and $prefs.fgal_checked eq 'y'
                and ($tiki_p_admin_file_galleries eq 'y' or $tiki_p_upload_files eq 'y' or $tiki_p_assign_perm_file_gallery eq 'y')
                and ($prefs.fgal_show_thumbactions eq 'y' or $show_details eq 'y' or $view neq 'browse')
                and $view neq 'page'
            )}
                <div id="sel">
                    {if $tiki_p_admin_file_galleries eq 'y'
                        or $tiki_p_remove_files eq 'y'
                        or !isset($file_info)
                        or $tiki_p_admin_file_galleries eq 'y'
                        or $prefs.fgal_display_zip_option eq 'y'
                        or $tiki_p_assign_perm_file_gallery eq 'y'
                    }
                        <div class="input-group col-sm-12">
                            <select name="fgal_actions" class="form-select" aria-label="{tr}Select action to perform with checked...{/tr}">
                                <option value="" selected="selected">
                                    {tr}Select action to perform with checked...{/tr}
                                </option>
                                {if $tiki_p_assign_perm_file_gallery eq 'y'}
                                    <option value="permsel_x">
                                        {tr}Assign permissions to file galleries{/tr}
                                    </option>
                                {/if}
                                {if $tiki_p_admin_file_galleries eq 'y' or $tiki_p_remove_files eq 'y'}
                                    <option
                                        value="delsel_x"
                                        class="confirm-popup"
                                        data-confirm-text="{tr}Delete selected items?{/tr}"
                                    >
                                        {tr}Delete{/tr}
                                    </option>
                                {/if}
                                {if $prefs.fgal_display_zip_option eq 'y'}
                                    <option value="zipsel_x">
                                        {tr}Download zip version{/tr}
                                    </option>
                                {/if}
                                {if !isset($file_info)}
                                    {if $all_galleries|@count gt 0 and ($tiki_p_admin_file_galleries eq 'y' or $tiki_p_remove_files eq 'y')}
                                        <option value="movesel_x">
                                            {tr}Move{/tr}
                                        </option>
                                    {/if}
                                    <option value="refresh_metadata_x">
                                        {tr}Refresh metadata{/tr}
                                    </option>
                                    {if $tiki_p_admin_file_galleries eq 'y'}
                                        <option
                                            value="defaultsel_x"
                                            class="confirm-popup"
                                            data-confirm-text="{tr}Reset to default list view settings?{/tr}"
                                        >
                                            {tr}Reset to default list view settings{/tr}
                                        </option>
                                    {/if}
                                    {if $offset}
                                        <input type="hidden" name="offset" value="{$offset}">
                                    {/if}
                                {/if}
                            </select>
                            <button class="btn btn-primary" form="fgalform" type="submit" onclick="confirmPopup()">
                                    {tr}OK{/tr}
                            </button>
                        </div>
                    {/if}
                    {if !empty($movesel_x) and !isset($file_info)}
                        <div class="card">
                            <div class="card-header">
                                {tr}Move selected file or gallery{/tr}
                            </div>
                            <div class="card-body">
                                <div class="mb-3 row">
                                    <label for="moveto" class="col-sm-2">
                                        {tr}Move to:{/tr}
                                    </label>
                                    <div class="col-sm-8">
                                        <select name="moveto" class="form-select">
                                            {section name=ix loop=$all_galleries}
                                                {if $all_galleries[ix].id ne $galleryId and $all_galleries[ix].perms.tiki_p_upload_files eq 'y' and
                                                        ($all_galleries[ix].public eq 'y' or $all_galleries[ix].user eq $user or $all_galleries[ix].perms.tiki_p_admin_file_galleries eq 'y')}
                                                    <option value="{$all_galleries[ix].id}">
                                                        {$all_galleries[ix].label|escape}
                                                    </option>
                                                {/if}
                                            {/section}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <input
                                    type='submit'
                                    class="btn btn-secondary"
                                    form="fgalform"
                                    name='movesel'
                                    value="{tr}Move{/tr}"
                                >
                            </div>
                        </div>
                    {/if}
                </div>
                {if !empty($perms)}
                    <div class="card">
                        <div class="card-header">
                            {tr}Assign file gallery permissions to groups{/tr}
                        </div>
                        <div class="card-body">
                            <div class="mb-3 row">
                                <div class="col-sm-6">
                                    <span class="form-text">
                                        {tr}Permissions{/tr}
                                    </span>
                                    <select name="perms[]" multiple="multiple" size="12" class="form-select">
                                        {foreach from=$perms item=perm}
                                            <option value="{$perm.permName|escape}">{$perm.permName|escape}</option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <span class="form-text">
                                        {tr}Groups{/tr}
                                    </span>
                                    <select name="groups[]" multiple="multiple" size="12" class="form-select">
                                        {section name=grp loop=$groups}
                                            <option value="{$groups[grp].groupName|escape}" {if $groupName eq $groups[grp].groupName}selected="selected"{/if}>
                                                {$groups[grp].groupName|escape}
                                            </option>
                                        {/section}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input
                                class="btn btn-secondary"
                                type="submit"
                                name="permsel"
                                value="{tr}Assign{/tr}"
                            >
                        </div>
                    </div>
                {/if}
                <br style="clear:both"/>
            {/if}
        </form>
        {reindex_file_pixel id=$reindex_file_id}<br>
        {pagination_links cant=$cant step=$maxRecords offset=$offset}
            {if $view eq 'page'}
                tiki-list_file_gallery.php?galleryId={$galleryId}&maxWidth={$maxWidth}&maxRecords={$maxRecords}&view={$view}
            {/if}
        {/pagination_links}
        </div>
    </div>
</div>
