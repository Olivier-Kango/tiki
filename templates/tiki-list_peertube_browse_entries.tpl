{if $videoList && $videoList|@count > 0}
    <div class="text-center">
        {wikiplugin _name=peertube id=$videoList[0]->uuid}{/wikiplugin}
        <div class="navi peertube">
            <a class="prev"></a>
            <div class="scrollable">
                <div class="items">
                    {foreach from=$videoList key=key item=item}
                        <a href="#" onclick="loadVideo('{$item->uuid}'); return false" role="button"><img class="athumb" src="{$item->thumbnailUrl}" alt="{$item->description|escape}" height="80" width="120"></a>
                    {/foreach}
                </div>
            </div>
            <a class="next"></a>
        </div>
    </div>
{else}
    <p>{tr}No videos available{/tr}</p>
{/if}

{jq notonready=true}
    function loadVideo(videoId) {
        // Update the wikiplugin to load the new video
        var plugin = "{peertube id='" + videoId + "'}";
        $.post('tiki-ajax_services.php', {
            controller: 'plugin',
            action: 'replace',
            name: 'peertube',
            params: { id: videoId }
        }, function(data) {
            $('#peertube_player').html(data);
        });
    }
{/jq}

{if $tiki_p_list_videos eq 'y'}
    {button _text="{tr}List Videos{/tr}" href="tiki-list_peertube_entries.php" _class="btn-info"}
{/if}
