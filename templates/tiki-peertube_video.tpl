{title help="PeerTube" admpage="video"}
    {if $mode eq 'edit'}{tr}Change Details:{/tr}{$videoInfo->name}
    {elseif $mode eq 'view'}{tr}View:{/tr}{$videoInfo->name}
    {else}{tr}PeerTube Video{/tr}{/if}
{/title}
<div class="navbar btn-group">
    {if $tiki_p_list_videos eq 'y'}
        {button class="btn btn-info" _text="{tr}List Videos{/tr}" href="tiki-list_peertube_entries.php"}
    {/if}
    {if $mode ne '' and $mode ne 'edit' and ($tiki_p_edit_videos eq 'y' or $tiki_p_admin_peertube eq 'y' or $tiki_p_admin eq 'y')}
        {button class="btn btn-primary" _text="{tr}Change Details{/tr}" href="tiki-peertube_video.php?videoId=$videoId&action=edit"}
    {/if}
</div>
<div>
    {if $mode eq 'edit'}
        <form action='tiki-peertube_video.php' enctype='multipart/form-data' method='post'>
            <div class="fgal_file">
                <table width="100%">
                    <tr>
                        <td width="50%" align="center">
                            {wikiplugin _name=peertube id=$videoInfo->uuid}{/wikiplugin}
                        </td>
                    </tr>
                </table>
                <table width="100%" class="formcolor">
                    <tr>
                        <td class="even">{tr}Video Title{/tr}</td>
                        <td class="even">
                            <input style="width:99%" type="text" name="name" value="{$videoInfo->name|escape}" size="40">
                        </td>
                    </tr>
                    <tr>
                        <td class="odd">{tr}Description{/tr}</td>
                        <td class="odd">
                            <textarea style="width:99%" rows="2" cols="40" name="description">{$videoInfo->description|escape}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="even">{tr}Embed code{/tr}</td>
                        <td class="even">
                            {ldelim}peertube id="{$videoId}"{rdelim}
                        </td>
                    </tr>
                </table>
            </div>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="videoId" value="{$videoInfo->uuid}">
            <input name="update" type="submit" class="btn btn-primary" value="{tr}Save{/tr}">
        </form>
    {else}
        <div class="fgal_file">
            <table width="100%">
                <tr>
                    <td width="50%" align="center">
                        {wikiplugin _name=peertube id=$videoInfo->uuid}{/wikiplugin}
                    </td>
                </tr>
            </table>
            <table width="100%" class="formcolor">
                <tr>
                    <td class="even">{tr}Video Title{/tr}</td>
                    <td class="even">{$videoInfo->name|escape}</td>
                </tr>
                <tr>
                    <td class="odd">{tr}Description{/tr}</td>
                    <td class="odd">{$videoInfo->description|escape}</td>
                </tr>
                <tr>
                    <td class="even">{tr}Embed code{/tr}</td>
                    <td class="even">{ldelim}peertube id="{$videoId}"{rdelim}</td>
                </tr>
            </table>
        </div>
    {/if}
</div>
