<div class="postfooter card-footer clearfix">
    <div class="status float-sm-end">
    {if $prefs.feature_contribution eq 'y' and $prefs.feature_contribution_display_in_comment eq 'y'}
        <span class="contributions">
        {section name=ix loop=$comment.contributions}
            <span class="contribution">{$comment.contributions[ix].name|escape}</span>
        {/section}
        </span>
    {/if}
    {if $forum_info.vote_threads eq 'y' and ($tiki_p_ratings_view_results eq 'y' or $tiki_p_admin eq 'y')}
        <span class="ratingResultAvg">{tr}Users rating: {/tr}</span>{rating_result_avg type=comment id=$comment.threadId }
    {/if}
    {if $forum_info.vote_threads eq 'y' and $tiki_p_forum_vote eq 'y'}
        <span class="score">
        <b>{tr}Score{/tr}</b>: {$comment.average|string_format:"%.2f"}
        {if $comment.userName ne $user and $comment.approved eq 'y' and $forum_info.vote_threads eq 'y' and ( $tiki_p_forum_vote eq 'y' or $tiki_p_admin_forum eq 'y' )}
        <b>{tr}Vote{/tr}</b>:

            <form method="post" action="">
                {rating type=comment id=$comment.threadId changemandated=y}
            </form>

        {/if}
        </span>
    {/if}
    {if $forum_info.vote_threads eq 'y' and ($tiki_p_ratings_view_results eq 'y' or $tiki_p_admin eq 'y')}
        {rating_result type=comment id=$comment.threadId }
    {/if}

    {if isset($first) and $first eq 'y'}
        <span class="post_reads">
            <b>{tr}Reads{/tr}</b>: {$comment.hits}
        </span>
    {else}
        <span class="back_to_top">
            <a href="#top" class="tips" title=":{tr}top of page{/tr}" aria-label="{tr}top of page{/tr}">
                {icon name='arrow-up'}
            </a>
        </span>
    {/if}

    </div>

    {if $thread_style != 'commentStyle_headers' and isset($comment.approved) and $comment.approved eq 'y'}
    <div class="actions">
        {if ($prefs.feature_sefurl eq 'y') }
            <a href="{$comments_parentId|sefurl:'forum post'}{if ($comment.threadId neq $comments_parentId)}#threadId{$comment.threadId}{/if}" class="btn btn-outline-primary me-2" title="{tr}Link to this comment{/tr}" aria-label="{tr}Link to this comment{/tr}" style="font-size: 1rem">{icon name="link"}</a>
        {else}
            <a href="tiki-view_forum_thread.php?comments_parentId={$comments_parentId}#threadId{$comment.threadId}">{tr}Link to this comment{/tr}</a>
        {/if}

        {if ( $prefs.feature_comments_locking neq 'y' or $thread_is_locked neq 'y' ) and
            ( $tiki_p_forum_post eq 'y' and ( $forum_is_locked neq 'y' or $prefs.feature_comments_locking neq 'y' ) )}
            {if $first eq 'y'}
                {button href="#form" _onclick="show('`$postclass`open');" _text="{tr}Reply{/tr}"}
            {elseif $comments_grandParentId}
                {button href="?post_reply=1&comments_threshold=`$comments_threshold`&comments_reply_threadId=`$comment.threadId`&comments_offset=`$comments_offset`&thread_sort_mode=`$thread_sort_mode`&comments_per_page=`$comments_per_page`&comments_grandParentId=`$comment_grandParentId`&comments_parentId=`$comments_grandParentId`&thread_style=`$thread_style`#form" _auto_args='*' _text="{tr}Reply{/tr}"}
            {elseif $forum_info.is_flat neq 'y' or $prefs.feature_forum_allow_flat_forum_quotes eq 'y'}
                {button href="?post_reply=1&comments_threshold=`$comments_threshold`&comments_reply_threadId=`$comment.threadId`&comments_offset=`$comments_offset`&thread_sort_mode=`$thread_sort_mode`&comments_per_page=`$comments_per_page`&comments_grandParentId=`$comment.parentId`&comments_parentId=`$comment.parentId`&thread_style=`$thread_style`#form" _auto_args='*' _text="{tr}Reply{/tr}"}
            {/if}
        {/if}
    </div>
    {/if}

</div>
