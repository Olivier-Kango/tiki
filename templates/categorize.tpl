{if $prefs.feature_categories eq 'y' and (count($categories) gt 0 or $tiki_p_admin_categories eq 'y')}
{if $notable neq 'y'}
<tr class="formcolor">
 <td>{tr}Categorize{/tr}</td>
 <td{if $colsCategorize} colspan="{$colsCategorize}"{/if}>
 {/if}
{if $mandatory_category >= 0}
  <div id="categorizator">
{else}
<a class="link" href="javascript:flip('categorizator');flip('categshow','inline');flip('categhide','inline');"{if ($mid eq 'tiki-editpage.tpl')}onclick="needToConfirm=false;"{/if}>
<span id="categshow" style="display:{if isset($smarty.session.tiki_cookie_jar.show_categorizator) and $smarty.session.tiki_cookie_jar.show_categorizator eq 'y'}none{else}inline{/if};">{tr}Show Categories{/tr}</span>
<span id="categhide" style="display:{if isset($smarty.session.tiki_cookie_jar.show_categorizator) and $smarty.session.tiki_cookie_jar.show_categorizator eq 'y'}inline{else}none{/if};">{tr}Hide Categories{/tr}</span>
</a>
  <div id="categorizator" style="display:{if isset($smarty.session.tiki_cookie_jar.show_categorizator) and $smarty.session.tiki_cookie_jar.show_categorizator eq 'y'}block{else}none{/if};">
{/if}
  {if count($categories) gt 0}
    <div style="vertical-align: middle; overflow-y: auto; overflow-x: hidden; height: 5em; width: 100%; border: 1px solid black;">
    <table width="100%">
      {cycle values="odd,even" print=false}
      {section name=ix loop=$categories}
      <tr class="{cycle}"><td><input type="checkbox" name="cat_categories[]" value="{$categories[ix].categId|escape}" {if $categories[ix].incat eq 'y'}checked="checked"{/if}/>{if $categories[ix].categpath}{$categories[ix].categpath}{else}{$categories[ix].name}{/if}</td></tr>
      {/section}
    </table>
    </div>
    <input type="hidden" name="cat_categorize" value="on" />
  {else}
    {tr}No categories defined{/tr} <br />
  {/if}
  {if $tiki_p_admin_categories eq 'y'}
    <a href="tiki-admin_categories.php" class="link">{tr}Admin Categories{/tr}</a>
  {/if}
  </div>
	{if $notable neq 'y'}
  </td>
</tr>
  {/if}
{/if}

