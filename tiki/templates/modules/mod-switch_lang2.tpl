{* $Header: /cvsroot/tikiwiki/tiki/templates/modules/mod-switch_lang2.tpl,v 1.8 2007-02-18 11:21:17 mose Exp $ *}

{if !isset($tpl_module_title)}{assign var=tpl_module_title value="{tr}Language :{/tr} $language"}{/if}
{tikimodule title=$tpl_module_title name="switch_lang2" flip=$module_params.flip decorations=$module_params.decorations}
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
{section name=ix loop=$languages}
  <td align="center">
    <a title="{$languages[ix].name|escape}" class="linkmodule" href="tiki-switch_lang.php?language={$languages[ix].value|escape}">
      {$languages[ix].display|escape}
    </a>
  </td>
  {if not ($smarty.section.ix.rownum mod 3)}
    {if not $smarty.section.ix.last}
      </tr><tr>
    {/if}
  {/if}
{/section}
</tr></table>
{/tikimodule}
