{* Title and help link for the page *}
{title help="PeerTube" admpage="video"}{tr}List Videos{/tr}{/title}

<div class="row mb-3 row">
    <form method="get" class="col-md-12 d-flex flex-row flex-wrap align-items-center">
        <label class="col-form-label col-sm-2" for="find">{tr}Find{/tr}</label>
        <div class="input-group col-sm-8">
            <input type="text" name="find" class="form-control" id="find" value="{$find|escape}" placeholder="Search videos...">
            <input type="hidden" name="list" value="videos">
        </div>
        <div class="col-sm-2">
            <input type="submit" class="btn btn-info btn-sm" name="search" value="{tr}Go{/tr}">
        </div>
    </form>
</div>

<div class="navbar btn-group mb-3">
    {if $tiki_p_upload_peertube eq 'y'}
        {button class="btn btn-info" _text="{tr}Upload Video{/tr}" href="tiki-peertube_upload.php"}
    {/if}
</div>

{if $count > 0}
    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}

    <div class="{if $js}table-responsive{/if}"> 
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>
                        <a href="tiki-list_peertube_entries.php?offset={$offset}&sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Title{/tr}</a>
                    </th>
                    <th>{tr}Status{/tr}</th>
                    <th>
                        <a href="tiki-list_peertube_entries.php?offset={$offset}&sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Published{/tr}</a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {section name=ix loop=$klist}
                    <tr>
                        <td>
                            <div class="peertube-title-hover">
                                <a href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}" class="text-decoration-none">
                                    {$klist[ix]->name|escape}
                                </a>
                                <div class="peertube-hover-info">
                                    <div class="hover-info-item">
                                        <span class="hover-info-label">{tr}Title:{/tr}</span>
                                        <span class="hover-info-value">{$klist[ix]->name|escape}</span>
                                    </div>
                                    <div class="hover-info-item">
                                        <span class="hover-info-label">{tr}Duration:{/tr}</span>
                                        {assign var=seconds value=$klist[ix]->duration}
                                        {math equation="floor(x/3600)" x=$seconds assign=hours}
                                        {math equation="floor((x%3600)/60)" x=$seconds assign=minutes}
                                        {math equation="x%60" x=$seconds assign=remainingSeconds}

                                        <span class="hover-info-value">
                                            {if $hours > 0}{$hours}h{/if}
                                            {if $minutes > 0 || $hours > 0}{$minutes|string_format:"%02d"}min{/if}
                                            {if $hours == 0 && $minutes == 0}{$remainingSeconds}s{/if}
                                        </span>
                                    </div>
                                    <div class="hover-info-item">
                                        <span class="hover-info-label">{tr}Views:{/tr}</span>
                                        <span class="hover-info-value">{$klist[ix]->views|escape}</span>
                                    </div>
                                    <div class="hover-info-item">
                                        <span class="hover-info-label">{tr}Video ID:{/tr}</span>
                                        <span class="hover-info-value"><pre style="margin:0">{$klist[ix]->uuid|escape}</pre></span>
                                    </div>
                                    {if !empty($klist[ix]->description)}
                                    <div class="hover-info-item">
                                        <span class="hover-info-label">{tr}Description:{/tr}</span>
                                        <span class="hover-info-value">{$klist[ix]->description|escape}</span>
                                    </div>
                                    {/if}
                                </div>
                            </div>
                        </td>
                        <td>{$klist[ix]->statusString|default:'N/A'|escape}</td>
                        <td>{$klist[ix]->publishedAt|date_format:"%d %b %Y %H:%M"}</td>
                        <td class="action">
                            {actions}
                                {strip}
                                    <action>
                                        <a href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}">
                                            {icon name='view' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                        </a>
                                    </action>

                                    {if $tiki_p_download_peertube eq 'y'}
                                        <action>
                                            <a href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}#download">
                                                {icon name='download' _menu_text='y' _menu_icon='y' alt="{tr}Download{/tr}"}
                                            </a>
                                        </action>
                                    {/if}

                                    {if $tiki_p_edit_peertube eq 'y'}
                                        <action>
                                            <a href="tiki-peertube_upload.php?videoId={$klist[ix]->uuid|escape}">
                                                {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                            </a>
                                        </action>
                                    {/if}

                                    {if $tiki_p_delete_peertube eq 'y'}
                                        <action>
                                            <form action="tiki-list_peertube_entries.php" method="post" class="m-0">
                                                {ticket}
                                                <input type="hidden" name="action" value="Delete">
                                                <input type="hidden" name="videoId[]" value="{$klist[ix]->uuid|escape}">
                                                <button type="submit"
                                                        class="btn btn-link px-0 pt-0 pb-0"
                                                        onclick="confirmPopup('{tr}Are you sure you want to delete this video?{/tr}')"
                                                        aria-label="{tr}Remove{/tr}">
                                                    {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                                </button>
                                            </form>
                                        </action>
                                    {/if}
                                {/strip}
                            {/actions}
                        </td>
                    </tr>
                {/section}
            </tbody>
        </table>
    </div>

    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}
{else}
    <p>{tr}No videos found.{/tr}</p>
{/if}

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}

<style>
    .peertube-title-hover {
        position: relative;
        display: inline-block;
    }
    .peertube-hover-info {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        min-width: 300px;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-size: 14px;
        line-height: 1.4;
    }
    .peertube-title-hover:hover .peertube-hover-info {
        display: block !important;
    }
    .peertube-hover-info::before {
        content: '';
        position: absolute;
        top: -8px;
        left: 20px;
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-bottom: 8px solid #ddd;
    }
    .peertube-hover-info::after {
        content: '';
        position: absolute;
        top: -7px;
        left: 21px;
        width: 0;
        height: 0;
        border-left: 7px solid transparent;
        border-right: 7px solid transparent;
        border-bottom: 7px solid #fff;
    }
    .hover-info-item {
        margin-bottom: 8px;
    }
    .hover-info-item:last-child {
        margin-bottom: 0;
    }
    .hover-info-label {
        font-weight: 600;
        color: #495057;
    }
    .hover-info-value {
        color: #6c757d;
    }
</style>
