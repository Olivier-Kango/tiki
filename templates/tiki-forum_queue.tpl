{title help="forums" admpage="forums"}{$forum_info.name}{/title}
<h4>
    {tr}Queued messages{/tr}
    <span class="badge bg-secondary">{$cant}</span>
    {icon name="refresh" href="tiki-forum_queue.php?forumId=$forumId" class="btn btn-link tips" title=":{tr}Refresh list{/tr}"}
</h4>

{if $smarty.request.qId and $form eq 'y'}
    <form method="post" action="tiki-forum_queue.php">
        {ticket}
        <div class="card">
            <div class="card-header">
                {tr}Edit queued message{/tr}
            </div>
            <div class="card-body">
                <div class="tiki-form-group row">
                    <label for="title" class="col-form-label col-sm-2">
                        {tr}Title{/tr}
                    </label>
                    <div class="col-sm-10">
                        <input type="text" name="title" value="{$msg_info.title|escape}" class="form-control">
                    </div>
                </div>
                {if $msg_info.parentId > 0}
                    <div class="tiki-form-group row">
                        <label for="parentId" class="col-form-label col-sm-2">
                            {tr}Topic{/tr}
                        </label>
                        <div class="col-sm-10">
                            <select name="parentId" class="form-select">
                                {section name=ix loop=$topics}
                                    <option value="{$topics[ix].threadId|escape}" {if $topics[ix].threadId eq $msg_info.parentId}selected="selected"{/if}>{$topics[ix].title|escape}</option>
                                {/section}
                            </select>
                        </div>
                    </div>
                {else}
                    <div class="tiki-form-group row">
                        <label for="parentId" class="col-form-label col-sm-2">
                            {tr}Make this a thread of{/tr}
                        </label>
                        <div class="col-sm-10">
                            <select name="parentId" class="form-select">
                                <option value="0" {if $topics[ix].threadId eq $msg_info.parentId}selected="selected"{/if}>{tr}None, this is a thread message{/tr}</option>
                                {section name=ix loop=$topics}
                                    <option value="{$topics[ix].threadId|escape}" {if $topics[ix].threadId eq $msg_info.parentId}selected="selected"{/if}>{$topics[ix].title|escape}</option>
                                {/section}
                            </select>
                        </div>
                    </div>
                {/if}
                {if $msg_info.parentId eq 0 and $forum_info.topic_summary eq 'y'}
                    <div class="tiki-form-group row">
                        <label for="summary" class="col-form-label col-sm-2">
                            {tr}summary{/tr}
                        </label>
                        <div class="col-sm-10">
                            <input type="text" name="summary" value="{$msg_info.summary|escape}" class="form-control">
                        </div>
                    </div>
                {/if}
                {if $msg_info.parentId eq 0}
                    <div class="tiki-form-group row">
                        <label for="type" class="col-form-label col-sm-2">
                            {tr}Type{/tr}
                        </label>
                        <div class="col-sm-10">
                            <select name="type" class="form-select">
                                <option value="n" {if $msg_info.type eq 'n'}selected="selected"{/if}>{tr}Normal{/tr}</option>
                                <option value="a" {if $msg_info.type eq 'a'}selected="selected"{/if}>{tr}Announce{/tr}</option>
                                <option value="h" {if $msg_info.type eq 'h'}selected="selected"{/if}>{tr}Hot{/tr}</option>
                                <option value="s" {if $msg_info.type eq 's'}selected="selected"{/if}>{tr}Sticky{/tr}</option>
                                <option value="l" {if $msg_info.type eq 'l'}selected="selected"{/if}>{tr}Locked{/tr}</option>
                            </select>
                            {if $forum_info.topic_smileys eq 'y'}
                                <select name="topic_smiley" class="form-select">
                                    <option value="" {if $msg_info.topic_smiley eq ''}selected="selected"{/if}>{tr}no feeling{/tr}</option>
                                    <option value="icon_frown.gif" {if $msg_info.topic_smiley eq 'icon_frown.gif'}selected="selected"{/if}>{tr}frown{/tr}</option>
                                    <option value="icon_exclaim.gif" {if $msg_info.topic_smiley eq 'icon_exclaim.gif'}selected="selected"{/if}>{tr}exclaim{/tr}</option>
                                    <option value="icon_idea.gif" {if $msg_info.topic_smiley eq 'icon_idea.gif'}selected="selected"{/if}>{tr}idea{/tr}</option>
                                    <option value="icon_mad.gif" {if $msg_info.topic_smiley eq 'icon_mad.gif'}selected="selected"{/if}>{tr}mad{/tr}</option>
                                    <option value="icon_neutral.gif" {if $msg_info.topic_smiley eq 'icon_neutral.gif'}selected="selected"{/if}>{tr}neutral{/tr}</option>
                                    <option value="icon_question.gif" {if $msg_info.topic_smiley eq 'icon_question.gif'}selected="selected"{/if}>{tr}question{/tr}</option>
                                    <option value="icon_sad.gif" {if $msg_info.topic_smiley eq 'icon_sad.gif'}selected="selected"{/if}>{tr}sad{/tr}</option>
                                    <option value="icon_smile.gif" {if $msg_info.topic_smiley eq 'icon_smile.gif'}selected="selected"{/if}>{tr}happy{/tr}</option>
                                    <option value="icon_wink.gif" {if $msg_info.topic_smiley eq 'icon_wink.gif'}selected="selected"{/if}>{tr}wink{/tr}</option>
                                </select>
                            {/if}
                        </div>
                    </div>
                {/if}
                <div class="tiki-form-group row">
                    <label for="data" class="col-form-label col-sm-2">
                        {tr}Body{/tr}
                    </label>
                    <div class="col-sm-10">
                        {textarea rows="6" cols="60" class="form-control" name="data"}{$msg_info.data}{/textarea}
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <input type="hidden" name="forumId" value="{$forumId|escape}">
                <input type="hidden" name="in_reply_to" value="{$msg_info.in_reply_to|escape}">
                <input type="hidden" name="qId" value="{$smarty.request.qId|escape}">
                <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}" onclick="needToConfirm=false">
                <input type="submit" class="btn btn-primary" name="saveapp" value="{tr}Save and Approve{/tr}" onclick="needToConfirm=false">
                <input type="submit" class="btn btn-warning btn-sm" name="remove" value="{tr}Remove{/tr}" onclick="needToConfirm=false">
                <input type="submit" class="btn btn-primary btn-sm" name="topicize" value="{tr}Convert to topic{/tr}" onclick="needToConfirm=false">
            </div>
        </div>
    </form>
{/if}

