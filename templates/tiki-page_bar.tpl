{if !isset($versioned) or not $versioned}
    {capture assign=more_section}
        {if $page|lower ne 'sandbox'}
            {if $tiki_p_remove eq 'y' && (isset($editable) and $editable)}
                <a class="dropdown-item btn btn-link alert alert-danger alert-link border-0 rounded-0 mb-0 py-0 px-4" style="font-weight: inherit;" href="{bootstrap_modal controller=wiki action=remove_pages checked=$page version='last'}">
                    {tr}Remove{/tr}
                </a>
            {/if}
            {if $tiki_p_admin_wiki eq 'y' or $tiki_p_assign_perm_wiki_page eq 'y'}
                {permission_link mode="button" type="wiki page" addclass="dropdown-item" id=$page permType=wiki title=$page}
            {/if}
            {if $prefs.feature_page_contribution eq 'y' and $tiki_p_page_contribution_view eq 'y'}
                {button _keepall='y' href="tiki-page_contribution.php" page=$page _type="link" _class="dropdown-item" _text="{tr}Contributions by author{/tr}"}
            {/if}
        {/if}
        {if $prefs.feature_likePages eq 'y' and $tiki_p_wiki_view_similar eq 'y'}
            {button _keepall='y' href="tiki-likepages.php" page=$page _type="link" _class="dropdown-item" _text="{tr}Similar{/tr}"}
        {/if}

        {if $prefs.feature_wiki_undo eq 'y' and $canundo eq 'y'}
            <form action="tiki-index.php" method="post" id="undo">
                {ticket}
                <input type="hidden" name="page" value="{$page}">
                <input type="hidden" name="undo" value="1">
            </form>
            <button type="submit" form="undo" class="btn btn-link dropdown-item pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to undo the last change?{/tr}')">
                Undo
            </button>
        {/if}

        {if $prefs.feature_wiki_make_structure eq 'y' and $tiki_p_edit_structures eq 'y' and (isset($editable)
        and $editable) and $structure eq 'n' and count($showstructs) eq 0}
            {button _keepall='y' href="tiki-index.php" page=$page convertstructure="1" _type="link" _class="dropdown-item" _text="{tr}Make Structure{/tr}"}
        {/if}

        {if $prefs.feature_slideshow eq 'y' && $prefs.wiki_uses_slides eq 'y'}
            {if $show_slideshow eq 'y'}
                {button _keepall='y' href="./tiki-slideshow.php" page=$page _type="link" _class="dropdown-item" _text="{tr}Slideshow{/tr}"}
            {elseif $structure eq 'y'}
                {button _keepall='y' href="tiki-slideshow2.php" page_ref_id=$page_info.page_ref_id _type="link" _class="dropdown-item" _text="{tr}Slideshow{/tr}"}
            {/if}
        {/if}

        {if $prefs.feature_wiki_export eq 'y' and ( $tiki_p_admin_wiki eq 'y' or $tiki_p_export_wiki eq 'y' )}
            {button _keepall='y' href="tiki-export_wiki_pages.php" page=$page _type="link" _class="dropdown-item" _text="{tr}Export{/tr}"}
        {/if}

        {if $prefs.feature_wiki_discuss eq 'y' && $show_page eq 'y' && $tiki_p_forum_post eq 'y' && ( empty($prefs.wiki_discuss_visibility) || $prefs.wiki_discuss_visibility eq 'button')}
            {capture assign=wiki_discussion_string}
                {include file='wiki-discussion.tpl'} [tiki-index.php?page={$page|escape:url}|{$page}]
            {/capture}
            {button _keepall='y' href="tiki-view_forum.php" forumId=$prefs.wiki_forum_id comments_postComment="post" comments_title=$page comments_data=$wiki_discussion_string comment_topictype="n" _type="link" _class="dropdown-item" _text="{tr}Discuss{/tr}"}
        {/if}
        {if $prefs.feature_multilingual eq 'y' and ($tiki_p_edit eq 'y'
        or (!$user and $prefs.wiki_encourage_contribution eq 'y')) and !$lock}
            {button _keepall='y' href="tiki-edit_translation.php" page=$page _type="link" _class="dropdown-item" _text="{tr}Translate{/tr}"}
        {/if}

        {if $tiki_p_admin_wiki eq 'y' && $prefs.wiki_keywords eq 'y'}
            {button _keepall='y' href="tiki-admin_keywords.php" page=$page _type="link" _class="dropdown-item" _text="{tr}Keywords{/tr}"}
        {/if}
        {if $user and (isset($tiki_p_create_bookmarks) and $tiki_p_create_bookmarks eq 'y') and $prefs.feature_user_bookmarks eq 'y'}
            {assign var=urlurl value="{$page|sefurl}{$smarty.server.REQUEST_URI|regex_replace:'/^[^\?\&]*/':''|regex_replace:'/(\?page=[^\&]+)/':''}"}{button _script="tiki-user_bookmarks.php" urlname=$page urlurl=$urlurl addurl="Add" _type="link" _class="dropdown-item" _text="{tr}Bookmark{/tr}" _auto_args="urlname,urlurl,addurl"}
        {/if}

        {* Use `$smarty->append('tiki_page_bar_more_items', $item);` to put your item here *}
        {if ! empty($tiki_page_bar_more_items)}
            {foreach from=$tiki_page_bar_more_items item=item }
                {$item}
            {/foreach}
        {/if}
    {/capture}
    {capture assign=page_bar}
        {if $edit_page neq 'y'}
            {* Check that page is not locked and edit permission granted. SandBox can be edited w/o perm *}
            {if ((isset($editable) and $editable) and ($tiki_p_edit eq 'y' or $page|lower eq 'sandbox')
                or (!$user and $prefs.wiki_encourage_contribution eq 'y')) or $tiki_p_admin_wiki eq 'y'}
                {if isset($beingEdited) and $beingEdited eq 'y'}
                    {assign var=thisPageClass value='+highlight'}
                {else}
                    {assign var=thisPageClass value=''}
                {/if}
                {if $prefs.flaggedrev_approval neq 'y' or ! $revision_approval or $lastVersion eq $revision_displayed}
                    {if isset($page_ref_id)}
                        {button _keepall='y' href="tiki-editpage.php" page=$page page_ref_id=$page_ref_id _class="$thisPageClass mb-2" _text="{tr}Edit{/tr}" _title="{tr}Edit this page{/tr}"}
                    {else}
                        {button _keepall='y' href="tiki-editpage.php" page=$page _class="$thisPageClass mb-2" _text="{tr}Edit{/tr}" _title="{tr}Edit this page{/tr}"}
                    {/if}
                {elseif $tiki_p_wiki_view_latest eq 'y'}
                    {self_link latest=1 _class="btn btn-warning"}
                        {tr}View latest version before editing{/tr}
                    {/self_link}
                {/if}
            {/if}

            {if $page|lower ne 'sandbox'}
                {if $tiki_p_rename eq 'y' && (isset($editable) and $editable)}
                    {button _keepall='y' _class="mb-2" href="tiki-rename_page.php" page=$page _text="{tr}Rename{/tr}"}
                {/if}
                {if $prefs.feature_wiki_usrlock eq 'y' and $user and $tiki_p_lock eq 'y'}
                    {if !$lock}
                        <a class="btn btn-primary mb-2" href="{bootstrap_modal controller=wiki action=lock_pages checked=$page}">
                            {tr}Lock{/tr}
                        </a>
                    {elseif $tiki_p_admin_wiki eq 'y' or $user eq $page_user}
                        <a class="btn btn-primary mb-2" href="{bootstrap_modal controller=wiki action=unlock_pages checked=$page}">
                            {tr}Unlock{/tr}
                        </a>
                    {/if}
                {/if}
                {if $prefs.feature_history eq 'y' and $tiki_p_wiki_view_history eq 'y'}
                    {button _keepall='y' _type="info mb-2" href="tiki-pagehistory.php" page=$page _text="{tr}History{/tr}"}
                {/if}
            {/if}

            {if $prefs.feature_source eq 'y' and $tiki_p_wiki_view_source eq 'y'}
                {button _keepall='y' _type="info mb-2" href="tiki-pagehistory.php" page=$page source="0" _text="{tr}Source{/tr}"}
            {/if}

            {if $prefs.feature_wiki_comments eq 'y'
                && ($prefs.wiki_comments_allow_per_page eq 'n' or $info.comments_enabled eq 'y')
                && $tiki_p_wiki_view_comments eq 'y'
                && $tiki_p_read_comments eq 'y'}

                        {* Auto display comments if display by default preference is set *}
                        {if $prefs.wiki_comments_displayed_default eq 'y' && $show_slideshow eq 'n'}
                        {jq}{literal}
                            var id = '#comment-container';
                            $(id).comment_load('tiki-ajax_services.php?controller=comment&action=list&type=wiki+page&objectId={/literal}{$page|escape:url}{literal}#comment-container');
                            $(document).on("ajaxComplete", function(){$(id).tiki_popover();});
                            {/literal}
                        {/jq}
                        {/if}

                        <a class="btn btn-secondary mb-2" id="comment-toggle" href="{service controller=comment action=list type="wiki page" objectId=$page}#comment-container">
                            {tr}Comments{/tr}
                            {if $count_comments}
                                &nbsp;<span class="count_comments badge bg-secondary">{$count_comments}</span>
                            {/if}
                        </a>
                        {jq}
                            $('#comment-toggle').comment_toggle();
                        {/jq}
            {/if}


            {if isset($show_page) and $show_page eq 'y'}
                {* don't show attachments button if feature disabled or no corresponding rights or no attached files and r/o*}

                {if $prefs.feature_wiki_attachments == 'y'
                    && (
                        $tiki_p_wiki_view_attachments == 'y'
                        && (isset($atts_count) && $atts_count gt 0)
                        || $tiki_p_wiki_attach_files == 'y'
                        || $tiki_p_wiki_admin_attachments == 'y')}

                    {capture assign=thistext}
                        {strip}
                            {if (!isset($atts_count) or $atts_count == 0) || $tiki_p_wiki_attach_files == 'y'
                                && $tiki_p_wiki_view_attachments == 'n' && $tiki_p_wiki_admin_attachments == 'n'}
                                {tr}Files{/tr}
                            {else}
                                {tr}Files{/tr}
                                &nbsp;<span class="atts_count badge bg-info">{$atts_count}</span>
                            {/if}
                        {/strip}
                    {/capture}

                    {if (isset($atts_count) and $atts_count gt 0) || $editable}
                        {button href="#attachments" _flip_id="attzone{if isset($pagemd5)}{$pagemd5}{/if}" _type="secondary mb-2" _text=$thistext _flip_default_open=$prefs.w_displayed_default _flip_hide_text="n"}
                    {/if}
                {/if}{* attachments *}

            {/if}
            {if $more_section|trim neq ''}
                <div class="btn-group dropup mb-2">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">{tr}More{/tr}</button>
                    <div class="dropdown-menu">
                        {$more_section}
                    </div>
                </div>
            {/if}
            
           
        {/if}
    {/capture}

    {if $prefs.feature_wiki_discuss eq 'y' && $show_page eq 'y' && $tiki_p_forum_post eq 'y' && $prefs.wiki_discuss_visibility eq 'above' }
            {include file='discussinforum.tpl'}
    {/if}

    {if $page_bar|trim neq ''}
        <div class="row mx-0 my-3" id="page-bar">
            <div class="btn-bar">
                {$page_bar}
            </div>
        </div>
    {/if}

    {strip}

        {if $wiki_extras eq 'y' && $prefs.feature_wiki_attachments eq 'y' and $tiki_p_wiki_view_attachments eq 'y'}
            {if $prefs.feature_use_fgal_for_wiki_attachments eq 'y'}
                {attachments _id=$page _type='wiki page'}
            {else}
                {include file='attachments.tpl'}
            {/if}
        {/if}

        {if $prefs.feature_wiki_comments eq 'y' and $tiki_p_wiki_view_comments == 'y' and $edit_page ne 'y'}
            <div id="comment-container"></div>
        {/if}

    {/strip}
{/if}
{* don't show comments if feature disabled or not enough rights *}
