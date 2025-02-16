{title help="File Galleries" admpage="fgal"}
    {if $edit_mode eq 'y' and $galleryId eq 0}
        {tr}Create a File Gallery{/tr}
    {else}
        {if $edit_mode eq 'y'}
            {tr}Edit Gallery:{/tr}
        {/if}
        {tr}{$name}{/tr}
    {/if}
{/title}
<div class="t_navbar mb-4">
    <div class="btn-group float-end">
        {if ! $js}<ul><li class="dropdown-item">{/if}
        <a class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#"  title="{tr}Views{/tr}">
            {icon name="menu-extra"}
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            {if $edit_mode neq 'y' and $dup_mode neq 'y'}
                <li class="dropdown-divider"></li>
                <li class="dropdown-header">
                    {tr}Views{/tr}
                </li>
                <li class="dropdown-divider"></li>
                {if $view neq 'admin' and $tiki_p_admin_file_galleries eq 'y'}
                    <li class="dropdown-item">
                        {self_link _icon_name="wrench" _text="{tr}Admin{/tr}" view="admin" galleryId=$galleryId}{/self_link}
                    </li>
                {/if}
                {if $view neq 'browse'}
                    <li class="dropdown-item">
                        {self_link _icon_name="view" _text="{tr}Browse{/tr}" view="browse" galleryId=$galleryId}{/self_link}
                    </li>
                {/if}
                {if $view neq 'finder' and $prefs.fgal_elfinder_feature eq 'y'}
                    <li class="dropdown-item">
                        {self_link _icon_name="file-archive-open" _text="{tr}Finder{/tr}" view="finder" galleryId=$galleryId}{/self_link}
                    </li>
                {/if}
                {if $view neq 'list'}
                    <li class="dropdown-item">
                        {self_link _icon_name="list" _text="{tr}List{/tr}" view="list" galleryId=$galleryId}{/self_link}
                    </li>
                {/if}
                {if $view neq 'page' and $filescount gt 0}
                    <li class="dropdown-item">
                        {self_link _icon_name="textfile" _text="{tr}Page{/tr}" view="page" galleryId=$galleryId}{/self_link}
                    </li>
                {/if}
            {/if}
            <li class="dropdown-divider"></li>
            <li class="dropdown-header">
                {tr}Gallery actions{/tr}
            </li>
            <li class="dropdown-divider"></li>
            {if $edit_mode neq 'y' or $dup_mode neq 'y'}
                {if $tiki_p_create_file_galleries eq 'y' or (not empty($user) and $user eq $gal_info.user and $gal_info.type eq 'user' and $tiki_p_userfiles eq 'y')}
                    <li class="dropdown-item">
                        <a href="tiki-list_file_gallery.php?edit_mode=1&galleryId={$galleryId}">{icon name="edit"} {tr}Edit{/tr}</a>
                    </li>
                {/if}
            {/if}
            {if $tiki_p_create_file_galleries eq 'y' and $dup_mode ne 'y' and $gal_info.type neq 'user' and $all_galleries|@count gt 0}
                <li class="dropdown-item">
                    <a href="tiki-list_file_gallery.php?dup_mode=1&galleryId={$galleryId}">{icon name="copy"} {tr}Duplicate{/tr}</a>
                </li>
            {/if}
            {if $gal_info.type eq 'direct'}
                <li class="dropdown-item">
                    <a href="tiki-list_file_gallery.php?sync=1&galleryId={$galleryId}">{icon name="redo"} {tr}Sync{/tr}</a>
                </li>
            {/if}
            {if $tiki_p_assign_perm_file_gallery eq 'y'}
                <li class="dropdown-item">
                    {permission_link mode=text type="file gallery" permType="file galleries" id=$galleryId}
                </li>
            {/if}
            {if $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
                <li class="dropdown-item">
                    {* links to a form so no confirm popup needed *}
                    <a href="tiki-object_watches.php?objectId={$galleryId|escape:"url"}&amp;watch_event=file_gallery_changed&amp;objectType=File+Gallery&amp;objectName={$gal_info.name|escape:"url"}&amp;objectHref={'tiki-list_file_gallery.php?galleryId='|cat:$galleryId|escape:"url"}">
                        {icon name='watch-group'} {tr}Group monitor{/tr}
                    </a>
                </li>
            {/if}
            {if $user and $prefs.feature_user_watches eq 'y'}
                <li class="dropdown-item">
                    {if !isset($user_watching_file_gallery) or $user_watching_file_gallery eq 'n'}
                        <form action="tiki-list_file_gallery.php" method="post">
                            {ticket}
                            <input type="hidden" name="galleryName" value="{$name|escape:'attr'}">
                            <input type="hidden" name="watch_event" value="file_gallery_changed">
                            <input type="hidden" name="watch_object" value="{$galleryId|escape:'attr'}">
                            <input type="hidden" name="watch_action" value="add">
                            <button type="submit" class="btn btn-link link-list">
                                {icon name='watch'} {tr}Monitor{/tr}
                            </button>
                        </form>
                    {else}
                        <form action="tiki-list_file_gallery.php" method="post">
                            {ticket}
                            <input type="hidden" name="galleryName" value="{$name|escape:'attr'}">
                            <input type="hidden" name="watch_event" value="file_gallery_changed">
                            <input type="hidden" name="watch_object" value="{$galleryId|escape:'attr'}">
                            <input type="hidden" name="watch_action" value="remove">
                            <button type="submit" class="btn btn-link link-list">
                                {icon name='stop-watching'} {tr}Stop monitoring{/tr}
                            </button>
                        </form>
                    {/if}
                </li>
            {/if}
            {if $prefs.feed_file_gallery eq 'y'}
                <li class="dropdown-item">
                    {if $gal_info.type eq "podcast" or $gal_info.type eq "vidcast"}
                        <a href="tiki-file_gallery_rss.php?galleryId={$galleryId}&amp;ver=PODCAST">
                            {icon name='rss'} {tr}RSS feed{/tr}
                        </a>
                    {else}
                        <a href="tiki-file_gallery_rss.php?galleryId={$galleryId}">
                            {icon name='rss'} {tr}RSS feed{/tr}
                        </a>
                    {/if}
                </li>
            {/if}
            {if $view eq 'browse'}
                <li class="dropdown-item">
                    {if $show_details eq 'y'}
                        <a href="{query _type='relative' show_details='n'}" title="{tr}Hide file information from list view{/tr}">
                            {icon name='ban' align='right' alt="{tr}Hide file information from list view{/tr}"} {tr}Hide list view information{/tr}
                        </a>
                    {else}
                        <a href="{query _type='relative' show_details='y'}" title="{tr}Show file information from list view{/tr}">
                            {icon name='view' align='right' alt="{tr}Show file information from list view{/tr}"} {tr}Show list view information{/tr}
                        </a>
                    {/if}
                </li>
            {/if}
        </ul>
        {if ! $js}</li></ul>{/if}
    </div>
    {if $galleryId gt 0}
    {* main navigation buttons under the page title *}
    {*    {if $treeRootId eq $prefs.fgal_root_id && ( $tiki_p_list_file_galleries eq 'y'
            or (!isset($tiki_p_list_file_galleries) and $tiki_p_view_file_gallery eq 'y') )}
            {button _icon_name="list" _text="{tr}List{/tr}" href="?"}
        {/if} *}
        {if $tiki_p_create_file_galleries eq 'y' and $edit_mode ne 'y'}
            {button _keepall='y' _icon_name="create" _type="link" _text="{tr}Create{/tr}" edit_mode=1 parentId=$galleryId cookietab=1}
        {/if}
        {if $tiki_p_admin_file_galleries eq 'y' or (not empty($user) and $user eq $gal_info.user and $gal_info.type eq 'user' and $tiki_p_userfiles eq 'y')}
            {if $edit_mode eq 'y' or $dup_mode eq 'y'}
                {button _keepall='y' _icon_name="view" _text="{tr}Browse{/tr}" galleryId=$galleryId}
            {/if}
        {/if}
        {if $tiki_p_admin_file_galleries eq 'y' or $user eq $gal_info.user or $gal_info.public eq 'y'}
            {if $tiki_p_upload_files eq 'y'}
                {button _keepall='y' _icon_name="upload" _type="link" _text="{tr}Upload{/tr}" href="tiki-upload_file.php" galleryId=$galleryId}
            {/if}
            {if $tiki_p_upload_files eq 'y' and $prefs.feature_draw eq 'y'}
                <a class="draw dialog"title="{tr}Draw{/tr}" href="{bootstrap_modal controller=draw action=edit galleryId=$galleryId size='modal-fullscreen'}">
                    {icon name='post' alt="{tr}Post{/tr}"} {tr}Draw{/tr}
                </a>
            {/if}
            {if $tiki_p_upload_files eq 'y' and $prefs.wikiplugin_diagram eq 'y'}
                {button _keepall='y' _icon_name="chart" _type="link" _text="{tr}Create Diagram{/tr}" href="tiki-editdiagram.php" galleryId=$galleryId newDiagram='1'}
            {/if}
            {if $prefs.feature_file_galleries_batch eq "y" and $tiki_p_batch_upload_file_dir eq 'y'}
                {button _keepall='y' _icon_name="file-archive" _type="link" _text="{tr}Batch{/tr}" href="tiki-batch_upload_files.php" galleryId=$galleryId}
            {/if}
        {/if}
    {else}
        {if $treeRootId eq $prefs.fgal_root_id && ( $edit_mode eq 'y' or $dup_mode eq 'y')}
            {button _icon_name="list" _text="{tr}List{/tr}" href='?' _class="btn-info"}
        {/if}
        {if $tiki_p_create_file_galleries eq 'y' and $edit_mode ne 'y'}
            {button _icon_name="create" _keepall='y' _text="{tr}Create{/tr}" edit_mode="1" parentId="-1" galleryId="0"}
        {/if}
        {if $tiki_p_upload_files eq 'y'}
            {button _icon_name="export" _text="{tr}Upload{/tr}" href="tiki-upload_file.php"}
        {/if}
    {/if}
    {if $edit_mode neq 'y' and $prefs.fgal_show_slideshow eq 'y' and $gal_info.show_slideshow eq 'y'}
        {button _icon_name="chart" _text="{tr}SlideShow{/tr}" href="#" _onclick="javascript:window.open('tiki-list_file_gallery.php?galleryId=$galleryId&amp;slideshow&offset=$offset','','menubar=no,width=600,height=500,resizable=yes');return false;"}
    {/if}
    {if $edit_mode neq 'y' and $prefs.h5p_enabled eq 'y' and $tiki_p_upload_files eq 'y' and $tiki_p_h5p_edit eq 'y'}
        <a href="{service controller='h5p' action='edit' modal=1}" class="btn btn-link create-h5p">{icon name='plus'} {tr}Create H5P{/tr}</a>
        {jq}$(".create-h5p").clickModal({title: "{tr}Create H5P{/tr}", size: "modal-lg"});{/jq}
    {/if}
</div>

{if $edit_mode neq 'y' and $gal_info.description neq ''}
    <div class="description form-text">
        {$gal_info.description|escape|nl2br}
    </div>
{/if}

{if !empty($filegals_manager)}
    {remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Be careful to set the right permissions on the files you link to{/tr}.{/remarksbox}
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="keepOpenCbx" checked="checked">
        <label for="keepOpenCbx" class="form-check-label" for="keepOpenCbx">{tr}Keep gallery window open{/tr}</label>
    </div>
{/if}

{if isset($fileChangedMessage) and $fileChangedMessage neq ''}
    {remarksbox type="note" title="{tr}Note{/tr}"}
        {$fileChangedMessage}
        <form method="post"
                action="{$smarty.server.SCRIPT_NAME}{if !empty($filegals_manager) and $filegals_manager neq ''}?filegals_manager={$filegals_manager|escape}{/if}"
                class="d-flex flex-row flex-wrap align-items-center">
            <input type="hidden" name="galleryId" value="{$galleryId|escape}">
            <input type="hidden" name="fileId" value="{$fileId|escape}">
            {ticket}
            <div class="mb-3 row">
                <label for="comment">
                    {tr}Comment{/tr} ({tr}optional{/tr}):
                </label>
                    <input type="text" name="comment" id="comment" class="form-control">
            </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    {icon name='ok'} {tr}Save{/tr}
                </button>
        </form>
    {/remarksbox}
{/if}

{if $user and $prefs.feature_user_watches eq 'y' and isset($category_watched) && $category_watched eq 'y'}
    <div class="categbar">
        {tr}Watched by categories:{/tr}
        {section name=i loop=$watching_categories}
            {button _keepall='y' _text=$watching_categories[i].name|escape href="tiki-browse_categories.php" parentId=$watching_categories[i].categId}
        {/section}
    </div>
{/if}

{if !empty($fgal_diff)}
    {remarksbox type="note" title="{tr}Modifications{/tr}"}
        {foreach from=$fgal_diff item=fgp_prop key=fgp_name name=change}
            {tr}Property <b>{$fgp_name}</b> Changed{/tr}
        {/foreach}
    {/remarksbox}
{/if}

{if $edit_mode eq 'y'}
    <br>{include file='edit_file_gallery.tpl'}
{elseif $dup_mode eq 'y'}
    {include file='duplicate_file_gallery.tpl'}
{else}
    {if $view neq 'page'}
        {if $prefs.fgal_elfinder_feature neq 'y' or $view neq 'finder'}
            <div class="search-button-container clearfix">
                <button class="btn btn-info btn-sm mb-2 dropdown-toggle float-end" type="button" data-bs-toggle="collapse" data-bs-target="#searchListFgal" aria-expanded="false" aria-controls="searchListFgal" title="{tr}Search file galleries{/tr}">{icon name="search"}</button>
            </div>
            <div class="collapse" id="searchListFgal">
                <div class="row">
                {if $prefs.fgal_search eq 'y'}
                    <div class="col-sm-6">
                        {include file='find.tpl' find_show_num_rows = 'y' find_show_categories_multi='y' find_durations=$find_durations find_show_sub='y' find_in="<ul><li>{tr}Name,{/tr}</li><li>{tr}Filename,{/tr}</li><li>{tr}Description{/tr}</li></ul>"|strip_tags}
                    </div>
                {/if}
                {if ($prefs.fgal_search_in_content eq 'y' or $prefs.fgal_search eq 'y') and $galleryId > 0}
                    <div class="col-sm-6">
                        {if $prefs.fgal_search_in_content eq 'y'}
                            <form id="search-form" class="form" method="get" action="tiki-search{if $prefs.feature_forum_local_tiki_search eq 'y'}index{else}results{/if}.php">
                                <input type="hidden" name="where" value="files">
                                <input type="hidden" name="galleryId" value="{$galleryId}">
                                <label for="highlight" class="find_content sr-only">{tr}Search in content{/tr}</label>
                                <div class="input-group">
                                    <input name="highlight" size="30" type="text" placeholder="{tr}Search in content{/tr}..." class="form-control tips bottom" title="|{tr}Search for text within files in all galleries{/tr}">
                                    <input type="submit" class="wikiaction btn btn-info" name="search" value="{tr}Go{/tr}">
                                </div>
                            </form>
                        {/if}
                        {if $prefs.fgal_search eq 'y'}
                            <form id="search-by-id" class="form" method="get" action="tiki-list_file_gallery.php">
                                <div class="input-group">
                                    <input class="form-control tips bottom" type="text" name="fileId" id="fileId" {if isset($fileId)} value="{$fileId}"{/if} placeholder="{tr}Search by identifier{/tr}..." title="|{tr}Search for the file with this number, in all galleries{/tr}">
                                    <button type="submit" class="btn btn-info">{tr}Go{/tr}</button>
                                </div>
                            </form>
                        {/if}
                    </div>
                {/if}
                </div>
            </div>
        {/if}
    {else}
        <div class="pageview">
            <form id="size-form" class="form d-flex flex-row flex-wrap align-items-center" action="tiki-list_file_gallery.php">
                {ticket}
                <input type="hidden" name="view" value="page">
                <input type="hidden" name="galleryId" value="{$galleryId}">
                <input type="hidden" name="maxRecords" value=1>
                <input type="hidden" name="offset" value="{$offset}">
                <label for="maxWidth">
                    {tr}Maximum width{/tr}&nbsp;<input id="maxWidth" class="form-control" type="text" name="maxWidth" value="{$maxWidth}">
                </label>
                <input type="submit" class="wikiaction btn btn-primary" name="setSize" value="{tr}Submit{/tr}">
            </form>
        </div><br>
        {pagination_links cant=$cant step=$maxRecords offset=$offset}
            tiki-list_file_gallery.php?galleryId={$galleryId}&maxWidth={$maxWidth}&maxRecords={$maxRecords}&view={$view}
        {/pagination_links}
        <br>
    {/if}
    {if $prefs.fgal_quota_show neq 'n' and $gal_info.quota}
        <div style="float:right; width: 350px;">
            {if $gal_info.usedSize neq null}
            {capture name='use'}
                {math equation="round((100*x)/(1024*1024*y),0)" x=$gal_info.usedSize y=$gal_info.quota}
            {/capture}
            {capture name='left_percent'}
                {math equation="round(100-(100*x)/(1024*1024*y),0)" x=$gal_info.usedSize y=$gal_info.quota}
            {/capture}
            {capture name='left'}
                {math equation="round(y - x/(1024*1024),0)" y=$gal_info.quota x=$gal_info.usedSize}
            {/capture}
            {/if}
            {if $prefs.fgal_quota_show neq 'text_only'}{if $gal_info.usedSize neq null}
                <div class="progress" style="display:inline-block;float:right;width: 250px;">
                    <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="100"
                        aria-valuemin="0" aria-valuemax="100" style="width:{$smarty.capture.left_percent|string_format:'%d'}%">
                    </div>
                    <div class="progress-bar progress-bar-danger" role="progressbar" style="width:{$smarty.capture.use|string_format:'%d'}%">
                    </div>
                </div>
            {/if}
                {if $gal_info.usedSize eq null}
                    <div class="progress" style="display:inline-block;float:right; width: 250px;">
                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100" style="width:100%">
                        </div>
                        <div class="progress-bar progress-bar-danger" role="progressbar" style="width:0%">
                        </div>
                    </div>
                {/if}
            {/if}
            {if $prefs.fgal_quota_show neq 'y'}
                {if $gal_info.usedSize eq null}
                    <div style="text-align:center;display:inline-block;float:right;padding-right: 10px;"><strong>{$gal_info.quota} MB</strong> left</div>
                {else}
                    <div style="text-align:center;display:inline-block;float:right;padding-right: 10px;"><strong>{$smarty.capture.left} MB</strong> left</div>
                {/if}
            {/if}
        </div>
    {/if}
    {if $prefs.fgal_elfinder_feature eq 'y' and $view eq 'finder'}<br>
        <div class="elFinderDialog" style="height: 100%" data-ticket="{ticket mode=get}"></div>
        {jq}

var elfoptions = initElFinder({
    defaultGalleryId: {{$galleryId}},
    defaultVolumeId: {{$volumeId}},
    deepGallerySearch:1,
    requestType: 'post',
    getFileCallback: function(file,elfinder) { window.handleFinderFile(file,elfinder); },
    height: 600
});

var $elFinderInstance = $(".elFinderDialog").elfinder(elfoptions).elfinder('instance');
$elFinderInstance.customData['ticket'] = $(".elFinderDialog").data('ticket');
// when changing folders update the buttons in the navebar above
$elFinderInstance.bind("open", function (data) {
    $.getJSON($.service('file_finder', 'finder'), {
        cmd: "tikiFileFromHash",
        hash: data.data.cwd.hash
    }).done(function (data) {
        var href = '';
        $(".t_navbar a").each(function () {
            href = $(this).attr("href");
            if (href) {    // avoid chosen select replacements
                href = href.replace(/(galleryId|objectId|parentId|watch_object)=\d+/, '$1=' + data.galleryId);
                $(this).attr("href", href);
            }
        });
    });
});

window.handleFinderFile = function (file, elfinder) {
    var hash = "";
    if (typeof file === "string") {
        var m = file.match(/target=([^&]*)/);
        if (!m || m.length < 2) {
            return false;    // error?
        }
        hash = m[1];
    } else {
        hash = file.hash;
    }
    $.ajax({
        type: 'POST',
        url: $.service('file_finder', 'finder'),
        dataType: 'json',
        data: {
            cmd: "tikiFileFromHash",
            {{if !empty($filegals_manager)}}
                filegals_manager: "{{$filegals_manager}}",
            {{/if}}
            {{if !empty($insertion_syntax)}}
                insertion_syntax: "{{$insertion_syntax}}",
            {{/if}}
            hash: hash
        },
        success: function (data) {
            {{if !empty($filegals_manager)}}
                window.opener.insertAt('{{$filegals_manager}}', processFgalSyntax(data), false, false, true);
                checkClose();
            {{/if}}
        }
    });
};
        {/jq}
    {else}
        {include file='list_file_gallery.tpl'}
    {/if}

    {if $galleryId gt 0
        && $prefs.feature_file_galleries_comments == 'y'
        && ($tiki_p_read_comments == 'y'
        || $tiki_p_post_comments == 'y'
        || $tiki_p_edit_comments == 'y')}

        <div id="page-bar">
            <a id="comment-toggle" href="{service controller=comment action=list type="file gallery" objectId=$galleryId}#comment-container" class="btn btn-primary btn-sm">
                {icon name="comments"} {tr}Comments{/tr}
            </a>
            {jq}
                $('#comment-toggle').comment_toggle();
            {/jq}
        </div>

        <div id="comment-container"></div>
    {/if}
{/if}

{if $galleryId>0}
    {if $edited eq 'y'}
        {remarksbox type="tip" title="{tr}Information{/tr}"}
            {tr}You can access the file gallery using the following URL:{/tr} <a class="fgallink alert-link" href="{$url}?galleryId={$galleryId}">{$url}?galleryId={$galleryId}</a>
        {/remarksbox}
    {/if}
{/if}
