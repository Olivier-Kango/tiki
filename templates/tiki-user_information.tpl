{capture assign="viewuser"}{$userwatch|escape:"url"}{/capture}
{title url="tiki-user_information.php?view_user=$viewuser"}{tr}User Information{/tr}{/title}

{if "$userwatch" eq "$user" }
    {include file='tiki-mytiki_bar.tpl'}
{/if}

{if $prefs.feature_tabs neq 'y' and $user and $prefs.feature_messages eq 'y' and $tiki_p_messages eq 'y' and $allowMsgs eq 'y'}
    <div class="t_navbar">
        {button href="#message" class="btn btn-primary" _text="{tr}Send a message{/tr}"}
    </div>
{/if}

{tabset name="user_information"}
    {tab name="{tr}Basic Data{/tr}"}
        {if $infoPublic eq 'y'}
            <h2>{$userinfo.login}
                <span class="float-end">
                {if $tiki_p_admin eq 'y' or $userinfo.login eq $user}
                    {if $tiki_p_admin eq 'y'}
                        <a class="link tips small" href="tiki-assignuser.php?assign_user={$userinfo.login|escape:"url"}" title=":{tr}Assign group{/tr}">
                            {icon name='group' align="right" alt="{tr}Assign Group{/tr}"}
                        </a>
                    {/if}
                    <a class="link tips small" href="tiki-user_preferences.php?userId={$userinfo.userId}" title=":{tr}Change user preferences{/tr}">
                        {icon name='wrench' align="right" alt="{tr}Change user preferences{/tr}"}
                    </a>
                {/if}
                </span>
            </h2>

            <div class="row">
                <div class="col-sm-9">
                    <div class="card">
                        <div class="card-body">
                            {if $avatar}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Profile picture{/tr} ({tr}User Icon{/tr}):</div>
                                    <div class="col-sm-8">
                                        <div class="col-sm-3">
                                            {if $userinfo.login eq $user}<a href="tiki-pick_avatar.php">{/if}
                                                {$avatar}
                                            {if $userinfo.login eq $user}</a>{/if}
                                        </div>
                                        <div class="col-sm-9">
                                            {if isset($user_picture_id)}
                                                <div class="userpicture">
                                                    {wikiplugin _name="img" fileId="$user_picture_id"}{/wikiplugin}
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            {/if}

                            {if $realName}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Real Name:{/tr}</div>
                                    <div class="col-sm-8">{$realName|escape}</div>
                                </div>
                            {/if}
                            {if $prefs.feature_community_gender eq 'y' and $gender neq 'Hidden' and $gender}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Gender:{/tr}</div>
                                    <div class="col-sm-8">{tr}{$gender}{/tr}</div>
                                </div>
                            {/if}
                            {if $email_isPublic neq 'n' and $userinfo.email neq ''}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Email:{/tr}</div>
                                    <div class="col-sm-8">{$scrambledEmail}</div>
                                </div>
                            {/if}
                            {if !empty($country) and $country != 'Other'}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Country:{/tr}</div>
                                    <div class="col-sm-8">{$userinfo.login|countryflag} {tr}{$country|stringfix}{/tr}</div>
                                </div>
                            {/if}
                            {if $prefs.change_theme ne 'n'}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Theme:{/tr}</div>
                                    <div class="col-sm-8">{$user_style}</div>
                                </div>
                            {/if}
                            {if $prefs.change_language eq 'y'}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Language:{/tr}</div>
                                    <div class="col-sm-8">{$user_language}</div>
                                </div>
                            {/if}
                            {if $homePage}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Homepage:{/tr}</div>
                                    <div class="col-sm-8">
                                        <a href="{$homePage|escape}" class="link" title="{tr}User's homepage{/tr}">
                                            {$homePage|escape}
                                        </a>
                                    </div>
                                </div>
                            {/if}
                            {if $prefs.feature_score eq 'y'}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Score:{/tr}</div>
                                    <div class="col-sm-8">{$userinfo.score|star}{$userinfo.score}</div>
                                </div>
                            {/if}
                            {if $prefs.feature_wiki eq 'y' && $prefs.feature_wiki_userpage eq 'y' && ($userPage_exists or $user == $userinfo.login)}
                                <div class="row mb-3">
                                    <div class="col-sm-4">{tr}Personal Wiki Page:{/tr}</div>
                                    <div class="col-sm-8">
                                        {if $userPage_exists}
                                            <a class="link" href="tiki-index.php?page={$prefs.feature_wiki_userpage_prefix|escape:'url'}{$userinfo.login|escape:'url'}">
                                                {$prefs.feature_wiki_userpage_prefix}{$userinfo.login}
                                            </a>
                                        {elseif $user == $userinfo.login}
                                            {$prefs.feature_wiki_userpage_prefix}{$userinfo.login}
                                            <a class="link" href="tiki-editpage.php?page={$prefs.feature_wiki_userpage_prefix|escape:'url'}{$userinfo.login|escape:'url'}"
                                                    title="{tr}Create Page{/tr}">?</a>
                                        {else}&nbsp;{/if}
                                    </div>
                                </div>
                            {/if}

                            <div class="row mb-3">
                                <div class="col-sm-4">{tr}Last login:{/tr}</div>
                                <div class="col-sm-8">{$userinfo.lastLogin|tiki_short_datetime}</div>
                            </div>

                            {* Custom database fields *}
                            {section name=ir loop=$customfields}

                                {if $customfields[ir].show}
                                    <div class="row mb-3">
                                        <div class="col-sm-4">{$customfields[ir].label}:</div>
                                        <div class="col-sm-8">{$customfields[ir].value}</div>
                                    </div>
                                {/if}
                            {/section}
                        </div>
                    </div>
                </div>
            </div>
            {if $prefs.feature_friends eq 'y'}
            <h3>{tr}Friendship Network{/tr}</h3>
                {if $user eq $userinfo.login}
            <h4>{tr}Your relationship with other users{/tr}</h4>
                    {module module=friend_list nobox=y}
                {else}
            <h4>{tr}Relationship of this user to you{/tr}</h4>
            <div id="friendship"></div>
            <div id="addfriend"></div>
                    {jq}
                        $('#friendship').load("tiki-user-info?username={{$userinfo.login}} .friendship");
                        $('#addfriend').load("tiki-user-info?username={{$userinfo.login}} .add-friend");
                    {/jq}
                {/if}
            {/if}

        {else}{* infoPublic eq 'n' *}
            <div>
                {remarksbox type="info" title="Private"}{tr}The user has chosen to make his information private{/tr}{/remarksbox}
            </div>
        {/if}
    {/tab}

    {if $prefs.user_tracker_infos and $infoPublic eq "y"}
        {tab name="{tr}Additional Information{/tr}"}
            <h2>{tr}Additional Information{/tr} – {$userinfo.login}</h2>
            <div class="card">
                <div class="card-body">

                    <div class="clearfix">
                        <div class="col-sm-8">
                            {if $userinfo.login eq $user}
                                <a class="link tips" href="tiki-view_tracker_item.php?view=+user&amp;cookietab=2" title=":{tr}Change user information{/tr}">
                                    {icon name='wrench' align="right" alt="{tr}Change user information{/tr}"}
                                </a>
                            {/if}
                        </div>
                    </div>

                    {foreach item=itemField from=$userItem.field_values}
                        {if $itemField.value ne '' or !empty($itemField.categs) or !empty($itemField.links)}
                            <div class="row">
                                <div class="col-sm-4" style="width: 25%">{$itemField.name}:</div>
                                <div class="col-sm-8" style="width: 75%">{trackeroutput field=$itemField item=$itemField}</div>
                            </div>
                        {/if}
                    {/foreach}

                </div>
            </div>
        {/tab}
    {/if}

    {if $prefs.feature_display_my_to_others eq 'y' and $infoPublic eq "y"}
        {tab name="{tr}User Contribution{/tr}"}
            <div class="card">
                <div class="card-body">
                    {if ($user_pages|@count > 0) or ($user_blogs|@count > 0) or ($user_blog_posts|@count > 0) or ($user_articles|@count > 0) or ($user_forum_comments|@count > 0) or ($user_forum_topics|@count > 0) or ($user_items|@count > 0)}
                        <h2 class="text-center">{tr}User{/tr} {$userinfo.login|userlink}</h2>
                        <p><em>{tr}has contributed to the following content{/tr}…</em></p>
                    {else}
                        <h2 class="text-center">{tr}User{/tr} {$userinfo.login|userlink}</h2>
                        <p><em>{tr}has not contributed to any content yet{/tr}</em></p>
                    {/if}

                    {if $user_pages|@count > 0}
                        <h3>{tr}Wiki Pages{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_pages as $page}
                                <li>
                                    <a class="link" title="{tr}View:{/tr} {$page.pageName|escape}" href="tiki-index.php?page={$page.pageName|escape:"url"}">{$page.pageName|truncate:40:"(...)"|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {if $user_blogs|@count > 0}
                        <h3>{tr}Blogs{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_blogs as $blog}
                                <li>
                                    <a class="link" title="{tr}View{/tr}" href="{$blog.blogId|sefurl:blog}">{$blog.title|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {if isset($user_blog_posts) && $user_blog_posts|@count > 0}
                        <h3>{tr}Blogs Posts{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_blog_posts as $post}
                                <li>
                                    <a class="link" title="{tr}View{/tr}" href="{$post.postId|sefurl:blogpost}">{$post.title|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {if $user_articles|@count > 0}
                        <h3>{tr}Articles{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_articles as $article}
                                <li>
                                    <a class="link" title="{tr}View{/tr}" href="{$article.articleId|sefurl:article}">{$article.title|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {if $user_forum_comments|@count > 0}
                        <h3>{tr}Forum comments{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_forum_comments as $comment}
                                <li>
                                    <a class="link" title="{tr}View{/tr}" href="tiki-view_forum_thread.php?comments_parentId={$comment.threadId}">{$comment.title|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {if $user_forum_topics|@count > 0}
                        <h3>{tr}Forum topics{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_forum_topics as $topic}
                                <li>
                                    <a class="link" title="{tr}View{/tr}" href="tiki-view_forum_thread.php?comments_parentId={$topic.threadId}">{$topic.title|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {if $user_items|@count > 0}
                        <h3>{tr}User Items{/tr}</h3>
                        <ul class="list-unstyled ps-3">
                            {foreach $user_items as $item}
                                <li>
                                    <a class="link" title="{tr}View{/tr}" href="tiki-view_tracker_item.php?trackerId={$item.trackerId}&amp;itemId={$item.itemId}">{$item.name|escape} : {$item.value|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                </div>
            </div>
        {/tab}
    {/if}


    {if $prefs.feature_actionlog eq 'y' and $prefs.user_who_viewed_my_stuff eq 'y' and !empty($user) and ($prefs.user_who_viewed_my_stuff_show_others eq 'y' or $user eq $userinfo.login or $tiki_p_admin eq "y") and $infoPublic eq "y"}
        {tab name="{tr}Who Looks at Items?{/tr}"}
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center">{if $user eq $userinfo.login}{tr}Who Looks at Your Items?{/tr}{else}{tr}Who Looks at His or Her Items?{/tr}{/if}</h2>

                    {section name=ix loop=$whoviewed}
                        <div class="row">
                            <div class="form col-sm-4">
                                {$whoviewed[ix].user|userlink} - {$whoviewed[ix].lastViewed|tiki_short_datetime}
                            </div>
                            <div class="form col-sm-8">
                                <a href="{$whoviewed[ix].link|escape}">
                                    {$whoviewed[ix].object|escape} ({$whoviewed[ix].objectType|escape})
                                </a>
                            </div>
                        </div>
                    {/section}
                </div>
            </div>
        {/tab}
    {/if}

    {if $user and $prefs.feature_messages eq 'y' and $tiki_p_messages eq 'y' and $allowMsgs eq 'y'}
        {tab name="{tr}Send a message{/tr}"}
            <div id="message">
                {if $sent}
                    {$message}
                {else}
                    <h2 class="text-center">{tr}Send a message!{/tr} </h2>
                    <form method="post" action="tiki-user_information.php" name="f">
                        {ticket}
                        <input type="hidden" name="to" value="{$userwatch|escape}">
                        <input type="hidden" name="view_user" value="{$userwatch|escape}">

                        <p>{tr}The following message will be sent to user{/tr} {$userinfo.login|userlink}…</p>

                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="priority">{tr}Priority{/tr}</label>
                            <div class="col-sm-10">
                                <select name="priority" id="priority" class="form-control">
                                    <option value="1" {if $priority eq 1}selected="selected"{/if}>1: {tr}Lowest{/tr}</option>
                                    <option value="2" {if $priority eq 2}selected="selected"{/if}>2: {tr}Low{/tr}</option>
                                    <option value="3" {if $priority eq 3}selected="selected"{/if}>3: {tr}Normal{/tr}</option>
                                    <option value="4" {if $priority eq 4}selected="selected"{/if}>4: {tr}High{/tr}</option>
                                    <option value="5" {if $priority eq 5}selected="selected"{/if}>5: {tr}Very High{/tr}</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="subject">{tr}Subject{/tr}</label>
                            <div class="col-sm-10">
                                <input type="text" name="subject" id="subject" value="" maxlength="255" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label" for="message">{tr}Message Body{/tr}</label>
                            <div class="col-sm-10">
                                <textarea rows="20" class="form-control" name="body" id="message"></textarea>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-sm-10 offset-sm-2">
                                <label for="replytome" class="ms-2">
                                    <input type="checkbox" name="replytome" id="replytome">
                                    {tr}Reply-to my email{/tr}
                                    {help url="User-Information" desc="{tr}Reply-to my email:{/tr}{tr}The user will be able to reply to you directly via email.{/tr}"}
                                </label>
                                <label for="bccme" class="ms-2">
                                    <input type="checkbox" name="bccme" id="bccme">
                                    {tr}Send me a copy{/tr}
                                    {help url="User-Information" desc="{tr}Send me a copy:{/tr}{tr}You will be sent a copy of this email.{/tr}"}
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-sm-10 offset-sm-2">
                                <input type="submit" class="btn btn-secondary" name="send" value="{tr}Send{/tr}">
                            </div>
                        </div>
                    </form>
                {/if}
            </div>
        {/tab}
    {/if}
{/tabset}
