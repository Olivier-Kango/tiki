{popup_init src="lib/overlib.js"}
<!--<a {popup sticky="true" trigger="onClick" caption="Special characters help" text="kj"}>foo</a><br/>-->
{if $preview}
{include file="tiki-preview.tpl"}
{/if}
<h1>{tr}Edit{/tr}: {$page}</h1>
{if $page eq 'SandBox'}
<div class="wikitext">
{tr}The SandBox is a page where you can practice your editing skills, use the preview feature
to preview the appeareance of the page, no versions are stored for this page.{/tr}
</div>
{/if}
<form  method="post" action="tiki-editpage.php" id='editpageform'>
<table class="normal">
<tr><td class="formcolor">{tr}Quicklinks{/tr}</td><td class="formcolor">
{assign var=area_name value="editwiki"}
{include file=tiki-edit_help_tool.tpl}
</td>

{include file=categorize.tpl}

{if $feature_wiki_templates eq 'y' and $tiki_p_use_content_templates eq 'y'}
<tr><td class="formcolor">{tr}Apply template{/tr}</td><td class="formcolor">
<select name="templateId" onChange="javascript:document.getElementById('editpageform').submit();">
<option value="0">{tr}none{/tr}</option>
{section name=ix loop=$templates}
<option value="{$templates[ix].templateId}">{tr}{$templates[ix].name}{/tr}</option>
{/section}
</select>
</td></tr>
{/if}
{if $feature_smileys eq 'y'}
<tr><td class="formcolor">{tr}Smileys{/tr}</td><td class="formcolor">
<table>
     <tr>
          <td><a href="javascript:setSomeElement('editwiki','(:biggrin:)');"><img src="img/smiles/icon_biggrin.gif" alt="big grin" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:confused:)');"><img src="img/smiles/icon_confused.gif" alt="confused" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:cool:)');"><img src="img/smiles/icon_cool.gif" alt="cool" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:cry:)');"><img src="img/smiles/icon_cry.gif" alt="cry" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:eek:)');"><img src="img/smiles/icon_eek.gif" alt="eek" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:evil:)');"><img src="img/smiles/icon_evil.gif" alt="evil" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:exclaim:)');"><img src="img/smiles/icon_exclaim.gif" alt="exclaim" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:frown:)');"><img src="img/smiles/icon_frown.gif" alt="frown" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:idea:)');"><img src="img/smiles/icon_idea.gif" alt="idea" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:lol:)');"><img src="img/smiles/icon_lol.gif" alt="lol" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:mad:)');"><img src="img/smiles/icon_mad.gif" alt="mad" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:mrgreen:)');"><img src="img/smiles/icon_mrgreen.gif" alt="mr green" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:neutral:)');"><img src="img/smiles/icon_neutral.gif" alt="neutral" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:question:)');"><img src="img/smiles/icon_question.gif" alt="question" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:razz:)');"><img src="img/smiles/icon_razz.gif" alt="razz" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:redface:)');"><img src="img/smiles/icon_redface.gif" alt="redface" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:rolleyes:)');"><img src="img/smiles/icon_rolleyes.gif" alt="rolleyes" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:sad:)');"><img src="img/smiles/icon_sad.gif" alt="sad" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:smile:)');"><img src="img/smiles/icon_smile.gif" alt="smile" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:surprised:)');"><img src="img/smiles/icon_surprised.gif" alt="surprised" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:twisted:)');"><img src="img/smiles/icon_twisted.gif" alt="twisted" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:wink:)');"><img src="img/smiles/icon_wink.gif" alt="wink" border="0" /></a></td>
          <td><a href="javascript:setSomeElement('editwiki','(:arrow:)');"><img src="img/smiles/icon_arrow.gif" alt="arrow" border="0" /></a></td>
      </tr>    
      </table>
</td>
</tr>
{/if}
<!--<a class="link" href="javascript:setSomeElement('editwiki',"''text here''");">i</a>-->
<tr><td class="formcolor">{tr}edit{/tr}</td><td class="formcolor">
<textarea id='editwiki' class="wikiedit" name="edit" rows="22" cols="80" wrap="virtual">{$pagedata}</textarea>
</td>
{if $page ne 'SandBox'}
<tr><td class="formcolor">{tr}Comment{/tr}:</td><td class="formcolor"><input size="50" class="wikitext" name="comment" value="{$commentdata}" /></td>
{/if}
{if $tiki_p_use_HTML eq 'y'}
<tr><td class="formcolor">{tr}Allow HTML{/tr}: </td><td class="formcolor"><input type="checkbox" name="allowhtml" {if $allowhtml eq 'y'}checked="checked"{/if}/></td>
{/if}
{if $wiki_spellcheck eq 'y'}
<tr><td class="formcolor">{tr}Spellcheck{/tr}: </td><td class="formcolor"><input type="checkbox" name="spellcheck" {if $spellcheck eq 'y'}checked="checked"{/if}/></td>
{/if}
<input type="hidden" name="page" value="{$page}" />
<tr><td class="formcolor">&nbsp;</td><td class="formcolor"><input type="submit" class="wikiaction" name="preview" value="{tr}preview{/tr}" /></td>
<tr><td class="formcolor">&nbsp;</td><td class="formcolor"><input type="submit" class="wikiaction" name="save" value="{tr}save{/tr}" /><input type="submit" class="wikiaction" name="cancel" value="{tr}cancel{/tr}" /></td>
</tr>
</table>
</form>
<br/>
<!--<a href="javascript:replaceSome('editwiki','foo','bar');">foo2bar</a>-->
{include file=tiki-edit_help.tpl}