{if $p.helpurl}
	{$icon = "help"}
{elseif $p.description}
	{$icon = "information"}
{/if}
{if isset($icon)}
	<a {if $p.helpurl} href="{$p.helpurl|escape}" target="tikihelp"{/if}
	 	class="tikihelp text-info" title="{$p.name|escape}: {$p.description|escape} {if $p.separator && $p.type neq 'multiselector'}<br>{tr _0=$p.separator}Use &quot;%0&quot; to separate values.{/tr}{/if}">
		{icon name=$icon}
	</a>
{/if}

{if $p.warning}
	<a href="#" target="tikihelp" class="tikihelp text-warning" title="{tr}Warning:{/tr} {$p.warning|escape}">
		{icon name="warning"}
	</a>
{/if}

{if $p.modified and $p.available}
	<span class="pref-reset-wrapper">
		<input class="pref-reset system" type="checkbox" name="lm_reset[]" value="{$p.preference|escape}" style="display:none" data-preference-default="{if is_array($p.default)}{$p.default|implode:$p.separator|escape}{else}{$p.default|escape}{/if}">
		<a href="#" class="pref-reset-undo tips" title="{tr}Reset{/tr}|{tr}Reset to default value{/tr}">{icon name="undo"}</a>
		<a href="#" class="pref-reset-redo tips" title="{tr}Restore{/tr}|{tr}Restore current value{/tr}" style="display:none">{icon name="repeat"}</a>
	</span>
{/if}

{if !empty($p.popup_html)}
	<a class="tips" title="{tr}Actions{/tr}" href="#" style="padding:0; margin:0; border:0" {popup fullhtml=1 center="true" text=$p.popup_html trigger="click"}>
		{icon name="actions"}
	</a>
{/if}
{if !empty($p.voting_html)}
	{$p.voting_html}
{/if}

{$p.pages}

{if not $pref_filters.advanced.selected and in_array('advanced', $p.tags)}
	<label class="label label-warning tips" title=":{tr}Change your preference filter settings in order to view advanced preferences by default{/tr}">
		{tr}advanced{/tr}
	</label>
{/if}
{if not $pref_filters.experimental.selected and in_array('experimental', $p.tags)}
	<label class="label label-danger tips" title=":{tr}Change your preference filter settings in order to view experimental preferences by default{/tr}">
		{tr}experimental{/tr}
	</label>
{/if}
{if $p.dependencies}
	{foreach from=$p.dependencies item=dep}
		{if $dep.met}
			{icon name="ok" class="pref_dependency tips text-success" title="{tr}Requires:{/tr} "|cat:$dep.label|escape|cat:" (OK)"}
		{elseif $dep.type eq 'profile'}
			<div class="alert alert-warning pref_dependency highlight"{if not $p.modified} style="display:none;"{/if}>{tr}You need apply profile{/tr} <a href="{$dep.link|escape}" class="alert-link">{$dep.label|escape}</a></div>
		{else}
			<div class="alert alert-warning pref_dependency highlight"{if not $p.modified} style="display:none;"{/if}>{tr}You need to set{/tr} <a href="{$dep.link|escape}" class="alert-link">{$dep.label|escape}</a></div>
		{/if}
	{/foreach}
{/if}

{* The 3 elements below are displayed with simple parsing (parse_data_simple()), which is probably better than using parse_data(), for performance and to obtain a more predictable parsing.
Converting these elements to HTML may still be better. Chealer *}
{if $p.shorthint}
	<div class="form-text">{$p.shorthint|parse:true}</div>
{/if}
{if $p.detail}
	<div class="form-text">{$p.detail|parse:true}</div>
{/if}
{if $p.hint}
	<div class="form-text">{$p.hint|parse:true}</div>
{/if}

{* Used by some preferences of type text (and textarea) *}
{if $p.translatable eq 'y'}
	{button _class="btn btn-link tips" _type="link" href="tiki-preference_translate.php?pref={$p.preference|escape}" _icon_name="language" _text="" _title=":{tr}Translate{/tr} {$p.name|escape}"}
{/if}

<input class="system" type="hidden" name="lm_preference[]" value="{$p.preference|escape}">
{if $p.packages_required}
	{foreach from=$p.packages_required item=dep}
		{if $dep.met}
			{icon name="ok" class="pref_dependency tips text-success" title="{tr}Requires package:{/tr} "|cat:$dep.label|escape|cat:" (OK)"}
		{else}
			<div class="alert alert-warning pref_dependency highlight"{if not $p.modified and not $p.value} style="display:none;"{/if}>{tr}Missing package:{/tr} <a href="{$dep.link|escape}" class="alert-link">{$dep.label|escape}</a></div>
		{/if}
	{/foreach}
{/if}
{foreach from=$p.notes item=note}
	<div class="form-text pref_note">{$note|escape}</div>
{/foreach}
