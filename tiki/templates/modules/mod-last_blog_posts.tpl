{* $Header: /cvsroot/tikiwiki/tiki/templates/modules/mod-last_blog_posts.tpl,v 1.17 2007-02-18 11:21:16 mose Exp $ *}

{if $feature_blogs eq 'y'}
{if !isset($tpl_module_title)}
{if $nonums eq 'y'}
{eval var="{tr}Last `$module_rows` blog posts{/tr}" assign="tpl_module_title"}
{else}
{eval var="{tr}Last blog posts{/tr}" assign="tpl_module_title"}
{/if}
{/if}
{tikimodule title=$tpl_module_title name="last_blog_posts" flip=$module_params.flip decorations=$module_params.decorations}
  <table  border="0" cellpadding="0" cellspacing="0">
  {section name=ix loop=$modLastBlogPosts}
    <tr>
       {if $nonums != 'y'}<td class="module" valign="top">{$smarty.section.ix.index_next})</td>{/if}
       <td class="module">
         <a class="linkmodule" href="tiki-view_blog.php?blogId={$modLastBlogPosts[ix].blogId}">
           <b>{$modLastBlogPosts[ix].blogTitle}</b>: {$modLastBlogPosts[ix].title}<br />
           {if $modLastBlogPostsTitle eq "title" and $modLastBlogPosts[ix].title}
             {$modLastBlogPosts[ix].title}
           {else}
             {$modLastBlogPosts[ix].created|tiki_short_datetime}
           {/if}
         </a>
       </td>
     </tr>
  {/section}
  </table>
{/tikimodule}
{/if}
