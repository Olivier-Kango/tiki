{title help="User Bookmarks"}{tr}My Bookmarks{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}

{if $parentId>0}[<a class="link" href="tiki-user_bookmarks.php">{tr}top{/tr}</a>] {/if}{tr}Current folder:{/tr} {$path}<br>
<h2>{tr}Folders{/tr}</h2>
<div class="table-responsive">
<table class="table table-striped table-hover">
    <tr>
        <th>{tr}Name{/tr}</th>
        <th></th>
    </tr>

    {section name=ix loop=$folders}
        <tr>
            <td class="text"><a class="tips" title=":{tr}Folder in{/tr}" href="tiki-user_bookmarks.php?parentId={$folders[ix].folderId}">
                {icon name='file-archive' }</a>&nbsp;{$folders[ix].name|escape} ({$folders[ix].urls})
            </td>
            <td class="action">
                <a class="tips" title=":{tr}Edit{/tr}" href="tiki-user_bookmarks.php?parentId={$parentId}&amp;editfolder={$folders[ix].folderId}">
                    {icon name='edit'}
                </a> &nbsp;
                <form action="tiki-user_bookmarks.php" method="post" >
                    {ticket}
                    <input type="hidden" name="removefolder" value={$folders[ix].folderId}>
                    <button type="submit" title=":{tr}Remove folder{/tr}" name="parentId" value={$parentId} class="tips btn btn-link btn-sm px-0 pt-0 pb-0">
                        {icon name='remove'}
                    </button>
                </form>
            </td>
        </tr>
    {sectionelse}
        {norecords _colspan=2}
    {/section}
</table>
</div>

<h2>{tr}Bookmarks{/tr}</h2>
<div class="table-responsive">
    <table class="table table-striped table-hover">
    <tr>
        <th>{tr}Name{/tr}</th>
        <th>{tr}URL{/tr}</th>
        <th></th>
    </tr>

    {section name=ix loop=$urls}
        <tr>
            <td class="text"><a class="link" target="_blank" href="{$urls[ix].url}">{$urls[ix].name|escape}</a>
                {if $tiki_p_cache_bookmarks eq 'y' and $urls[ix].datalen > 0}
                    (<a href="tiki-user_cached_bookmark.php?urlid={$urls[ix].urlId}" class="link" target="_blank">{tr}Cache{/tr}</a>)
                {/if}
            </td>
            <td class="text">{textformat wrap="60" wrap_cut=true wrap_char="<br>"}{$urls[ix].url}{/textformat}</td>
            <td class="action">
                <a class="tips" title=":{tr}Edit{/tr}" href="tiki-user_bookmarks.php?parentId={$parentId}&amp;editurl={$urls[ix].urlId}">
                    {icon name='edit'}
                </a>
                {if $tiki_p_cache_bookmarks eq 'y' and $urls[ix].datalen > 0}
                    <a class="tips" title=":{tr}Refresh cache{/tr}" href="tiki-user_bookmarks.php?parentId={$parentId}&amp;refreshurl={$urls[ix].urlId}">
                        {icon name='refresh'}
                    </a>
                    <form action="tiki-user_bookmarks.php" method="post" >
                        {ticket}
                        <input type="hidden" name="refreshurl" value={$urls[ix].urlId}>
                        <button type="submit" title=":{tr}Refresh cache{/tr}" name="parentId" value={$parentId} class="tips btn btn-link btn-sm px-0 pt-0 pb-0">
                        {icon name='refresh'}
                        </button>
                    </form>
                {/if}
                &nbsp; <a class="tips" title=":{tr}Remove{/tr}" href="tiki-user_bookmarks.php?parentId={$parentId}&amp;removeurl={$urls[ix].urlId}">
                    {icon name='remove'}
                </a>
            </td>
        </tr>
    {sectionelse}
        {norecords _colspan=3}
    {/section}
</table>
</div>

<h2>{tr}Admin folders and bookmarks{/tr}</h2>
{* form to add a category *}
<h3>{if $editfolder}{tr}Edit{/tr}{else}{tr}Add{/tr}{/if} {tr}a folder{/tr}</h3>
{if $editfolder}
    <a class="btn btn-link" href="tiki-user_bookmarks.php?parentId={$parentId}&amp;editfolder=0">{tr}New{/tr}</a>
{/if}
<form action="tiki-user_bookmarks.php" method="post" class="mb-4">
{ticket}
    <input type="hidden" name="editfolder" value="{$editfolder|escape}">
    <input type="hidden" name="parentId" value="{$parentId|escape}">
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Name:{/tr}</label>
        <div class="col-sm-6">
            <input type="text" name="foldername" class="form-control" value="{$foldername|escape}">
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-12">
            <input type="submit" class="btn btn-primary btn-sm" name="addfolder" value="{tr}Add{/tr}">
        </div>
    </div>
</form>

{* form to add a url *}
<h3>{if $urlname}{tr}Edit{/tr}{else}{tr}Add{/tr}{/if} {tr}a bookmark{/tr}</h3>
{if $urlname}
    <a class="btn btn-link" href="tiki-user_bookmarks.php?parentId={$parentId}&amp;editurl=0">{tr}New{/tr}</a>
{/if}
<form action="tiki-user_bookmarks.php" method="post" class="mb-4">
{ticket}
    <input type="hidden" name="editurl" value="{$editurl|escape}">
    <input type="hidden" name="parentId" value="{$parentId|escape}">
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Name:{/tr}</label>
        <div class="col-sm-6">
            <input type="text" name="urlname" class="form-control" value="{$urlname|escape}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}URL:{/tr}</label>
        <div class="col-sm-6">
            <input type="text" name="urlurl" class="form-control" value="{$urlurl|escape}">
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-12">
            <input type="submit" class="btn btn-primary btn-sm" name="addurl" value="{tr}Add{/tr}">
        </div>
    </div>
</form>
