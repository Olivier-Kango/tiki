{if $user and $tiki_p_create_bookmarks eq 'y'}
    {tikimodule error=$module_params.error title=$tpl_module_title name="user_bookmark" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

        {if !isset($tpl_module_title)}{assign var=tpl_module_title value="<a href=\"tiki-user_bookmarks.php\">{tr}Bookmarks{/tr}</a>"}{/if}


        <ul class="list-unstyled ps-3">
            {section name=ix loop=$modb_urls}
                <li class="d-flex flex-row align-items-center">
                    <a class="linkmodule mb-1" href="{$modb_urls[ix].url}">{$modb_urls[ix].name|escape}</a>
                    {if $tiki_p_cache_bookmarks eq 'y' and $modb_urls[ix].datalen > 0}
                        (<a href="tiki-user_cached_bookmark.php?urlid={$modb_urls[ix].urlId}" class="linkmodule" target="_blank"><small>{tr}Cache{/tr}</small></a>)
                    {/if}
                    <a class="btn btn-close ms-2" style="--bs-btn-font-size: .75rem;" title="{tr}Delete{/tr}" aria-label="{tr}Delete{/tr}" href="{$ownurl}{$modb_sep}bookmark_removeurl={$modb_urls[ix].urlId}"></a>
                </li>
            {/section}
        </ul>
        <ul class="list-unstyled ps-3">
            {section name=ix loop=$modb_folders}
                <li>
                    <a href="{$ownurl}{$modb_sep}bookmarks_directory={$modb_folders[ix].folderId}" aria-label="{tr}Toggle display{/tr}" title="{tr}Toggle display{/tr}">{icon name="folder-o"}</a>&nbsp;{$modb_folders[ix].name|escape}
                </li>
            {/section}
        </ul>
        <div class="row">
            <div class="col">
                <form name="bookmarks" action="{$ownurl}" method="post" class="align-items-center">
                    <input class="form-control form-control-sm col-12 mb-2" type="text" name="modb_name" aria-label="{tr}Create Bookmark{/tr}">
                    <input type="submit" class="btn btn-sm btn-primary mb-2" name="bookmark_mark" value="{tr}Create Bookmark{/tr}"><br>
                    <input type="submit" class="btn btn-primary btn-sm" name="bookmark_create_folder" value="{tr}New Folder{/tr}">
                </form>
            </div>
        </div>
    {/tikimodule}
{/if}
