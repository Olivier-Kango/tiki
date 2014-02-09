{* $Id$ *}

<a name="attachments"></a>
{if $tiki_p_wiki_view_attachments == 'y' || $tiki_p_wiki_admin_attachments == 'y' || $tiki_p_wiki_attach_files == 'y'}

	<div
		{if isset($pagemd5) && $pagemd5}
			{assign var=cookie_key value="show_attzone$pagemd5"}
			id="attzone{$pagemd5}"
		{else}
			{assign var=cookie_key value="show_attzone"}
			id="attzone"
		{/if}
		{if (isset($smarty.session.tiki_cookie_jar.$cookie_key) and $smarty.session.tiki_cookie_jar.$cookie_key eq 'y')
			or (!isset($smarty.session.tiki_cookie_jar.$cookie_key) and $prefs.w_displayed_default eq 'y')}
			style="display:block;"
		{else}
			style="display:none;"
		{/if}
	>

	{* Generate table if view permissions granted and if count of attached files > 0 *}

	{if ($tiki_p_wiki_view_attachments == 'y' || $tiki_p_wiki_admin_attachments == 'y') && count($atts) > 0}
		{if isset($offset)}
			{$offsetparam = "offset={$offset}&amp;"}
		{else}
			{$offsetparam = ''}
		{/if}
        <div class="table-responsive">
		<table class="table normal">
			<h3>{tr}Attached files{/tr}</h3>
			<tr>
				<th>
					<a href="tiki-index.php?page={$page|escape:"url"}&amp;{$offsetparam}sort_mode={if $sort_mode eq 'attId_desc'}attId_asc{else}attId_desc{/if}&amp;atts_show=y#attachments">{tr}ID{/tr}</a>
				</th>
				<th>
					<a href="tiki-index.php?page={$page|escape:"url"}&amp;{$offsetparam}sort_mode={if $sort_mode eq 'filename_desc'}filename_asc{else}filename_desc{/if}&amp;atts_show=y#attachments">{tr}Name{/tr}</a>
				</th>
				<th>
					<a href="tiki-index.php?page={$page|escape:"url"}&amp;{$offsetparam}sort_mode={if $sort_mode eq 'comment_desc'}comment_asc{else}comment_desc{/if}&amp;atts_show=y#attachments">{tr}Comment{/tr}</a>
				</th>
				<th>
					<a href="tiki-index.php?page={$page|escape:"url"}&amp;{$offsetparam}sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}&amp;atts_show=y#attachments">{tr}Uploaded{/tr}</a>
				</th>
				<th>
					<a href="tiki-index.php?page={$page|escape:"url"}&amp;{$offsetparam}sort_mode={if $sort_mode eq 'size_desc'}size_asc{else}size_desc{/if}&amp;atts_show=y#attachments">{tr}Size{/tr}</a>
				</th>
				<th>
					<a href="tiki-index.php?page={$page|escape:"url"}&amp;{$offsetparam}sort_mode={if $sort_mode eq 'hits_desc'}hits_asc{else}hits_desc{/if}&amp;atts_show=y#attachments">{tr}Downloads{/tr}</a>
				</th>
				<th>{tr}Actions{/tr}</th>
			</tr>
			{cycle values="odd,even" print=false advance=false}
			{section name=ix loop=$atts}
				<tr>
					<td class="id">{$atts[ix].attId}</td>
					<td class="text">
						{$atts[ix].filename|iconify}
						<a class="tablename" href="tiki-download_wiki_attachment.php?attId={$atts[ix].attId}&amp;page={$page|escape:"url"}&amp;download=y">{$atts[ix].filename}</a>
					</td>
					<td class="text"><small>{$atts[ix].comment|escape}</small></td>
					<td class="date">
						<small>{if $atts[ix].user}{$atts[ix].user|userlink}{/if} {$atts[ix].created|tiki_short_datetime}</small>
					</td>
					<td class="integer"><small>{$atts[ix].filesize|kbsize}</small></td>
					<td class="integer"><small>{$atts[ix].hits}</small></td>
					<td class="action">
						<a title="{tr}View{/tr}" href="tiki-download_wiki_attachment.php?attId={$atts[ix].attId}" target="_blank">{icon _id='monitor' alt="{tr}View{/tr}"}</a>
						<a title="{tr}Download{/tr}" href="tiki-download_wiki_attachment.php?attId={$atts[ix].attId}&amp;download=y">{icon _id='disk' alt="{tr}Download{/tr}"}</a>
						&nbsp;
						{if ($tiki_p_wiki_admin_attachments eq 'y' or ($user and ($atts[ix].user eq $user))) and $editable}
							<a title="{tr}Delete{/tr}" class="link" href="tiki-index.php?page={$page|escape:"url"}&amp;removeattach={$atts[ix].attId}&amp;{$offsetparam}{if !empty($sort_mode)}sort_mode={$sort_mode}{/if}"{if !empty($target)} target="{$target}"{/if}>{icon _id='cross' alt="{tr}Remove{/tr}"}</a>
						{/if}
					</td>
				</tr>
			{/section}
		</table>
        </div>
	{/if}{* Generate table if view ... attached files > 0 *}

	{* It is allow to attach files or current user have admin rights *}

	{if ($tiki_p_wiki_attach_files eq 'y' or $tiki_p_wiki_admin_attachments eq 'y')
		and (!isset($attach_box) or $attach_box ne 'n') and $editable}
		<div class="file-upload well well-sm">
			<form class="form-horizontal" enctype="multipart/form-data" action="tiki-index.php?page={$page|escape:"url"}" method="post">
				{if $page_ref_id}
					<input type="hidden" name="page_ref_id" value="{$page_ref_id|escape}">
				{/if}
				{if !empty($smarty.request.no_bl)}
					<input type="hidden" name="no_bl" value="{$smarty.request.no_bl|escape}">
				{/if}
				<div class="form-group">
					<label class="col-sm-2 control-label" for="attach-upload">{tr}Upload file{/tr}</label><input type="hidden" name="MAX_FILE_SIZE" value="1000000000">
					<div class="col-sm-10">
						<input class="form-control"  name="userfile1" type="file" id="attach-upload">
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-2 control-label" for="attach-comment">{tr}Comment{/tr}</label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="attach_comment" maxlength="250" id="attach-comment" placeholder="{tr}File upload comment{/tr}...">
					</div>
					<div class="col-sm-2">
						<input type="submit" class="btn btn-primary" name="attach" value="{tr}Attach{/tr}">
					</div>
				</div>
			</form>
		</div>
	{/if}
</div>
{/if}
