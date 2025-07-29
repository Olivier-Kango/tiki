{title help="PeerTube" admpage="video"}
    {if $pmode eq 'view'}{tr}View:{/tr} {$videoInfo->name}
    {else}{tr}PeerTube Video{/tr}{/if}
{/title}

<div class="navbar btn-group">
    {if $tiki_p_list_videos eq 'y'}
        {button class="btn btn-info" _text="{tr}List Videos{/tr}" href="tiki-list_peertube_entries.php"}
    {/if}
</div>

<div>
    <div class="fgal_file">
        <table width="100%">
            <tr>
                <td width="100%" align="center">
                    {if $videoUrl}
                        {wikiplugin _name=peertube url=$videoUrl}{/wikiplugin}
                    {else}
                        <p class="alert alert-warning">{tr}Video URL is required to display the video{/tr}</p>
                    {/if}
                </td>
            </tr>
        </table>

        {if $videoInfo}
            <table width="100%" class="table table-striped">
                <tr>
                    <td class="fw-bold" style="width: 200px;">{tr}Video Title{/tr}</td>
                    <td>{$videoInfo->name|escape}</td>
                </tr>
                <tr>
                    <td class="fw-bold">{tr}Description{/tr}</td>
                    <td>{if $videoInfo->description}{$videoInfo->description|escape|nl2br}{else}<em>{tr}No description available{/tr}</em>{/if}</td>
                </tr>
                <tr>
                    <td class="fw-bold">{tr}Duration{/tr}</td>
                    <td>{if $videoInfo->duration}{$videoInfo->duration} {tr}seconds{/tr}{else}<em>{tr}Not available{/tr}</em>{/if}</td>
                </tr>
                <tr>
                    <td class="fw-bold">{tr}Views{/tr}</td>
                    <td>{if $videoInfo->views}{$videoInfo->views}{else}0{/if}</td>
                </tr>
                <tr>
                    <td class="fw-bold">{tr}Published{/tr}</td>
                    <td>{if $videoInfo->publishedAt}{$videoInfo->publishedAt|tiki_short_datetime}{else}<em>{tr}Not available{/tr}</em>{/if}</td>
                </tr>
                <tr>
                    <td class="fw-bold">{tr}Embed code{/tr}</td>
                    <td>
                        <code>{ldelim}peertube url="{$videoUrl}"{rdelim}</code>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="navigator.clipboard.writeText('{ldelim}peertube url=&quot;{$videoUrl}&quot;{rdelim}')">
                            {tr}Copy{/tr}
                        </button>
                    </td>
                </tr>
                <tr>
                    <td class="fw-bold">{tr}Direct Link{/tr}</td>
                    <td>
                        <a href="{$videoUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                            {tr}View on PeerTube{/tr} <i class="fas fa-external-link-alt"></i>
                        </a>
                    </td>
                </tr>
            </table>
        {/if}
    </div>
</div>
