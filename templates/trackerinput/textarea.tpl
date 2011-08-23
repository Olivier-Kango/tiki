{strip}
{if $field.isMultilingual ne 'y'}
	{if $field.options_array[2] == 1}
		{if $field.options_array[0] eq 1}
   			{toolbars qtnum=$field.fieldId area_id="area_"|cat:$field.fieldId section="trackers"}
		{/if}
		<input type="text" id="area_{$field.fieldId}" name="{$field.ins_id}"{if $field.options_array[1] > 0} size="{$field.options_array[1]}"{/if}{if $field.options_array[3]>0} maxlength="{$field.options_array[3]}"{/if} value="{$field.value|escape}" onkeyup=$keyup />
	{else}
		{if $field.options_array[7] == 'y'}
			{textarea id="area_`$field.fieldId`" name=$field.ins_id cols=$context.cols rows=$context.rows onkeyup=$context.keyup _wysiwyg='y' section="trackers"}
				{$field.value}
			{/textarea}
		{else}
			{textarea id="area_`$field.fieldId`" name=$field.ins_id _toolbars=$context.toolbar _simple='y' cols=$context.cols rows=$context.rows onkeyup=$context.keyup _wysiwyg='n' section="trackers"}
				{$field.value}
			{/textarea}
		{/if}
	{/if}
	{if $field.options_array[3]}
		<div class="charCount">
			{if $prefs.javascript_enabled eq 'y'}
			 	 {tr}Character Count:{/tr} <input type="text" id="cpt_{$field.fieldId}" size="4" readOnly="true"{if !empty($field.value)} value="{$field.value|count_characters}"{/if} />
			{/if}
			{if $field.options_array[3] > 0} {tr}Max:{/tr} {$field.options_array[3]}{/if}
		</div>
	{/if}
	{if $field.options_array[5]}
		<div class="wordCount">
			{if $prefs.javascript_enabled eq 'y'}
				 {tr}Word Count:{/tr} <input type="text" id="cpt_{$field.fieldId}" size="4" readOnly="true"{if !empty($field.value)} value="{$field.value|count_words}"{/if} />
			{/if}
			{if $field.options_array[5] > 0} {tr}Max:{/tr} {$field.options_array[5]}{/if}
		</div>
	{/if}
{else}
	{foreach name=lg from=$field.lingualvalue item=ling}
		<label for="area_{$field.fieldId}_{$ling.lang}">{$ling.lang|langname}</label><br />
			{if $field.options_array[0] eq 1}
       			{toolbars qtnum=$field.id area_id="area_"|cat:$field.id|cat:'_'|cat:$ling.lang}
       		{/if}
			<textarea id="area_{$field.fieldId}_{$ling.lang}" name="{$field.ins_id}[{$ling.lang}]" cols="{if $field.options_array[1] gt 1}{$field.options_array[1]}{else}50{/if}" rows="{if $field.options_array[2] gt 1}{$field.options_array[2]}{else}4{/if}"{if $field.options_array[5] > 0} onkeyup="wordCount({$field.options_array[5]}, this, 'cpt_{$field.fieldId}_{$ling.lang}', '{tr}Word Limit Exceeded{/tr}')"{/if}>
				{$ling.value|escape}
			</textarea>
			{if $field.options_array[5]}<div class="wordCount">{tr}Word Count:{/tr} <input type="text" id="cpt_{$field.fieldId}_{$ling.lang}" size="4" readOnly="true"{if !empty($ling.value)} value="{$ling.value|count_words}"{/if} />{if $field.options_array[5] > 0}{tr}Max:{/tr} {$field.options_array[5]}{/if}</div>i{elseif !$smarty.foreach.lg.last}<br />{/if}
	{/foreach}
{/if}
{/strip}