{* FILTERING FORM *}
{if $items or ($find ne '')}
    <form action="tiki-forum_queue.php" method="post" class="form">
        {ticket}
        <div class="mb-3">
            <input type="hidden" name="forumId" value="{$forumId|escape}">
            <input type="hidden" name="offset" value="{$offset|escape}">
            <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
            <div class="input-group">
                <input type="text" name="find" value="{$find|escape}" class="form-control" placeholder="{tr}Find{/tr}...">
                <input type="submit" class="btn btn-primary" name="filter" value="{tr}Filter{/tr}">
            </div>
        </div>
    </form>
{/if}
{*END OF FILTERING FORM *}

{*LISTING*}
<form action="tiki-forum_queue.php" method="post">
    {ticket}
    <input type="hidden" name="forumId" value="{$forumId|escape}">
    <input type="hidden" name="offset" value="{$offset|escape}">
    <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
    <input type="hidden" name="find" value="{$find|escape}">

    <div class="table-responsive">
        <table class="table">
            <tr>
                {if $items}<th>&nbsp;</th>{/if}
                <th>{tr}Message{/tr}</th>
            </tr>

            {section name=ix loop=$items}
                <tr>
                    <td class="checkbox-cell">
                        <input type="checkbox" name="msg[{$items[ix].qId}]" class="form-check-input" aria-label="{tr}Select{/tr}">
                    </td>

                    <td class="text">
                        {if $items[ix].parentId > 0}
                            [{tr}Topic:{/tr} {$items[ix].topic_title|escape}]
                        {else}
                            [{tr}New Topic{/tr}]
                        {/if}
                        <b><a class="link" href="tiki-forum_queue.php?forumId={$forumId}&amp;qId={$items[ix].qId}">{if !empty($items[ix].title)}{$items[ix].title|escape}{else}{tr}Untitled{/tr}{/if}</a></b>
                        by {$items[ix].user|username} on {$items[ix].timestamp|tiki_short_datetime}
                        <br>
                        {if $items[ix].parentId eq 0 and $forum_info.topic_summary eq 'y'}
                            {if strlen($items[ix].summary) > 0}
                                <i>{$items[ix].summary|escape}</i><br>
                            {else}
                                <i>{tr}no summary{/tr}</i>
                            {/if}
                        {/if}
                        <hr/>
                        {$items[ix].parsed}
                        {if count($items[ix].attachments) > 0}
                            <br>
                            {section name=iz loop=$items[ix].attachments}
                                <a class="link" href="tiki-download_forum_attachment.php?attId={$items[ix].attachments[iz].attId}">
                                    {icon name='attach' alt="{tr}Attachment{/tr}"}
                                    {$items[ix].attachments[iz].filename} ({$items[ix].attachments[iz].filesize|kbsize})</a>
                                <form action="tiki-forum_queue.php" method="post" >
                                    {ticket}
                                    <input type="hidden" name="find" value={$find}>
                                    <input type="hidden" name="offset" value={$offset}>
                                    <input type="hidden" name="sort_mode" value={$sort_mode}>
                                    <input type="hidden" name="remove_attachment" value={$items[ix].attachments[iz].attId}>
                                    <button type="submit" name="forumId" value={$forumId} class="tips btn btn-link btn-sm px-0 pt-0 pb-0">
                                        {icon name='remove' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                                <br>
                            {/section}
                        {/if}
                    </td>
                </tr>
            {sectionelse}
                {norecords _colspan=2 _text="{tr}No messages queued yet{/tr}"}
            {/section}
        </table>
    </div>
    {if $items}
        <br>
        {tr}Perform action with checked:{/tr}
        <input type="submit" class="btn btn-primary btn-sm" name="rej" onclick="confirmPopup()" value="{tr}Reject{/tr}">
        <input type="submit" class="btn btn-primary btn-sm" name="app" onclick="confirmPopup()" value="{tr}Approve{/tr}">
    {/if}
</form>
{* END OF LISTING *}

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
