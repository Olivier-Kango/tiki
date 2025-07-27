{* Title and help link for the page *}
{title help="PeerTube" admpage="video"}{tr}List Videos{/tr}{/title}

<div class="row mb-3 row">
    <form method="get" class="col-md-12 d-flex flex-row flex-wrap align-items-center">
        <label class="col-form-label col-sm-2" for="find">{tr}Find{/tr}</label>
        <div class="input-group col-sm-8">
            <input type="text" name="find" class="form-control" id="find" value="{$find|escape}">
            <input type="hidden" name="list" value="videos">
        </div>
        <div class="col-sm-2">
            <input type="submit" class="btn btn-info btn-sm" name="search" value="{tr}Go{/tr}">
        </div>
    </form>
</div>

<div class="navbar btn-group">
    {if $tiki_p_upload_peertube eq 'y'}
        {button class="btn btn-info" _text="{tr}Upload Video{/tr}" href="tiki-peertube_upload.php"}
    {/if}
</div>

{if $count > 0}
    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}

    <table class="table">
        <tr>
            <th>{self_link _sort_arg="asc_name" _sort_field="name"}{tr}Title{/tr}{/self_link}</th>
            <th>{tr}Status{/tr}</th>
            <th>{self_link _sort_arg="asc_publishedAt" _sort_field="publishedAt"}{tr}Published{/tr}{/self_link}</th>
            <th>{tr}Actions{/tr}</th>
        </tr>
        {section name=ix loop=$klist}
            <tr>
                <td>
                    <a href="{$prefs.peertube_service_url}videos/watch/{$klist[ix]->uuid}" target="_blank">
                        {$klist[ix]->name|escape}
                    </a>
                </td>
                <td>{$klist[ix]->statusString|default:'N/A'|escape}</td>
                <td>{$klist[ix]->publishedAt|date_format:"%d %b %Y %H:%M"}</td>
                <td>
                    {* Show delete form if user has delete permission *}
                    {if $tiki_p_delete_peertube eq 'y'}
                        <form method="post" action="tiki-list_peertube_entries.php">
                            <input type="hidden" name="csrftoken" value="{$token}">
                            <input type="hidden" name="action" value="Delete">
                            <input type="hidden" name="videoId[]" value="{$klist[ix]->uuid}">
                            <input type="submit" class="btn btn-danger" value="{tr}Delete{/tr}">
                        </form>
                    {/if}
                </td>
            </tr>
        {/section}
    </table>

    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}
{else}
    <p>{tr}No videos found.{/tr}</p>
{/if}

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
