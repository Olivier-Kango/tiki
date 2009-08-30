{* based on $Id$ *}

{if isset($modFreetagsCurrent) && count($modFreetagsCurrent) gt 0}
  {tikimodule error=$module_params.error title=$tpl_module_title name="freetags_current" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
	{if $modFreetagsCurrent.cant gt 0}
	<div class="heading">{tr}Tags This Page Has{/tr}</div>
	{section name=ix loop=$modFreetagsCurrent.data}
     <div class="module">
     <a class="linkmodule" href="tiki-browse_freetags.php?tag={$modFreetagsCurrent.data[ix].tag|escape:'url'}">{$modFreetagsCurrent.data[ix].tag|escape}</a>
	 </div>
	{/section}
	{/if}
  {if isset($addFreetags)}
  <form method="post" action="">
  <div>
  <input type="text" name="tags" value=""/>
  <input type="submit" name="mod_add_tags" value="{tr}Add tags{/tr}"/>
  </div>
  </form>
  {/if}
  {/tikimodule}
{/if}
