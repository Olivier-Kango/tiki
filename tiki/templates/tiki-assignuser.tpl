{* $Header: /cvsroot/tikiwiki/tiki/templates/tiki-assignuser.tpl,v 1.40 2007-09-13 15:36:36 jyhem Exp $ *}

<h1><a href="tiki-assignuser.php?assign_user={$assign_user|escape:url}" class="pagetitle">{tr}Assign User {$assign_user} to Groups{/tr}</a></h1>

<div class="navbar">
{if $tiki_p_admin eq 'y'} {* only full admins can manage groups, not tiki_p_admin_users *}
<span class="button2"><a href="tiki-admingroups.php" class="linkbut">{tr}Admin groups{/tr}</a></span>
{/if}
<span class="button2"><a href="tiki-adminusers.php" class="linkbut">{tr}Admin users{/tr}</a></span>
</div>

{if $feature_intertiki eq 'y' and !empty($feature_intertiki_mymaster)}
  <br /><b>{tr}Warning: since this tiki site is in slave mode, all user information you enter manually will be automatically overriden by other site's data, including users permissions{/tr}</b>
{/if}
  
<h2>{tr}User Information{/tr}</h2>
<table class="normal">
<tr><td class="even">{tr}Login{/tr}:</td><td class="odd">{$user_info.login}</td></tr>
<tr><td class="even">{tr}Email{/tr}:</td><td class="odd">{$user_info.email}</td></tr>
<tr><td class="even">{tr}Groups{/tr}:</td><td class="odd">
{foreach from=$user_info.groups item=what key=grp}
{if $what eq 'included'}<i>{/if}{$grp}{if $what eq 'included'}</i>{/if}
{if $grp != "Anonymous" && $grp != "Registered"}
(<a class="link" href="tiki-assignuser.php?offset={$offset}&amp;maxRecords={$maxRecords}&amp;sort_mode={$sort_mode}&amp;assign_user={$assign_user|escape:url}&amp;action=removegroup&amp;group={$grp|escape:url}" title="remove">x</a>)
{/if}&nbsp;&nbsp;
{/foreach}
</td></tr>
<form method="post" action="tiki-assignuser.php?assign_user={$assign_user}">
<tr><td class="even">{tr}Default Group{/tr}:</td><td class="odd">
<select name="defaultgroup">
<option value=""></option>
{foreach from=$user_info.groups key=name item=included}
<option value="{$name}" {if $name eq $user_info.default_group}selected="selected"{/if}>{$name}</option>
{/foreach}
</select>
<input type="hidden" value="{$user_info.login}" name="login" />
<input type="hidden" value="{$maxRecords}" name="maxRecords" />
<input type="hidden" value="{$offset}" name="offset" />
<input type="hidden" value="{$sort_mode}" name="sort_mode" />
<input type="submit" value="{tr}Set{/tr}" name="set_default" />
</form>
</td></tr>
</table>

<div align="left"><h2>{tr}Assign User {$assign_user} to Groups{/tr}</h2></div>
<table class="findtable">
<tr><td class="findtable">{tr}Find{/tr}</td>
   <td class="findtable">
   <form method="get" action="tiki-assignuser.php">
     <input type="text" name="find" value="{$find|escape}" />
     <input type="submit" value="{tr}Find{/tr}" name="search" />
     <input type="hidden" name="sort_mode" value="{$sort_mode|escape}" />
     <input type="hidden" name="assign_user" value="{$assign_user|escape}" />
	 {tr}Number of displayed rows{/tr}</td><td  class="findtitle"><input type="text" name="maxRecords" value="{$maxRecords|escape}" size="3" />
   </form>
   </td>
</tr>
</table>

<table class="normal">
<tr>
<td class="heading"><a class="tableheading" href="tiki-assignuser.php?assign_user={$assign_user|escape:url}&amp;offset={$offset}&amp;maxRecords={$maxRecords}&amp;sort_mode={if $sort_mode eq 'groupName_desc'}groupName_asc{else}groupName_desc{/if}">{tr}Name{/tr}</a></td>
<td class="heading"><a class="tableheading" href="tiki-assignuser.php?assign_user={$assign_user|escape:url}&amp;offset={$offset}&amp;maxRecords={$maxRecords}&amp;sort_mode={if $sort_mode eq 'groupDesc_desc'}groupDesc_asc{else}groupDesc_desc{/if}">{tr}Description{/tr}</a></td>
<td class="heading">{tr}Action{/tr}</td>
</tr>
{cycle values="even,odd" print=false}
{section name=user loop=$users}
{if $users[user].groupName != 'Anonymous'}
<tr>
<td class="{cycle advance=false}">
{if $tiki_p_admin eq 'y'}<a class="link" href="tiki-assignpermission.php?group={$users[user].groupName|escape:url}" title="{tr}Assign Perms to this Group{/tr}"><img border="0" alt="{tr}Permissions{/tr}" src="pics/icons/key.png" width='16' height='16' align="right" /></a>{/if}{$users[user].groupName}</td>
<td class="{cycle advance=false}">{tr}{$users[user].groupDesc}{/tr}</td>
<td class="{cycle}">
{if $users[user].what ne 'real'}
<a class="link" href="tiki-assignuser.php?offset={$offset}&amp;maxRecords={$maxRecords}&amp;sort_mode={$sort_mode}&amp;action=assign&amp;group={$users[user].groupName|escape:url}&amp;assign_user={$assign_user|escape:url}" title="{tr}Assign User to Group{/tr}"><img src="pics/icons/accept.png" border="0" width="16" height="16" alt='{tr}Assign{/tr}' /></a>
{elseif $users[user].groupName ne "Registered"}
<a class="link" href="tiki-assignuser.php?offset={$offset}&amp;maxRecords={$maxRecords}&amp;sort_mode={$sort_mode}&amp;assign_user={$assign_user|escape:url}&amp;action=removegroup&amp;group={$users[user].groupName|escape:url}" title="unassign"><img src="pics/icons/cross.png" border="0" width="16" height="16" alt='{tr}Unassign{/tr}' /></a>
{/if}
</td></tr>
{/if}
{/section}
</table>
<br />
<div class="mini">
{if $prev_offset >= 0}
[<a class="prevnext" href="tiki-assignuser.php?find={$find}&amp;assign_user={$assign_user|escape:url}&amp;offset={$prev_offset}&amp;sort_mode={$sort_mode}">{tr}Prev{/tr}</a>]&nbsp;&nbsp;
{/if}
{tr}Page{/tr}: {$actual_page}/{$cant_pages}
{if $next_offset >= 0}
&nbsp;&nbsp;[<a class="prevnext" href="tiki-assignuser.php?find={$find}&amp;assign_user={$assign_user|escape:url}&amp;offset={$next_offset}&amp;sort_mode={$sort_mode}">{tr}Next{/tr}</a>]
{/if}
{if $direct_pagination eq 'y'}
<br />
{section loop=$cant_pages name=foo}
{assign var=selector_offset value=$smarty.section.foo.index|times:$maxRecords}
<a class="prevnext" href="tiki-assignuser.php?find={$find}&amp;assign_user={$assign_user|escape:url}&amp;offset={$selector_offset}&amp;sort_mode={$sort_mode}">
{$smarty.section.foo.index_next}</a>&nbsp;
{/section}
{/if}

</div>
</div>
