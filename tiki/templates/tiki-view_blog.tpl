<div class="blogtitle">Blog: {$title}</div>
<div class="bloginfo">
{tr}Created by{/tr} {$creator}{tr} on {/tr}{$created|tiki_short_datetime}<br/>
{tr}Last modified{/tr} {$lastModif|tiki_short_datetime}<br/><br/>
({$posts} {tr}posts{/tr} | {$hits} {tr}visits{/tr} | {tr}Activity={/tr}{$activity|string_format:"%.2f"})
{if $tiki_p_blog_post eq 'y'}
{if ($user and $creator eq $user) or $tiki_p_blog_admin eq 'y' or $public eq 'y'}
[<a class="bloglink" href="tiki-blog_post.php?blogId={$blogId}">{tr}Post{/tr}</a>]
{/if}
{if $rss_blog eq 'y'}
[<a class="bloglink" href="tiki-blog_rss.php?blogId={$blogId}">RSS</a>]
{/if}
{/if}
{if ($user and $creator eq $user) or $tiki_p_blog_admin eq 'y'}
[<a class="bloglink" href="tiki-edit_blog.php?blogId={$blogId}">{tr}Edit{/tr}</a>]
{/if}
</div>

<div class="blogdesc">{tr}Description:{/tr}{$description}</div>
<div class="blogtools">
<table><tr><td>
<form action="tiki-view_blog.php" method="get">
<input type="hidden" name="sort_mode" value="{$sort_mode}" />
<input type="hidden" name="blogId" value="{$blogId}" />
{tr}Find:{/tr}<input type="text" name="find" /><input type="submit" name="search" value="{tr}find{/tr}" />
</form>
</td><td>
{tr}Sort posts by:{/tr}
<a class="bloglink" href="tiki-view_blog.php?blogId={$blogId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Date{/tr}</a>
</td></tr></table>
</div>
{section name=ix loop=$listpages}
<div>
<div class="posthead">
<table width="100%"><tr><td align="left">
<span class="posthead">{$listpages[ix].created|tiki_short_datetime}</span>
</td><td align="right">
{if ($ownsblog eq 'y') or ($user and $listpages[ix].user eq $user) or $tiki_p_blog_admin eq 'y'}
<a class="blogt" href="tiki-blog_post.php?blogId={$listpages[ix].blogId}&amp;postId={$listpages[ix].postId}">{tr}Edit{/tr}</a>
<a class="blogt" href="tiki-view_blog.php?blogId={$blogId}&amp;remove={$listpages[ix].postId}">{tr}Remove{/tr}</a>
{/if}
</td></tr></table>
</div>
<div class="postbody">
{$listpages[ix].parsed_data}
{if $feature_blogposts_comments eq 'y'}
<hr/>
{$listpages[ix].comments} {tr}comments{/tr}
 [<a class="link" href="tiki-view_blog_post.php?find={$find}&amp;blogId={$blogId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;postId={$listpages[ix].postId}">{tr}view comments{/tr}</a>]
{/if}
</div>
</div>
{/section}
<br/><br/>
<div align="center">
<div class="mini">
{if $prev_offset >= 0}
[<a class="blogprevnext" href="tiki-view_blog.php?find={$find}&amp;blogId={$blogId}&amp;offset={$prev_offset}&amp;sort_mode={$sort_mode}">{tr}prev{/tr}</a>]&nbsp;
{/if}
{tr}Page{/tr}: {$actual_page}/{$cant_pages}
{if $next_offset >= 0}
&nbsp;[<a class="blogprevnext" href="tiki-view_blog.php?find={$find}&amp;blogId={$blogId}&amp;offset={$next_offset}&amp;sort_mode={$sort_mode}">{tr}next{/tr}</a>]
{/if}
{if $direct_pagination eq 'y'}
<br/>
{section loop=$cant_pages name=foo}
{assign var=selector_offset value=$smarty.section.foo.index|times:$maxRecords}
<a class="prevnext" href="tiki-view_blog.php?find={$find}&amp;blogId={$blogId}&amp;offset={$selector_offset}&amp;sort_mode={$sort_mode}">
{$smarty.section.foo.index_next}</a>&nbsp;
{/section}
{/if}
</div>
</div>
{if $feature_blog_comments eq 'y'}
{include file=comments.tpl}
{/if}

