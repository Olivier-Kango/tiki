{* Title and help link for the page *}
{title help="PeerTube" admpage="video"}{tr}List Videos{/tr}{/title}

<div class="navbar btn-group">
    {* Show upload button if user has upload permission *}
    {if $tiki_p_upload_peertube eq 'y'}
        {button class="btn btn-info" _text="{tr}Upload Video{/tr}" href="tiki-peertube_upload.php"}
    {/if}
</div>

{if $count > 0}
    {* Pagination control (before list) *}
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
                <td>{$klist[ix]->name|escape}</td>
                {* Cette propriété peut ne pas exister, donc afficher une chaîne par défaut si manquante *}
                <td>{$klist[ix]->statusString|default:'N/A'|escape}</td>
                <td>{$klist[ix]->publishedAt|date_format:"%d %b %Y %H:%M"}</td>
                <td>
                    {* Show delete form if user has delete permission *}
                    {if $tiki_p_delete_peertube eq 'y'}
                        <form method="post" action="tiki-list_peertube_entries.php">
                            {* CSRF token à inclure si tu l’as généré dans le PHP (ex : via tokenlib) *}
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

    {* Pagination control (after list) *}
    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}
{else}
    <p>{tr}No videos found.{/tr}</p>
{/if}
