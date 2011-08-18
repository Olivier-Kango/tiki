{title}{tr}Submissions{/tr}{/title}

<div class="navbar">
	{button href="tiki-edit_submission.php" _text="{tr}Edit New Submission{/tr}"}
	{if $tiki_p_read_article eq 'y'}
		{button href="tiki-list_articles.php" _text="{tr}List Articles{/tr}"}
	{/if}
</div>

{include file='find.tpl' find_show_num_rows='y'}
<form name="checkform" method="get" action="{$smarty.server.PHP_SELF}">
	<input type="hidden" name="maxRecords" value="{$maxRecords|escape}" />
	<table class="normal">
		{assign var=numbercol value=0}
		<tr>
			<th class="auto">
				{if $listpages}
				   {select_all checkbox_names='checked[]'}
				{/if}
			</th>
			{if $prefs.art_list_title eq 'y'}
				{assign var=numbercol value=$numbercol+1}
				<th>
					<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_desc'}title_asc{else}title_desc{/if}">{tr}Title{/tr}</a>
				</th>
			{/if}
			{if $prefs.art_list_topic eq 'y'}
				{assign var=numbercol value=$numbercol+1}
				<th>
					<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'topicName_desc'}topicName_asc{else}topicName_desc{/if}">{tr}Topic{/tr}</a>
				</th>
			{/if}
			{if $prefs.art_list_date eq 'y'}
				{assign var=numbercol value=$numbercol+1}
				<th>
					<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'publishDate_desc'}publishDate_asc{else}publishDate_desc{/if}">{tr}PublishDate{/tr}</a>
				</th>
			{/if}
			{if $prefs.art_list_size eq 'y'}
				{assign var=numbercol value=$numbercol+1}
				<th style="text-align:right;">
					<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'size_desc'}size_asc{else}size_desc{/if}">{tr}Size{/tr}</a>
				</th>
			{/if}
			{if $prefs.art_list_img eq 'y'}
				{assign var=numbercol value=$numbercol+1}
				<th>{tr}Img{/tr}</th>
			{/if}
			{if $prefs.art_list_author eq 'y'}
				{assign var=numbercol value=$numbercol+1}
				<th>
					<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'author_desc'}author_asc{else}author_desc{/if}">{tr}User{/tr}</a>
				</th>
			{/if}
			{assign var=numbercol value=$numbercol+1}
			<th>{tr}Action{/tr}</th>
		</tr>
		{cycle values="odd,even" print=false}
		{section name=changes loop=$listpages}
			<tr class="{cycle}">
				<td class="checkbox">
					<input type="checkbox" name="checked[]" value="{$listpages[changes].subId|escape}" {if $listpages[changes].checked eq 'y'}checked="checked" {/if}/>
				</td>
				{if $prefs.art_list_title eq 'y'}
					<td class="text">
						<a class="link" title="{$listpages[changes].title|escape}" href="tiki-edit_submission.php?subId={$listpages[changes].subId}">{$listpages[changes].title|truncate:$prefs.art_list_title_len:"...":true|escape}</a>
					</td>
				{/if}
				{if $prefs.art_list_topic eq 'y'}
					<td class="text">{$listpages[changes].topicName|escape}</td>
				{/if}
				{if $prefs.art_list_date eq 'y'}
					<td class="date">{$listpages[changes].publishDate|tiki_short_datetime}</td>
				{/if}
				{if $prefs.art_list_size eq 'y'}
					<td class="integer">{$listpages[changes].size|kbsize}</td>
				{/if}
				{if $prefs.art_list_img eq 'y'}
					<td class="text">{$listpages[changes].hasImage}/{$listpages[changes].useImage}</td>
				{/if}
				{if $prefs.art_list_author eq 'y'}
					<td class="text">{$listpages[changes].author|escape}</td>
				{/if}
				<td class="action">
					{if $tiki_p_edit_submission eq 'y' or ($listpages[changes].author eq $user and $user)}
						<a class="link" href="tiki-edit_submission.php?subId={$listpages[changes].subId}">{icon _id='page_edit'}</a>
					{/if}
					{if $tiki_p_approve_submission eq 'y'}
						<a class="link" href="tiki-list_submissions.php?approve={$listpages[changes].subId}"><img src='img/icons2/post.gif' alt="{tr}Approve{/tr}" title="{tr}Approve{/tr}" /></a>
					{/if}
					{if $tiki_p_remove_submission eq 'y'}
						<a class="link" href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$listpages[changes].subId}">{icon _id='cross' alt="{tr}Remove{/tr}"}</a>
					{/if}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=$numbercol}
		{/section}
		<tr>
			<td colspan="7">
				{if $listpages}
					<p align="left"> {*on the left to have it close to the checkboxes*}
						{button _text="{tr}Select Duplicates{/tr}" _onclick="checkDuplicateRows(this); return false;"}
						<label>{tr}Perform action with checked:{/tr}
							<select name="submit_mult">
								<option value=""></option>
								<option value="remove_subs" >{tr}Remove{/tr}</option>
							</select>
						</label>
						<input type="submit" value="{tr}OK{/tr}" />
					</p>
				{/if}
			</td>
		</tr>
	</table>
	{pagination_links cant=$cant_pages step=$maxRecords offset=$offset}{/pagination_links}
<form>
