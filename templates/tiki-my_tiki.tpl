{title help="My Account"}{tr}My Account{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}
<br>

{capture name=my}
    {tabset name="mytiki"}
        {if $prefs.feature_wiki eq 'y' and $mytiki_pages eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My pages{/tr}{else}{tr}User Pages{/tr}{/if}"}
                <div id="content1" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My pages{/tr}{else}{tr}User Pages{/tr}{/if} <span class="badge bg-secondary">{$user_pages_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tr>
                                <th>
                                    <a href="tiki-my_tiki.php?sort_mode={if $sort_mode eq 'pageName_desc'}pageName_asc{else}pageName_desc{/if}">{tr}Page{/tr}</a>
                                </th>
                                <th>{tr}Creator{/tr}</th>
                                <th>{tr}Last editor{/tr}</th>
                                <th>
                                    <a href="tiki-my_tiki.php?sort_mode={if $sort_mode eq 'date_desc'}date_asc{else}date_desc{/if}">{tr}Last modification{/tr}</a>
                                </th>
                                <th></th>
                            </tr>

                            {section name=ix loop=$user_pages}
                                <tr>
                                    <td class="text">
                                        <a class="tips" title=":{tr}View{/tr}" href="tiki-index.php?page={$user_pages[ix].pageName|escape:"url"}">{$user_pages[ix].pageName|truncate:40:"(...)"}</a>
                                    </td>
                                    <td class="username">
                                        {if $userwatch eq $user_pages[ix].creator}{tr}y{/tr}{else}&nbsp;{/if}
                                    </td>
                                    <td class="username">
                                        {if $userwatch eq $user_pages[ix].lastEditor}{tr}y{/tr}{else}&nbsp;{/if}
                                    </td>
                                    <td class="date">
                                        {$user_pages[ix].date|tiki_short_datetime}
                                    </td>
                                    <td class="action">
                                        <a class="tips" href="tiki-editpage.php?page={$user_pages[ix].pageName|escape:"url"}" title=":{tr}Edit{/tr}">
                                            {icon name='edit'}
                                        </a>
                                    </td>
                                </tr>
                            {/section}
                        </table>
                    </div>
                    {pagination_links cant=$user_pages_count step=$step offset=$pages_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_articles eq 'y' and $mytiki_articles eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My Articles{/tr}{else}{tr}User Articles{/tr}{/if}"}
                <div id="content3" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My Articles{/tr}{else}{tr}User Articles{/tr}{/if} <span class="badge bg-secondary">{$user_articles_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tr>
                                <th>{tr}Article{/tr}</th>
                                <th style="width:50px"></th>
                            </tr>

                            {section name=ix loop=$user_articles}
                                <tr>
                                    <td class="text">
                                        <a class="tips" title=":{tr}Edit{/tr}" href="{$user_articles[ix].articleId|sefurl:article}">
                                            {$user_articles[ix].title}
                                        </a>
                                    </td>
                                    <td class="action">
                                        <a class="tips" href="tiki-edit_article.php?articleId={$user_articles[ix].articleId}" title=":{tr}Edit{/tr}">
                                            {icon name='edit'}
                                        </a>
                                    </td>
                                </tr>
                            {/section}
                        </table>
                    </div>
                    {pagination_links cant=$user_articles_count step=$step offset=$articles_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_trackers eq 'y' and $mytiki_user_items eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My User Items{/tr}{else}{tr}User Items{/tr}{/if}"}
                <div id="content4" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My User Items{/tr}{else}{tr}User Items{/tr}{/if} <span class="badge bg-secondary">{$user_items_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tr>
                                <th>{tr}Item{/tr}</th>
                                <th>{tr}Tracker{/tr}</th>
                            </tr>

                            {section name=ix loop=$user_items}
                                <tr>
                                    <td class="text">
                                        <a class="tips" title=":{tr}View{/tr}" href="tiki-view_tracker_item.php?trackerId={$user_items[ix].trackerId}&amp;itemId={$user_items[ix].itemId}">
                                            {$user_items[ix].value}
                                        </a>
                                    </td>
                                    <td class="text">
                                        <a class="tips" title=":{tr}View{/tr}" href="tiki-view_tracker.php?trackerId={$user_items[ix].trackerId}">
                                            {tr}{$user_items[ix].name}{/tr}
                                        </a>
                                    </td>
                                </tr>
                            {/section}
                        </table>
                    </div>
                    <ul class="nav nav-pills float-end">
                        <li><a href="#">{tr}Records{/tr} <span class="badge bg-secondary">{$user_items|@count}</span></a>&nbsp;</li>
                        <li><a href="#">{tr}Comments{/tr} <span class="badge bg-secondary">{$nb_item_comments}</span></a></li>
                    </ul>
                    {pagination_links cant=$user_items_count step=$step offset=$user_items_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_messages eq 'y' and $mytiki_msgs eq 'y'}
            {tab name="{tr}Unread Messages{/tr}"}
                <div id="content5" class="content clearfix mb-4">
                    <h4>{tr}Unread Messages{/tr} <span class="badge bg-secondary">{$msgs_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tr>
                                <th>{tr}Subject{/tr}</th>
                                <th>{tr}From{/tr}</th>
                                <th>{tr}Date{/tr}</th>
                            </tr>

                            {section name=ix loop=$msgs}
                                <tr>
                                    <td class="text">
                                        <a class="tips" title=":{tr}View{/tr}" href="messu-read.php?offset=0&amp;flag=&amp;flagval=&amp;find=&amp;sort_mode=date_desc&amp;priority=&amp;msgId={$msgs[ix].msgId}">
                                            {$msgs[ix].subject}
                                        </a>
                                    </td>
                                    <td class="text">
                                        {$msgs[ix].user_from}
                                    </td>
                                    <td class="date">
                                        {$msgs[ix].date|tiki_short_datetime}
                                    </td>
                                </tr>
                            {/section}
                        </table>
                    </div>
                    {pagination_links cant=$msgs_count step=$step offset=$msgs_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_tasks eq 'y' and $mytiki_tasks eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My tasks{/tr}{else}{tr}My Tasks{/tr}{/if}"}
                <div id="content6" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My tasks{/tr}{else}{tr}My Tasks{/tr}{/if} <span class="badge bg-secondary">{$tasks_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                        <tr>
                            <th>{tr}Tasks{/tr}</th>
                        </tr>

                        {section name=ix loop=$tasks}
                            <tr>
                                <td class="text">
                                    <a href="tiki-user_tasks.php?taskId={$tasks[ix].taskId}">
                                        {$tasks[ix].title}
                                    </a>
                                </td>
                            </tr>
                        {/section}
                    </table>
                    </div>
                    {pagination_links cant=$tasks_count step=$step offset=$tasks_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_forums eq 'y' && $mytiki_forum_topics eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My forum topics{/tr}{else}{tr}User forum topics{/tr}{/if}"}
                <div id="content7" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My forum topics{/tr}{else}{tr}User forum topics{/tr}{/if} <span class="badge bg-secondary">{$forum_topics_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tr>
                                <th>{tr}Forum topic{/tr}</th>
                                <th>{tr}Date of post{/tr}</th>
                            </tr>

                            {section name=ix loop=$user_forum_topics}
                                <tr>
                                    <td class="text">
                                        <a class="tips" title=":{tr}View{/tr}" href="tiki-view_forum_thread.php?comments_parentId={$user_forum_topics[ix].threadId}">
                                            {$user_forum_topics[ix].title}
                                        </a>
                                    </td>
                                    <td class="date">
                                        {$user_forum_topics[ix].commentDate|tiki_short_datetime}
                                    </td>
                                </tr>
                            {/section}
                        </table>
                    </div>
                    {pagination_links cant=$forum_topics_count step=$step offset=$forum_topics_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_forums eq 'y' && $mytiki_forum_replies eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My forum replies{/tr}{else}{tr}User forum replies{/tr}{/if}"}
                <div id="content8" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My forum replies{/tr}{else}{tr}User forum replies{/tr}{/if} <span class="badge bg-secondary">{$forum_replies_count}</span></h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tr>
                                <th>{tr}Forum reply{/tr}</th>
                                <th>{tr}Date of post{/tr}</th>
                            </tr>

                            {section name=ix loop=$user_forum_replies}
                                <tr>
                                    <td class="text">
                                        <a class="tips" title=":{tr}View{/tr}" href="tiki-view_forum_thread.php?comments_parentId={$user_forum_replies[ix].threadId}&amp;forumId={$user_forum_replies[ix].object}">
                                            {$user_forum_replies[ix].title}
                                        </a>
                                    </td>
                                    <td class="date">
                                        {$user_forum_replies[ix].commentDate|tiki_short_datetime}
                                    </td>
                                </tr>
                            {/section}
                        </table>
                    </div>
                    {pagination_links cant=$forum_replies_count step=$step offset=$forum_replies_offset clean='y'}{/pagination_links}
                </div>
            {/tab}
        {/if}

        {if $prefs.feature_blogs eq 'y' && $mytiki_blogs eq 'y'}
            {tab name="{if $userwatch eq $user}{tr}My blogs{/tr}{else}{tr}User Blogs{/tr}{/if}"}
                <div id="content9" class="content clearfix mb-4">
                    <h4>{if $userwatch eq $user}{tr}My blogs{/tr}{else}{tr}User Blogs{/tr}{/if} <span class="badge bg-secondary">{$user_blogs|@count}</span></h4>
                    <div class="clearfix mb-4">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <tr>
                                    <th>{tr}Blog{/tr}</th>
                                    <th style="width:50px"></th>
                                </tr>

                                {section name=ix loop=$user_blogs}
                                    <tr>
                                        <td class="text">
                                            <a class="tips" title=":{tr}View{/tr}" href="{$user_blogs[ix].blogId|sefurl:blog}">{$user_blogs[ix].title}</a>
                                        </td>
                                        <td class="action">
                                            <a class="tips" href="tiki-edit_blog.php?blogId={$user_blogs[ix].blogId}" title=":{tr}Edit{/tr}">
                                                {icon name='edit'}
                                            </a>
                                        </td>
                                    </tr>
                                {/section}
                            </table>
                        </div>
                    </div>
                    <div class="clearfix">
                        <h4>{if $userwatch eq $user}{tr}My blog Posts{/tr}{else}{tr}User Blog Posts{/tr}{/if} <span class="badge bg-secondary">{$user_posts_count}</span></h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <tr>
                                    <th>{tr}Blog post{/tr}</th>
                                    <th style="width:50px"></th>
                                </tr>

                                {section name=ix loop=$user_blog_posts}
                                    <tr>
                                        <td class="text">
                                            <a class="tips" title=":{tr}View{/tr}" href="{$user_blog_posts[ix].postId|sefurl:blogpost}">{$user_blog_posts[ix].title|escape}</a>
                                        </td>
                                        <td class="action">
                                            <a class="tips" href="tiki-blog_post.php?postId={$user_blog_posts[ix].postId}" title=":{tr}Edit{/tr}">
                                                {icon name='edit'}
                                            </a>
                                        </td>
                                    </tr>
                                {/section}
                            </table>
                        </div>
                        {pagination_links cant=$user_posts_count step=$step offset=$posts_offset clean='y'}{/pagination_links}
                    </div>
                </div>
            {/tab}
        {/if}

    {/tabset}
{/capture}

{$smarty.capture.my}
{if $smarty.capture.my|strip:'' eq ''}
    <div class="description d-inline me-2">{tr}To display the objects you created or contributed to:{/tr}</div> <a href="tiki-user_information.php#contentuser_information-2">{tr}My Items{/tr}</a>
{/if}
