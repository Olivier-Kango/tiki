{if $prefs.feature_freetags eq 'y' and $tiki_p_view_freetags eq 'y' and isset($tags[0])}
    <div class="freetaglist">{icon name="tags" class="tips btn btn-link btn-sm" title=":{tr}Browse Tags{/tr}" href="tiki-browse_freetags.php"}
        {foreach from=$tags item=taginfo}
            {capture name=tagurl}{if (strstr($taginfo.tag, ' '))}"{$taginfo.tag}"{else}{$taginfo.tag}{/if}{/capture}
            {if isset($links_inactive) and $links_inactive eq 'y'}
                <a class="btn-primary btn-sm" href="#">{$taginfo.tag|escape}</a>
            {else}
                <a class="label label-default" href="tiki-browse_freetags.php?tag={$smarty.capture.tagurl|escape:'url'}">{$taginfo.tag|escape}</a>
                {if isset($deleteTag) and $tiki_p_unassign_freetags eq 'y'}
                    <a class="tips" title=":{tr}Untag{/tr} {$taginfo.tag|escape}" href="{$smarty.server.REQUEST_URI}{if strstr($smarty.server.REQUEST_URI, '?')}&amp;{else}?{/if}delTag={$taginfo.tag|escape:'url'}">
                        {icon name='remove' alt="{tr}Untag{/tr}"}
                    </a>&nbsp;
                {/if}
            {/if}
        {/foreach}
        {if isset($freetags_mixed_lang) && $freetags_mixed_lang}
            (<a href="{$freetags_mixed_lang}">{tr}Translate tags{/tr}</a>)
        {/if}
    </div>
{/if}
