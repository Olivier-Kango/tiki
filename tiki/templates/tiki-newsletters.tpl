<a href="tiki-newsletters.php" class="pagetitle">{tr}Newsletters{/tr}</a><br/><br/>
{if $subscribed eq 'y'}
{tr}Thanks for your subscription. You will receive an email soon to confirm your subscription. No newsletters will be sent to you until the subscription is confirmed.{/tr}<br/><br/>
{/if}
{if $unsub eq 'y'}
{tr}Your email address was removed from the list of subscriptors.{/tr}<br/><br/>
{/if}

{if $confirm eq 'y'}
<table class="normal">
<tr>
  <td colspan="2" class="heading">{tr}Subscription confirmed!{/tr}</td>
</tr>
<tr>
  <td class="even">{tr}Name{/tr}:</td>
  <td class="even">{$nl_info.name}</td>
</tr>
<tr>
  <td class="even">{tr}Description{/tr}:</td>
  <td class="even">{$nl_info.description}</td>
</tr>
</table>
{/if}
{if $subscribe eq 'y'}
<form method="post" action="tiki-newsletters.php">
<input type="hidden" name="nlId" value="{$nlId}" />
<table class="normal">
<tr>
  <td colspan="2" class="heading">{tr}Subscribe to newsletter{/tr}</td>
</tr>
<tr>
  <td class="even">{tr}Name{/tr}:</td>
  <td class="even">{$nl_info.name}</td>
</tr>
<tr>
  <td class="even">{tr}Description{/tr}:</td>
  <td class="even">{$nl_info.description}</td>
</tr>
{if $tiki_p_subscribe_email eq 'y'}
<tr>
  <td class="even">{tr}Email:{/tr}</td>
  <td class="even"><input type="text" name="email" value="{$email}" /></td>
</tr>
{else}
  <input type="hidden" name="email" value="{$email}" />
{/if}
<tr>
  <td class="even">&nbsp;</td>
  <td class="even"><input type="submit" name="subscribe" value="{tr}Subscribe{/tr}" /></td>
</tr>
</table>
</form>
{/if}

<h2>{tr}Newsletters{/tr}</h2>
<div  align="center">
<table class="findtable">
<tr><td class="findtable">{tr}Find{/tr}</td>
   <td class="findtable">
   <form method="get" action="tiki-admin_newsletters.php">
     <input type="text" name="find" value="{$find}" />
     <input type="submit" value="{tr}find{/tr}" name="search" />
     <input type="hidden" name="sort_mode" value="{$sort_mode}" />
   </form>
   </td>
</tr>
</table>
<table class="normal">
<tr>
<td class="heading"><a class="tableheading" href="tiki-admin_newsletters.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}name{/tr}</a></td>
<td class="heading"><a class="tableheading" href="tiki-admin_newsletters.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'description_desc'}description_asc{else}description_desc{/if}">{tr}description{/tr}</a></td>
</tr>
{cycle values="odd,even" print=false}
{section name=user loop=$channels}
{if $channels.individual ne 'y' or $channels.individual_tiki_p_subscribe_newsletters eq 'y'}
<tr>
<td class="{cycle advance=false}"><a class="tablename" href="tiki-newsletters.php?nlId={$channels[user].nlId}&amp;info=1">{$channels[user].name}</a></td>
<td class="{cycle advance=false}">{$channels[user].description}</td>
</tr>
{/if}
{/section}
</table>
<div class="mini">
{if $prev_offset >= 0}
[<a class="prevnext" href="tiki-admin_newsletters.php?find={$find}&amp;offset={$prev_offset}&amp;sort_mode={$sort_mode}">{tr}prev{/tr}</a>]&nbsp;
{/if}
{tr}Page{/tr}: {$actual_page}/{$cant_pages}
{if $next_offset >= 0}
&nbsp;[<a class="prevnext" href="tiki-admin_newsletters.php?find={$find}&amp;offset={$next_offset}&amp;sort_mode={$sort_mode}">{tr}next{/tr}</a>]
{/if}
{if $direct_pagination eq 'y'}
<br/>
{section loop=$cant_pages name=foo}
{assign var=selector_offset value=$smarty.section.foo.index|times:$maxRecords}
<a class="prevnext" href="tiki-admin_newsletters.php?find={$find}&amp;offset={$selector_offset}&amp;sort_mode={$sort_mode}">
{$smarty.section.foo.index_next}</a>&nbsp;
{/section}
{/if}

</div>
</div>
