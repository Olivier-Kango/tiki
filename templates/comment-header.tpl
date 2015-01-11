{* $Id$ *}
<header class="panel-heading clearfix postbody-title">
	{if $prefs.feature_comments_locking neq 'y' or
		( $comment.locked neq 'y' and $thread_is_locked neq 'y' )}
		{assign var='this_is_locked' value='n'}
	{else}
		{assign var='this_is_locked' value='y'}
	{/if}

	{if $thread_style != 'commentStyle_headers' and $this_is_locked eq 'n' and isset($comment.threadId) and $comment.threadId > 0}
	<div class="actions pull-right btn-group">
		{if $comment.threadId eq $comments_parentId}
			{* Only on the main forum topic *}
			{monitor_link type="forum post" object=$comments_parentId}
		{/if}
		{if $tiki_p_admin_forum eq 'y'
			|| ( $comment.userName == $user && $tiki_p_forum_edit_own_posts eq 'y' )}
		<a title="{$comment.title|escape|replace:':':' '}:{tr}Edit{/tr}"
			{if $first eq 'y'}
			class="tips" href="tiki-view_forum.php?comments_offset={$smarty.request.topics_offset}{if isset($thread_sort_mode_param)}{$thread_sort_mode_param}{/if}&amp;comments_threshold={$smarty.request.topics_threshold}{if isset($comments_find_param)}{$comments_find_param}{/if}&amp;comments_threadId={$comment.threadId}&amp;openpost=1&amp;forumId={$forum_info.forumId}{if isset($comments_per_page_param)}{$comments_per_page_param}{/if}"
			{else}
			class="tips" href="{$comments_complete_father}comments_threadId={$comment.threadId}&amp;comments_threshold={$comments_threshold}&amp;comments_offset={$comments_offset}&amp;thread_sort_mode={$thread_sort_mode}&amp;comments_per_page={$comments_per_page}&amp;comments_parentId={$comments_parentId}&amp;thread_style={$thread_style}&amp;edit_reply=1#form"
			{/if}
		>{icon name="edit"}</a>
		{/if}

		{if $tiki_p_admin_forum eq 'y'}
		<a title="{$comment.title|escape|replace:':':' '}:{tr}Delete{/tr}"
			{if $first eq 'y'}
			class="tips" href="tiki-view_forum.php?comments_offset={$smarty.request.topics_offset}{if isset($thread_sort_mode_param)}{$thread_sort_mode_param}{/if}&amp;comments_threshold={$smarty.request.topics_threshold}{if isset($comments_find_param)}{$comments_find_param}{/if}&amp;comments_remove=1&amp;comments_threadId={$comment.threadId}&amp;forumId={$forum_info.forumId}{if isset($comments_per_page_param)}{$comments_per_page_param}{/if}"
			{else}
			class="tips" href="{$comments_complete_father}comments_threshold={$comments_threshold}&amp;comments_threadId={$comment.threadId}&amp;comments_remove=1&amp;comments_offset={$comments_offset}&amp;thread_sort_mode={$thread_sort_mode}&amp;comments_per_page={$comments_per_page}&amp;comments_parentId={$comments_parentId}&amp;thread_style={$thread_style}"
			{/if}
		>{icon name="delete"}</a>
		{/if}

		{if $tiki_p_forums_report eq 'y'}
			{self_link report=$comment.threadId _icon_name='error' _class="tips" _alt="{tr}Report this post{/tr}" _title="{$comment.title|escape|replace:':':' '}:{tr}Report{/tr}"}{/self_link}
		{/if}

		{if $user and $prefs.feature_notepad eq 'y' and $tiki_p_notepad eq 'y' and $forumId}
			{self_link savenotepad=$comment.threadId _icon_name='floppy' _class="tips" _alt="{tr}Save to notepad{/tr}" _title="{$comment.title|escape|replace:':':' '}:{tr}Save to notepad{/tr}"}{/self_link}
		{/if}

		{if $user and $prefs.feature_user_watches eq 'y' and $display eq ''}
		{if $first eq 'y'}
		{if $user_watching_topic eq 'n'}
			{self_link watch_event='forum_post_thread' watch_object=$comments_parentId watch_action='add' _class="tips" _icon_name='watch' _title="{$comment.title|escape|replace:':':' '}:{tr}Monitor{/tr}"}{/self_link}
		{else}
			{self_link watch_event='forum_post_thread' watch_object=$comments_parentId watch_action='remove' _class="tips" _icon_name='stop-watching' _title="{$comment.title|escape|replace:':':' '}:{tr}Stop monitoring{/tr}"}{/self_link}
		{/if}
		{if $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
			<a class="tips" href="tiki-object_watches.php?objectId={$comments_parentId|escape:"url"}&amp;watch_event=forum_post_thread&amp;objectType=forum&amp;objectName={$comment.title|escape:"url"}&amp;objectHref={'tiki-view_forum_thread.php?comments_parentId='|cat:$comments_parentId|cat:'&forumId='|cat:$forumId|escape:"url"}" class="icon" title="{$comment.title|escape|replace:':':' '}:{tr}Group monitor{/tr}">{icon name="watch-group"}</a>
		{/if}
		{/if}
		<br>
		{if $category_watched eq 'y'}
			{tr}Watched by categories:{/tr}
			{section name=i loop=$watching_categories}
				<a href="tiki-browse_categories.php?parentId={$watching_categories[i].categId}">{$watching_categories[i].name|escape}</a>&nbsp;
			{/section}
		{/if}
		{/if}
	</div>
	{/if}

	{if !isset($first) or $first neq 'y'}
	<div>
		{if $tiki_p_admin_forum eq 'y' and isset($comment.threadId) and $comment.threadId > 0}
		<input type="checkbox" name="forumtopic[]" value="{$comment.threadId|escape}" {if $smarty.request.forumthread and in_array($comment.threadId,$smarty.request.forumthread)}checked="checked"{/if}>
		{/if}
	</div>
	{/if}

	{if $comment.title neq '' && $comment.title neq 'Untitled' && (!isset($page) or $comment.title neq $page)}
	<!-- <div class="title"> -->
	{if isset($first) and $first eq 'y'}
		<h2 class=" panel-title">{$comment.title|escape}</h2>
	{/if}

	<!-- </div> -->
	{/if}

	{if $thread_style eq 'commentStyle_headers'}
		{include file='comment-footer.tpl' comment=$comments_coms[rep]}
	{/if}
</header>
