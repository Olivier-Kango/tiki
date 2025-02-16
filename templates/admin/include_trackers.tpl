<form action="tiki-admin.php?page=trackers" method="post" class="admin">
    {ticket}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            <a role="button" class="btn btn-link" href="tiki-list_trackers.php" title="{tr}List{/tr}">
                {icon name="list"} {tr}Trackers{/tr}
            </a>
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    {tabset}

        {tab name="{tr}Settings{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}Activate the feature{/tr}</legend>
                {preference name=feature_trackers visible="always"}
            </fieldset>
            <fieldset class="mb-3 w-100">
                <legend class="h3">{tr}Tracker settings{/tr}</legend>
                {preference name="tracker_remote_sync"}
                {preference name="tracker_tabular_enabled"}
                {preference name="tracker_clone_item"}
                {preference name=allocate_memory_tracker_export_items}
                {preference name=allocate_time_tracker_export_items}
                {preference name=allocate_memory_tracker_import_items}
                {preference name=allocate_time_tracker_import_items}
                {preference name=allocate_time_tracker_clear_items}

                {preference name=feature_warn_on_edit}
                {preference name=ajax_inline_edit}
                {preference name="tracker_item_select_feature"}
                <div class="adminoptionboxchild" id="ajax_inline_edit_childcontainer">
                    {preference name=ajax_inline_edit_trackerlist}
                </div>
                {preference name=tracker_report_resize_button}
                {preference name=tracker_show_comments_below}
                {preference name=tracker_legacy_insert}
                {preference name=tracker_status_in_objectlink}
                {preference name=tracker_always_notify}
                {preference name=feature_sefurl_tracker_prefixalias}
                {preference name=tracker_prefixalias_on_links}
                {preference name=feature_sefurl_title_trackeritem}
                {preference name=tracker_list_order}
                {preference name=tracker_history_diff_style}
            </fieldset>
            <fieldset class="mb-3 w-100">
                <legend class="h3">{tr}Field settings{/tr}</legend>
                {preference name=user_selector_threshold}
                {preference name=user_selector_realnames_tracker}
                {preference name=tiki_object_selector_threshold}
                {preference name=tiki_object_selector_searchfield}
                {preference name=tiki_object_selector_wildcardsearch}
                {preference name="tracker_refresh_itemlink_detail"}
                {preference name=tracker_refresh_itemslist_detail}
                {preference name=fgal_tracker_existing_search}
                {preference name=unified_trackeritem_category_names}
                {preference name=tracker_change_field_type}
                {preference name=tracker_field_rules}
                {preference name=unified_numeric_field_scroll}
                {preference name=tracker_autoincrement_resettable}
                {preference name=tracker_currency_default_locale}
            </fieldset>

            <fieldset class="admin">
                <legend class="h3">{tr}Linked wiki pages{/tr}</legend>
                {remarksbox type="tip" title="{tr}Tip{/tr}"}
                    {tr}Wiki pages are linked to tracker items, and their page names to tracker fields, via the tiki.wiki.linkeditem and tiki.wiki.linkedfield relations. You need to be familiar with the Relations tracker field or use the outputwiki option in the TRACKER plugin to make use of these features.{/tr}
                {/remarksbox}
                {preference name=tracker_wikirelation_synctitle}
                {preference name=tracker_wikirelation_redirectpage}
            </fieldset>

            <fieldset class="mb-3 w-100">
                <legend class="h3">{tr}Tracker attachment preferences{/tr}</legend>
                {preference name='t_use_db'}
                <div class="adminoptionboxchild t_use_db_childcontainer n">
                    {preference name='t_use_dir'}
                </div>
            </fieldset>
            <fieldset class="admin">
                <legend class="h3">{tr}Tracker force-fill feature{/tr}</legend>
                {preference name=tracker_force_fill}
                {preference name=tracker_force_tracker_id}
                {preference name=tracker_force_mandatory_field}
                {preference name=tracker_force_tracker_fields}
                {preference name=user_force_avatar_upload}
            </fieldset>
        {/tab}

        {tab name="{tr}Plugins{/tr}"}
            <br>
            <fieldset class="mb-3 w-100">
                {preference name=wikiplugin_insert}
                <div class="adminoptionboxchild" id="wikiplugin_insert_childcontainer">
                    {preference name=tracker_insert_allowed}
                </div>
                {preference name=wikiplugin_tracker}
                {preference name=wikiplugin_trackerlist}
                {preference name=wikiplugin_trackerfilter}
                {preference name=wikiplugin_trackerif}
                {preference name=wikiplugin_trackerstat}
                {preference name=wikiplugin_miniquiz}
                {preference name=wikiplugin_vote}
                {preference name=wikiplugin_trackercomments}
                {preference name=wikiplugin_trackeritemfield}
                {preference name=wikiplugin_trackerprefill}
                {preference name=wikiplugin_trackertimeline}
                {preference name=wikiplugin_trackertoggle}
                {preference name=wikiplugin_trackeritemcopy}
                {preference name=wikiplugin_trackerquerytemplate}
            </fieldset>
        {/tab}

        {tab name="{tr}Field Types{/tr}"}
            <br>
            <fieldset class="mb-3 w-100">
                <legend class="h3">{tr}Field types{/tr}</legend>
                {foreach from=$fieldPreferences item=name}
                    {preference name=$name}
                {/foreach}
            </fieldset>
        {/tab}

        {tab name="{tr}System Trackers{/tr}"}
            <br>
            <fieldset class="admin">
                <legend class="h3">{tr}System trackers{/tr}</legend>
                {preference name=tracker_system_currency}
                <div class="adminoptionboxchild" id="tracker_system_currency_childcontainer">
                    {preference name=tracker_system_currency_tracker}
                    {preference name=tracker_system_currency_rate}
                    {preference name=tracker_system_currency_currency}
                    {preference name=tracker_system_currency_date}
                    {preference name=tracker_system_currency_direction}
                </div>
                {preference name=tracker_system_bounces}
                <div class="adminoptionboxchild" id="tracker_system_bounces_childcontainer">
                    {preference name=tracker_system_bounces_tracker}
                    {preference name=tracker_system_bounces_mailbox}
                    {preference name=tracker_system_bounces_emailfolder}
                    {preference name=tracker_system_bounces_soft_total}
                    {preference name=tracker_system_bounces_hard_total}
                    {preference name=tracker_system_bounces_blacklisted}
                </div>
            </fieldset>
            <fieldset class="admin">
                <legend class="h3">{tr}Relationship System Trackers{/tr}</legend>
                {remarksbox type="tip" title="{tr}Tip{/tr}"}
                    {tr _0="<a href='tiki-list_trackers.php'>" _1="</a>"}You can quickly create common used trackers to store relationship metadata and behaviour here. Alternatively use any %0tracker%1 for a relationship tracker by editing its properties.{/tr}
                {/remarksbox}
                <div class="row">
                    <div class="col-sm-6">
                        <select name="relationshipTrackerType">
                            <option value="">{tr}Choose example tracker type{/tr}</option>
                            <option value="generic">Generic description</option>
                            <option value="parent-child">Parent-child</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <button role="button" type="submit" class="btn btn-primary" name="createRelationsTracker" value="1">
                            {tr}Create Relationship Tracker{/tr}
                        </button>
                    </div>
                </div>
                <br>
            </fieldset>
        {/tab}

    {/tabset}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            <div class="text-center">
                <input
                    type="submit"
                    class="btn btn-primary tips"
                    name="trkset"
                    title=":{tr}Apply changes{/tr}"
                    value="{tr}Apply{/tr}"
                >
            </div>
        </div>
    </div>
</form>


<fieldset>
    <legend class="h3">{tr}Tracker attachments{/tr}</legend>
    <div class="table">
        {if $attachments}
            <form action="tiki-admin.php?page=trackers" method="post">
                {ticket}
                <input type="text" name="find" value="{$find|escape}">
                <input type="submit" class="btn btn-primary btn-sm" name="action" value="{tr}Find{/tr}">
            </form>
        {/if}

        <div class="table-responsive">
            <table class="table">
                <tr>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=user_{if $sort_mode eq 'attId'}asc{else}desc{/if}">{tr}ID{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=user_{if $sort_mode eq 'user'}asc{else}desc{/if}">{tr}User{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=filename_{if $sort_mode eq 'filename'}asc{else}desc{/if}">{tr}Name{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=filesize_{if $sort_mode eq 'filesize'}asc{else}desc{/if}">{tr}Size{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=filetype_{if $sort_mode eq 'filetype'}asc{else}desc{/if}">{tr}Type{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=hits_{if $sort_mode eq 'hits'}asc{else}desc{/if}">{tr}dls{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=itemId_{if $sort_mode eq 'itemId'}asc{else}desc{/if}">{tr}Item{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=path_{if $sort_mode eq 'path'}asc{else}desc{/if}">{tr}Storage{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-admin.php?page=trackers&amp;sort_mode=created_{if $sort_mode eq 'created'}asc{else}desc{/if}">{tr}Created{/tr}</a>
                    </th>
                    <th>{tr}Switch storage{/tr}</th>
                </tr>

                {section name=x loop=$attachments}
                    <tr class={cycle}>
                        <td class="id"><a href="tiki-download_item_attachment.php?attId={$attachments[x].attId}" title="{tr}Download{/tr}">{$attachments[x].attId}</a></td>
                        <td class="username">{$attachments[x].user}</td>
                        <td class="text">{$attachments[x].filename}</td>
                        <td class="integer">{$attachments[x].filesize|kbsize}</td>
                        <td class="text">{$attachments[x].filetype}</td>
                        <td class="integer">{$attachments[x].hits}</td>
                        <td class="integer">{$attachments[x].itemId}</td>
                        <td class="text">{if $attachments[x].path}file{else}db{/if}</td>
                        <td class="date">{$attachments[x].created|tiki_short_date}</td>
                        <td class="action">
                            <a href="tiki-admin.php?page=trackers&amp;attId={$attachments[x].attId}&amp;action={if $attachments[x].path}move2db{else}move2file{/if}">
                                {icon name='refresh' iclass='tips' title=":{tr}Switch storage{/tr}"}
                            </a>
                        </td>
                    </tr>
                {sectionelse}
                    {norecords _colspan=10}
                {/section}
            </table>
        </div>

        {pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
    </div>
    {if $attachments}
        <table>
            <tr>
                <td>
                    <form action="tiki-admin.php?page=trackers" method="post">
                        {ticket}
                        <input type="hidden" name="all2db" value="1">
                        <input
                            type="submit"
                            class="btn btn-primary btn-sm"
                            name="action"
                            value="{tr}Change all to db{/tr}"
                        >
                    </form>
                </td>
                <td>
                    <form action="tiki-admin.php?page=trackers" method="post">
                        {ticket}
                        <input type="hidden" name="all2file" value="1">
                        <input
                            type="submit"
                            class="btn btn-primary btn-sm"
                            name="action"
                            value="{tr}Change all to file{/tr}"
                        >
                    </form>
                </td>
            </tr>
        </table>
    {/if}
</fieldset>
