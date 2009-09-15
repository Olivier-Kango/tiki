{* $Id$ *}

{if isset($zoom_mode) and $zoom_mode eq 'y'}
<form id='editpageform' name='editpageform' method='post' action='tiki-editpage.php' enctype='multipart/form-data'>
{* The line below is used to generate all input hidden tags needed to keep modifications (e.g. categories, freetags, ...) between zoom mode and normal mode *}
{query _type='form_input' edit=NULL zoom=NULL zoom_value=NULL zoom_x=NULL zoom_y=NULL}
{/if}

{if !isset($textarea_attributes)}
	{assign var=textarea_attributes value=" rows='$rows' cols='$cols' style='width:99%'"}
{/if}
<div id='edit-zone'>
	<div class='textarea-toolbar' id='{$textarea_id|default:editwiki}_toolbar'>
		{toolbars area_name=$textarea_name|default:edit}
	</div>
	{if isset($noautosave) and $noautosave neq 'y' and $has_autosave eq 'y'} 
		{remarksbox type="warning" title="{tr}AutoSave{/tr}"}
			{tr}If you want the saved version instead of the autosaved one{/tr}&nbsp;{self_link noautosave='y' _ajax='n'}{tr}Click Here{/tr}{/self_link}
		{/remarksbox}
	{/if} 
	<textarea id="{$textarea_id|default:editwiki}" class="{$textarea_class|default:wikiedit}" name="{$textarea_name|default:edit}" {$textarea_attributes}>{$pagedata}</textarea>
</div>

{if isset($zoom_mode) and $zoom_mode eq 'y'}
<div id='{$textarea_id|default:editwiki}_actions' style="width:99%; text-align:right;">
{include file='wiki_edit_actions.tpl'}
</div>
{jq}
$jq('#{{$textarea_id|default:editwiki}}').height($jq(window).height() - $jq('#{{$textarea_id|default:editwiki}}_toolbar').height() - $jq('#{{$textarea_id|default:editwiki}}_actions').height() - 15);
{/jq}
</form>
{/if}
