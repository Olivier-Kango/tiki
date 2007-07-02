{* $Header: /cvsroot/tikiwiki/tiki/templates/comment-footer.tpl,v 1.1 2007-07-02 20:53:20 nyloth Exp $ *}
<div class="postfooter">
	<div class="status">
	{if $feature_contribution eq 'y' and $feature_contribution_display_in_comment eq 'y'}
		<span class="contributions">
		{section name=ix loop=$comment.contributions}
			<span class="contribution">{$comment.contributions[ix].name|escape}</span>
		{/section}
		</span>
	{/if}
	{if $forum_mode eq 'y' and $forum_info.vote_threads eq 'y' or $forum_mode neq 'y'}
		<span class="score">
		<b>{tr}score{/tr}</b>: {$comment.average|string_format:"%.2f"}
		{if $comment.userName ne $user and (
			   ( $forum_mode neq 'y' and $tiki_p_vote_comments eq 'y' )
			or ( $forum_mode eq 'y' and $forum_info.vote_threads eq 'y' and ( $tiki_p_forum_vote eq 'y' or $tiki_p_admin_forum eq 'y' ) )
		) }
		<b>{tr}Vote{/tr}</b>:

		{if $first eq 'y'}
		<a class="link" href="tiki-view_forum_thread.php?topics_offset={$smarty.request.topics_offset}{$topics_sort_mode_param}{$topics_threshold_param}{$topics_find_param}&amp;comments_parentId={$comments_parentId}&amp;forumId={$forum_info.forumId}{$comments_threshold_param}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=1&amp;comments_offset={$comments_offset}{$comments_sort_mode_param}{$comments_maxComments_param}&amp;comments_parentId={$comments_parentId}">1</a>
		<a class="link" href="tiki-view_forum_thread.php?topics_offset={$smarty.request.topics_offset}{$topics_sort_mode_param}{$topics_threshold_param}{$topics_find_param}&amp;comments_parentId={$comments_parentId}&amp;forumId={$forum_info.forumId}{$comments_threshold_param}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=2&amp;comments_offset={$comments_offset}{$comments_sort_mode_param}{$comments_maxComments_param}&amp;comments_parentId={$comments_parentId}">2</a>
		<a class="link" href="tiki-view_forum_thread.php?topics_offset={$smarty.request.topics_offset}{$topics_sort_mode_param}{$topics_threshold_param}{$topics_find_param}&amp;comments_parentId={$comments_parentId}&amp;forumId={$forum_info.forumId}{$comments_threshold_param}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=3&amp;comments_offset={$comments_offset}{$comments_sort_mode_param}{$comments_maxComments_param}&amp;comments_parentId={$comments_parentId}">3</a>
		<a class="link" href="tiki-view_forum_thread.php?topics_offset={$smarty.request.topics_offset}{$topics_sort_mode_param}{$topics_threshold_param}{$topics_find_param}&amp;comments_parentId={$comments_parentId}&amp;forumId={$forum_info.forumId}{$comments_threshold_param}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=4&amp;comments_offset={$comments_offset}{$comments_sort_mode_param}{$comments_maxComments_param}&amp;comments_parentId={$comments_parentId}">4</a>
		<a class="link" href="tiki-view_forum_thread.php?topics_offset={$smarty.request.topics_offset}{$topics_sort_mode_param}{$topics_threshold_param}{$topics_find_param}&amp;comments_parentId={$comments_parentId}&amp;forumId={$forum_info.forumId}{$comments_threshold_param}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=5&amp;comments_offset={$comments_offset}{$comments_sort_mode_param}{$comments_maxComments_param}&amp;comments_parentId={$comments_parentId}">5</a>
		{else}
		<a class="link" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=1&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_parentId={$comments_parentId}&amp;comments_style={$comments_style}">1</a>
		<a class="link" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=2&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_parentId={$comments_parentId}&amp;comments_style={$comments_style}">2</a>
		<a class="link" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=3&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_parentId={$comments_parentId}&amp;comments_style={$comments_style}">3</a>
		<a class="link" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=4&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_parentId={$comments_parentId}&amp;comments_style={$comments_style}">4</a>
		<a class="link" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_threadId={$comment.threadId}&amp;comments_vote=5&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_parentId={$comments_parentId}&amp;comments_style={$comments_style}">5</a>
		{/if}

		{/if}
		</span>
	{/if}
	
	{if $first eq 'y'}
                <span class="post_reads"><b>{tr}reads{/tr}</b>: {$comment.hits}</span>
	{else}
		<span class="back_to_top"><a href="#tiki-top">{html_image file="img/icon_back_top.gif" border='0' alt="{tr}top of page{/tr}"}</a></span>
	{/if}

	</div>

	{if $comments_style != 'commentStyle_headers'}
	<div class="actions">
		{if ( $forum_mode neq 'y' and $tiki_p_post_comments == 'y' )
			or ( $forum_mode eq 'y' and $tiki_p_forum_post eq 'y' )
		}
			{if $forum_mode neq 'y'}
			<a class="linkbut" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_reply_threadId={$comment.threadId}&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_grandParentId={$comment.parentId}&amp;comments_parentId={$comment.threadId}&amp;comments_style={$comments_style}&amp;post_reply=1#form">{tr}reply{/tr}</a>
			{else}
				{if $first eq 'y'}
				<a class="linkbut" href="#form">{tr}reply{/tr}</a>
				{elseif $comments_grandParentId}
				<a class="linkbut" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_reply_threadId={$comment.threadId}&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_grandParentId={$comments_grandParentId}&amp;comments_parentId={$comments_grandParentId}&amp;comments_style={$comments_style}&amp;post_reply=1#form">{tr}reply{/tr}</a>
				{else}
				<a class="linkbut" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_reply_threadId={$comment.threadId}&amp;comments_offset={$comments_offset}&amp;comments_sort_mode={$comments_sort_mode}&amp;comments_maxComments={$comments_maxComments}&amp;comments_grandParentId={$comment.parentId}&amp;comments_parentId={$comment.parentId}&amp;comments_style={$comments_style}&amp;post_reply=1#form">{tr}reply{/tr}</a>
				{/if}
			{/if}
		{/if}
	</div>
	{/if}

</div>
