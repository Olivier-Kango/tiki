<form method="post" action="tiki-admin.php?page=forums" class="admin">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        <a role="button" class="btn btn-link tips" href="tiki-admin_forums.php" title=":{tr}Forums listing{/tr}">
            {icon name="list"} {tr}Forums{/tr}
        </a>
        {include file='admin/include_apply_top.tpl'}
    </div>
    {tabset name="admin_forums"}
        {tab name="{tr}General Settings{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}Activate the feature{/tr}</legend>
                {preference name=feature_forums visible="always"}
                {preference name=home_forum}
                {preference name=forum_image_file_gallery}
            </fieldset>
            <fieldset>
                <legend class="h3">
                    {tr}Features{/tr}
                </legend>
                {preference name=feature_forum_rankings}
                {preference name=feature_forum_process_inbound_mail_in_cron}
                {preference name=feature_forum_parse}
                <div class="adminoptionboxchild" id="feature_forum_parse_childcontainer">
                    {if $prefs.feature_wysiwyg eq 'y' and $prefs.wysiwyg_default eq 'y' and $prefs.wysiwyg_htmltowiki neq 'y'}
                        {remarksbox type="warning" title="{tr}Note{/tr}"}
                            {tr}WYSIWYG is enabled but needs to be in "wiki syntax" mode to work correctly in forums{/tr}
                        {/remarksbox}
                    {/if}
                    {preference name=forum_strip_wiki_syntax_outgoing}
                </div>
                {preference name=feature_forum_topics_archiving}
                {preference name=feature_forum_quickjump}
                {preference name=feature_forum_replyempty}
                {preference name=feature_forum_allow_flat_forum_quotes}
                {preference name=forum_reply_notitle}
                <div class="adminoptionboxchild" id="forum_reply_notitle_childcontainer">
                    {remarksbox type="information" title="{tr}Note{/tr}"}
                    {tr _0="<a href=\"tiki-admin.php?page=comments\" title=\"{tr}Disable comment titles{/tr}\" class=\"alert-link\" alt=\"{tr}Disable comment titles{/tr}\">{tr}Disable comment titles{/tr}</a>"}Depending of your configuration it may be also necessary to %0 on the Comments control panel to avoid having a "Please enter a Title..." error.{/tr}
                    {/remarksbox}
                </div>
                {preference name=forum_comments_no_title_prefix}
                {preference name=forum_reply_forcetitle}
                <div class="adminoptionboxchild" id="forum_reply_forcetitle_childcontainer">
                    {remarksbox type="information" title="{tr}Note{/tr}"}
                    {tr _0="<a href=\"tiki-admin.php?page=comments\" title=\"{tr}Disable comment titles{/tr}\" class=\"alert-link\" alt=\"{tr}Disable comment titles{/tr}\">{tr}Disable comment titles{/tr}</a>"}Depending of your configuration it may be also necessary to %0 on the Comments control panel to avoid having a "Please enter a Title..." error.{/tr}
                    {/remarksbox}
                </div>
                {preference name=feature_forums_allow_thread_titles}
                {preference name=forum_quote_prevent_nesting}
                {preference name=forum_inbound_mail_ignores_perms}
                {preference name=forum_inbound_mail_parse_html}
                {preference name=forum_match_regex}
                {preference name=forum_moderator_notification}
                {preference name=forum_moderator_email_approve}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Threads{/tr}</legend>
                {preference name=forum_thread_defaults_by_forum}
                {preference name=forum_thread_user_settings}
                <div class="adminoptionboxchild" id="forum_thread_user_settings_childcontainer">
                    {preference name=forum_thread_user_settings_keep}
                    {preference name=forum_thread_user_settings_threshold}
                </div>
                {preference name=forum_comments_per_page}
                {preference name=forum_thread_style}
                {preference name=forum_thread_sort_mode}
                {preference name=feature_sefurl_title_forumthread}
                {preference name=forum_notifications_use_new_threads}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Searches{/tr}</legend>
                {preference name=feature_forums_name_search}
                {preference name=feature_forums_search}
                {preference name=feature_forum_content_search}
                <div class="adminoptionboxchild" id="feature_forum_content_search_childcontainer">
                    {preference name=feature_forum_local_tiki_search}
                </div>
                {preference name=feature_forum_post_index}
            </fieldset>
        {/tab}
        {tab name="{tr}Forum Listing{/tr}"}
            <br>
            {preference name=forums_ordering}
            {preference name=forums_section_ordering}
            {preference name=forum_list_topics}
            {preference name=forum_list_posts}
            {preference name=forum_list_ppd}
            {preference name=forum_list_lastpost}
            {preference name=forum_list_visits}
            {preference name=forum_list_desc}
            <div class="adminoptionboxchild" id="forum_list_desc_childcontainer">
                {preference name=forum_list_description_len}
            </div>
            {preference name=forum_category_selector_in_list}
            <div class="adminoptionboxchild" id="forum_category_selector_in_list_childcontainer">
                {preference name=forum_available_categories}
            </div>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
