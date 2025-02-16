{title help="Blogs" admpage="blogs"}{tr}Blogs{/tr}{/title}

<div class="t_navbar mb-4">
    {if $tiki_p_create_blogs eq 'y' or $tiki_p_blog_admin eq 'y'}
        {button href="tiki-edit_blog.php" _icon_name="create" _text="{tr}Create Blog{/tr}" _type="link" class="btn btn-link"}
        {if $tiki_p_read_blog eq 'y' and $tiki_p_blog_admin eq 'y'}
            {button href="tiki-list_posts.php" _type="link" class="btn btn-link" _icon_name="list" _text="{tr}List Posts{/tr}"}
        {/if}
    {/if}
</div>

{if $listpages or ($find ne '')}
    {include file='find.tpl'}
{/if}

<div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
    <table class="table table-striped normal">
        {assign var=numbercol value=0}
        <tr>
            {if $prefs.blog_list_title eq 'y' or $prefs.blog_list_description eq 'y'}
                {assign var=numbercol value=$numbercol+1}
                <th><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_desc'}title_asc{else}title_desc{/if}">{tr}Blog{/tr}</a></th>
            {/if}
            {if $prefs.blog_list_created eq 'y'}
                {assign var=numbercol value=$numbercol+1}
                <th><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Created{/tr}</a></th>
            {/if}
            {if $prefs.blog_list_lastmodif eq 'y'}
                {assign var=numbercol value=$numbercol+1}
                <th><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'lastModif_desc'}lastModif_asc{else}lastModif_desc{/if}">{tr}Last post{/tr}</a></th>
            {/if}
            {if $prefs.blog_list_user ne 'disabled'}
                {assign var=numbercol value=$numbercol+1}
                <th><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'user_desc'}user_asc{else}user_desc{/if}">{tr}User{/tr}</a></th>
            {/if}
            {if $prefs.blog_list_posts eq 'y'}
                {assign var=numbercol value=$numbercol+1}
                <th class="text-end"><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'posts_desc'}posts_asc{else}posts_desc{/if}">{tr}Posts{/tr}</a></th>
            {/if}
            {if $prefs.blog_list_visits eq 'y'}
                {assign var=numbercol value=$numbercol+1}
                <th class="text-end"><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'hits_desc'}hits_asc{else}hits_desc{/if}">{tr}Visits{/tr}</a></th>
            {/if}
            {if $prefs.blog_list_activity eq 'y'}
                {assign var=numbercol value=$numbercol+1}
                <th><a href="tiki-list_blogs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'activity_desc'}activity_asc{else}activity_desc{/if}">{tr}Activity{/tr}</a></th>
            {/if}
            {assign var=numbercol value=$numbercol+1}
            <td></td> {* empty th changed to td to avoid ARIA error *}
        </tr>
        {section name=changes loop=$listpages}
            <tr>
                {if $prefs.blog_list_title eq 'y' or $prefs.blog_list_description eq 'y'}
                    <td class="text">
                        {if ($tiki_p_admin eq 'y') or ($listpages[changes].individual eq 'n') or ($listpages[changes].individual_tiki_p_read_blog eq 'y' )}
                            <a class="blogname" href="{$listpages[changes].blogId|sefurl:blog}">
                        {/if}
                        {if $listpages[changes].title}
                            {$listpages[changes].title|truncate:$prefs.blog_list_title_len:"...":true|escape}
                        {else}
                            &nbsp;
                        {/if}
                        {if ($tiki_p_admin eq 'y') or ($listpages[changes].individual eq 'n') or ($listpages[changes].individual_tiki_p_read_blog eq 'y' )}
                            </a>
                        {/if}
                        {if $prefs.blog_list_description eq 'y'}
                            <div class="form-text">{$listpages[changes].description|escape|nl2br}</div>
                        {/if}
                    </td>
                {/if}
                {if $prefs.blog_list_created eq 'y'}
                    <td class="date">&nbsp;{$listpages[changes].created|tiki_short_date}&nbsp;</td>{*tiki_date_format:"%b %d"*}
                {/if}
                {if $prefs.blog_list_lastmodif eq 'y'}
                    <td class="date">&nbsp;{$listpages[changes].lastModif|tiki_short_datetime}&nbsp;</td>{*tiki_date_format:"%d of %b [%H:%M]"*}
                {/if}
                {if $prefs.blog_list_user ne 'disabled'}
                    {if $prefs.blog_list_user eq 'link'}
                        <td class="username">&nbsp;{$listpages[changes].user|userlink}&nbsp;</td>
                    {elseif $prefs.blog_list_user eq 'avatar'}
                        <td>&nbsp;{$listpages[changes].user|avatarize}&nbsp;<br>
                        &nbsp;{$listpages[changes].user|userlink}&nbsp;</td>
                    {else}
                        <td class="username">&nbsp;{$listpages[changes].user|escape}&nbsp;</td>
                    {/if}
                {/if}
                {if $prefs.blog_list_posts eq 'y'}
                    <td class="integer">{$listpages[changes].posts}</td>
                {/if}
                {if $prefs.blog_list_visits eq 'y'}
                    <td class="integer">{$listpages[changes].hits}</td>
                {/if}
                {if $prefs.blog_list_activity eq 'y'}
                    <td class="integer">{$listpages[changes].activity}</td>
                {/if}
                <td class="action">
                    {actions}
                        {strip}
                            {if ($tiki_p_admin eq 'y') or ($listpages[changes].individual eq 'n') or ($listpages[changes].individual_tiki_p_read_blog eq 'y' )}
                                <action>
                                    <a href="{$listpages[changes].blogId|sefurl:blog}">
                                        {icon name="view" _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                    </a>
                                </action>
                            {/if}
                            {if ($user and $listpages[changes].user eq $user) or ($tiki_p_blog_admin eq 'y')}
                                {if ($tiki_p_admin eq 'y') or ($listpages[changes].individual eq 'n') or ($listpages[changes].individual_tiki_p_blog_create_blog eq 'y' )}
                                    <action>
                                        <a href="tiki-edit_blog.php?blogId={$listpages[changes].blogId}">
                                            {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                        </a>
                                    </action>
                                {/if}
                            {/if}
                            {if $tiki_p_blog_post eq 'y'}
                                {if ($tiki_p_admin eq 'y') or ($listpages[changes].individual eq 'n') or ($listpages[changes].individual_tiki_p_blog_post eq 'y' )}
                                    {if ($user and $listpages[changes].user eq $user) or ($tiki_p_blog_admin eq 'y') or ($listpages[changes].public eq 'y')}
                                        <action>
                                            <a href="tiki-blog_post.php?blogId={$listpages[changes].blogId}">
                                                {icon name="post" _menu_text='y' _menu_icon='y' alt="{tr}Post{/tr}"}
                                            </a>
                                        </action>
                                    {/if}
                                {/if}
                            {/if}
                            {if $tiki_p_blog_admin eq 'y' and $listpages[changes].allow_comments eq 'y'}
                                <action>
                                    <a href='tiki-list_comments.php?types_section=blogs&amp;blogId={$listpages[changes].blogId}'>
                                        {icon name="comments" _menu_text='y' _menu_icon='y' alt="{tr}Comments{/tr}"}
                                    </a>
                                </action>
                            {/if}
                            {if $tiki_p_admin eq 'y' || $tiki_p_assign_perm_blog eq 'y'}
                                <action>
                                    {permission_link mode=text type="blog" permType="blogs" id=$listpages[changes].blogId}
                                </action>
                            {/if}
                            {if ($user and $listpages[changes].user eq $user) or ($tiki_p_blog_admin eq 'y')}
                                {if ($tiki_p_admin eq 'y') or ($listpages[changes].individual eq 'n') or ($listpages[changes].individual_tiki_p_blog_create_blog eq 'y' )}
                                    <action>
                                        <form action="tiki-list_blogs.php" method="post">
                                            {ticket}
                                            <input type="hidden" name="offset" value="{$offset}">
                                            <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                            <input type="hidden" name="remove" value="{$listpages[changes].blogId}">
                                            <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr _0=$listpages[changes].blogId}Are you sure you want to permanently remove the blog with identifier %0?{/tr}')">
                                                {icon name='delete' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                            </button>
                                        </form>
                                    </action>
                                {/if}
                            {/if}
                        {/strip}
                    {/actions}
                </td>
            </tr>
        {sectionelse}
            {norecords _colspan=$numbercol}
        {/section}
    </table>
</div>
{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
