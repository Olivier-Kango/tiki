
<div align="center">
    {include file='find.tpl'}
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    <a href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">{tr}Newsletter{/tr}</a>
                </th>
                <th>
                    <a href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={if $sort_mode eq 'subject_desc'}subject_asc{else}subject_desc{/if}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">{tr}Subject{/tr}</a>
                </th>
                {if $view_editions eq 'y'}
                    <th>
                        <a href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={if $sort_mode eq 'users_desc'}users_asc{else}users_desc{/if}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">{tr}Users{/tr}</a>
                    </th>
                    <th>
                        <a href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={if $sort_mode eq 'sent_desc'}sent_asc{else}sent_desc{/if}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">{tr}Sent{/tr}</a>
                    </th>
                {/if}
                <th>{tr}Errors{/tr}</th>
                <th></th>
            </tr>

            {section name=user loop=$channels}
                <tr>
                    <td class="text">{$channels[user].name|escape}</td>
                    <td class="text">
                        {if $view_editions eq 'y'}
                            <a class="link" href="{$url}?{if $nl_info}nlId={$channels[user].nlId}&amp;{elseif $nlId}nlId={$nlId}&amp;{/if}offset={$offset}&amp;sort_mode={$sort_mode}&amp;editionId={$channels[user].editionId}&amp;resend=1">{$channels[user].subject|escape}</a>
                        {else}
                            <a class="link" href="{$url}?{if $nl_info}nlId={$channels[user].nlId}&amp;{elseif $nlId}nlId={$nlId}&amp;{/if}offset={$offset}&amp;sort_mode={$sort_mode}&amp;editionId={$channels[user].editionId}">{$channels[user].subject|escape}</a>
                        {/if}
                    </td>
                    {if $view_editions eq 'y'}
                        <td>{$channels[user].users}</td>
                        <td>{$channels[user].sent|tiki_short_datetime}</td>
                    {/if}
                    <td class="integer">
                        {if $channels[user].nbErrors > 0}
                            <a href="tiki-newsletter_archives.php?nlId={$channels[user].nlId}&amp;error={$channels[user].editionId}">{$channels[user].nbErrors}</a>
                        {else}
                            0
                        {/if}
                    </td>
                    <td class="action">
                        {actions}
                            {strip}
                                {if $url == "tiki-newsletter_archives.php" or $url == "tiki-send_newsletters.php" }
                                    <action>
                                        <a href="{$url}?{if $nl_info}nlId={$channels[user].nlId}&amp;{/if}offset={$offset}&amp;sort_mode={$sort_mode}&amp;editionId={$channels[user].editionId}">
                                            {icon name='view' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                        </a>
                                    </action>
                                {/if}
                                {if ($channels[user].tiki_p_send_newsletters eq 'y') or ($channels[user].tiki_p_admin_newsletters eq 'y')}
                                    {if $view_editions eq 'y'}
                                        <action>
                                            <a href="tiki-send_newsletters.php?nlId={$channels[user].nlId}&amp;editionId={$channels[user].editionId}&amp;resend=1">
                                                {icon name='repeat' _menu_text='y' _menu_icon='y' alt="{tr}Resend newsletter{/tr}"}
                                            </a>
                                        </action>
                                    {else}
                                        <action>
                                            <a class="tips" title="{tr}Send Newsletter{/tr}" href="tiki-send_newsletters.php?nlId={$channels[user].nlId}&amp;editionId={$channels[user].editionId}">
                                                {icon name='envelope' _menu_text='y' _menu_icon='y' alt="{tr}Send newsletter{/tr}"}
                                            </a>
                                        </action>
                                    {/if}
                                {else}
                                    &nbsp;
                                {/if}
                                {if $channels[user].tiki_p_admin_newsletters eq 'y'}
                                    <action>
                                        <form action="{$url}" method="post">
                                            {ticket}
                                            <input type="hidden" name="nlId" value="{$channels[user].nlId}">
                                            <input type="hidden" name="offset" value="{$offset}">
                                            <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                            <input type="hidden" name="remove" value="{$channels[user].editionId}">
                                            <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup()">
                                                {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                            </button>
                                        </form>
                                    </action>
                                {else}
                                    &nbsp;
                                {/if}
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {/section}
        </table>
    </div>

    <div class="mx-auto">
        {if $prev_offset >= 0}
            [<a class="prevnext" href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$prev_offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={$sort_mode}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">{tr}Prev{/tr}</a>]&nbsp;
            {/if}
        {tr}Page:{/tr} {$actual_page}/{$cant_pages}
        {if $next_offset >= 0}
            &nbsp;[<a class="prevnext" href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$next_offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={$sort_mode}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">{tr}Next{/tr}</a>]
    {/if}
        {if $prefs.direct_pagination eq 'y'}
            <br>
            {section loop=$cant_pages name=foo}
                {assign var=selector_offset value=$smarty.section.foo.index|times:$prefs.maxRecords}
                    <a class="prevnext" href="{$url}?nlId={$nlId}&amp;{$cur}_offset={$selector_offset}&amp;{$bak}_offset={$offset_bak}&amp;{$cur}_sort_mode={$sort_mode}&amp;{$bak}_sort_mode={$sort_mode_bak}&amp;cookietab={$tab}">
                {$smarty.section.foo.index_next}</a>&nbsp;
            {/section}
        {/if}
    </div>
</div>
