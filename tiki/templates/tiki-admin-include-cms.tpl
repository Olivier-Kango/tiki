<a name="cms"></a>
<div class="cbox">
<div class="cbox-title">{tr}CMS settings{/tr}</div>
<div class="cbox-data">



<div class="simplebox">
{tr}CMS features{/tr}<br/>
<form action="tiki-admin.php" method="post">
    <table width="100%">
    <tr><td class="form">{tr}Rankings{/tr}:</td><td><input type="checkbox" name="feature_cms_rankings" {if $feature_cms_rankings eq 'y'}checked="checked"{/if}/></td></tr>
    <tr><td class="form">{tr}Comments{/tr}:</td><td><input type="checkbox" name="feature_article_comments" {if $feature_article_comments eq 'y'}checked="checked"{/if}/></td></tr>
    <tr><td class="form">{tr}Spellchecking{/tr}:</td><td><input type="checkbox" name="cms_spellcheck" {if $cms_spellcheck eq 'y'}checked="checked"{/if}/></td></tr>
    <tr><td class="form">{tr}Use templates{/tr}:</td><td><input type="checkbox" name="feature_cms_templates" {if $feature_cms_templates eq 'y'}checked="checked"{/if}/></td></tr>
    <tr><td align="center" colspan="2"><input type="submit" name="cmsfeatures" value="{tr}Set features{/tr}" /></td></tr>    
    </table>
</form>
</div>
<div class="simplebox">
<form method="post" action="tiki-admin.php">
<table>
  <tr><td class="form">{tr}Maximum number of articles in home{/tr}: </td><td><input size="5" type="text" name="maxArticles" value="{$maxArticles}" /></td></tr>
  <tr><td align="center" colspan="2"><input type="submit" name="cmsprefs" value="{tr}Change preferences{/tr}" /></td></tr>
</table>
</form>
</div>

    <div class="simplebox">
    {tr}Article comments settings{/tr}
    <form method="post" action="tiki-admin.php">
    <table>
    <tr><td class="form">{tr}Default number of comments per page{/tr}: </td><td><input size="5" type="text" name="article_comments_per_page" value="{$article_comments_per_page}" /></td></tr>
    <tr><td class="form">{tr}Comments default ordering{/tr}
    </td><td>
    <select name="article_comments_default_ordering">
    <option value="commentDate_desc" {if $article_comments_default_ordering eq 'commentDate_dec'}selected="selected"{/if}>{tr}Date{/tr}</option>
    <option value="points_desc" {if $article_comments_default_ordering eq 'points_desc'}selected="selected"{/if}>{tr}Points{/tr}</option>
    </select>
    </td></tr>
    <tr><td align="center" colspan="2"><input type="submit" name="articlecomprefs" value="{tr}Change preferences{/tr}" /></td></tr>
    </table>
    </form>
    </div>
    
    <div class="simplebox">
    {tr}Articles listing configuration{/tr} <a href="tiki-list_articles.php" class="link">{tr}view{/tr}</a>
	<form method="post" action="tiki-admin.php">
	<table>
	<tr>
		<td class="form">{tr}Title{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_title" {if $art_list_title eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">{tr}Topic{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_topic" {if $art_list_topic eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">{tr}Date{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_date" {if $art_list_date eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">{tr}Author{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_author" {if $art_list_author eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">{tr}Reads{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_reads" {if $art_list_reads eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">{tr}Size{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_size" {if $art_list_size eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">{tr}Img{/tr}</td>
		<td class="form">
			<input type="checkbox" name="art_list_img" {if $art_list_img eq 'y'}checked="checked"{/if} />
		</td>
	</tr>
	<tr>
		<td class="form">&nbsp;</td>
		<td class="form">
			<input type="submit" name="artlist" value="set preferences" />
		</td>
	</tr>


	</table>
	</form>    
    </div>
    

</div>
</div>

