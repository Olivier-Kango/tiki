{title admpage="articles" help="Articles"}{tr}Submissions{/tr}{/title}

<div class="t_navbar margin-bottom-md">
	{button href="tiki-edit_submission.php" class="btn btn-default" _text="{tr}New Submission{/tr}"}
	{if $tiki_p_read_article eq 'y'}
		{button href="tiki-list_articles.php" class="btn btn-default" _text="{tr}List Articles{/tr}"}
	{/if}
</div>

{if $listpages or ($find ne '') or ($types ne '') or ($topics ne '') or ($lang ne '') or ($categId ne '')}
    <div class="row row-sidemargins-zero">
        <div class="col-md-6">
            {include file='find.tpl' find_show_languages='y' find_show_num_rows='y'}
        </div>
    </div>
{/if}
<form name="checkform" method="post">
	<input type="hidden" name="maxRecords" value="{$maxRecords|escape}">
	<div class="table-responsive">
		<table class="table normal">
			{assign var=numbercol value=0}
			<tr>
				{if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'}
					<th class="auto">
						{if $listpages}
							{select_all checkbox_names='checked[]'}
						{/if}
					</th>
				{/if}
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
						<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'publishDate_desc'}publishDate_asc{else}publishDate_desc{/if}">{tr}Publish Date{/tr}</a>
					</th>
				{/if}
				{if $prefs.art_list_expire eq 'y'}
					{assign var=numbercol value=$numbercol+1}
					<th>
						<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'expireDate_desc'}expireDate_asc{else}expireDate_desc{/if}">{tr}Expiry Date{/tr}</a>
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
					<th>{tr}Image{/tr}</th>
				{/if}
				{if $prefs.art_list_author eq 'y'}
					{assign var=numbercol value=$numbercol+1}
					<th>
						<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'author_desc'}author_asc{else}author_desc{/if}">{tr}User{/tr}</a>
					</th>
				{/if}
				{if $prefs.art_list_authorName eq 'y'}
					{assign var=numbercol value=$numbercol+1}
					<th>
						<a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'authorName_desc'}authorName_asc{else}authorName_desc{/if}">{tr}Author{/tr}</a>
					</th>
				{/if}
				{assign var=numbercol value=$numbercol+1}
				<th>{tr}Actions{/tr}</th>
			</tr>

			{section name=changes loop=$listpages}
				<tr>
					{if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'}
						<td class="checkbox-cell">
							<input type="checkbox" name="checked[]" value="{$listpages[changes].subId|escape}" {if $listpages[changes].checked eq 'y'}checked="checked" {/if}>
						</td>
					{/if}
					{if $prefs.art_list_title eq 'y'}
						<td class="text">
							<a class="link" title="{$listpages[changes].title|escape}" href="tiki-edit_submission.php?subId={$listpages[changes].subId}">{$listpages[changes].title|truncate:$prefs.art_list_title_len:"...":true|escape}</a>
						</td>
					{/if}
					{if $prefs.art_list_topic eq 'y'}
						<td class="text">{$listpages[changes].topicName|escape}</td>
					{/if}
					{if $prefs.art_list_date eq 'y'}
						<td class="date" title="{$listpages[changes].publishDate|tiki_short_datetime}">{$listpages[changes].publishDate|tiki_short_date}</td>
					{/if}
					{if $prefs.art_list_expire eq 'y'}
						<td class="date" title="{$listpages[changes].expireDate|tiki_short_datetime}">{$listpages[changes].expireDate|tiki_short_date}</td>
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
					{if $prefs.art_list_authorName eq 'y'}
							<td class="text">{$listpages[changes].authorName|escape}</td>
					{/if}
					<td class="action">
						{if $tiki_p_edit_submission eq 'y' or ($listpages[changes].author eq $user and $user)}
							<a class="link" href="tiki-edit_submission.php?subId={$listpages[changes].subId}">{icon _id='page_edit'}</a>
						{/if}
						{if $tiki_p_approve_submission eq 'y'}
							{self_link approve=$listpages[changes].subId}{icon _id='accept' alt="{tr}Approve{/tr}"}{/self_link}
						{/if}
						{if $tiki_p_remove_submission eq 'y'}
							{self_link remove=$listpages[changes].subId}{icon _id='cross' alt="{tr}Remove{/tr}"}{/self_link}
						{/if}
					</td>
				</tr>
			{sectionelse}
				{assign var=numbercol value=$numbercol+1}
				{norecords _colspan=$numbercol}
			{/section}
			{if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'}
				<tr>
					<td colspan="{$numbercol+1}">
						{if $listpages}
							<p align="left"> {*on the left to have it close to the checkboxes*}
								{if $tiki_p_remove_submission eq 'y'}
									{button _text="{tr}Select Duplicates{/tr}" _onclick="checkDuplicateRows(this,'td:not(:eq(2))'); return false;"}
								{/if}
								<label>{tr}Perform action with checked:{/tr}
									<select name="submit_mult">
										<option value=""></option>
										{if $tiki_p_remove_submission eq 'y'}<option value="remove_subs" >{tr}Remove{/tr}</option>{/if}
										{if $tiki_p_approve_submission eq 'y'}<option value="approve_subs" >{tr}Approve{/tr}</option>{/if}
									</select>
								</label>
								<input type="submit" class="btn btn-default btn-sm" value="{tr}Ok{/tr}">
							</p>
						{/if}
					</td>
				</tr>
			{/if}
		</table>
	</div>
	{pagination_links cant=$cant_pages step=$maxRecords offset=$offset}{/pagination_links}
</form>
