{* a simplified version of templates/search/list/table.tpl which can be used as a start for a customised table plugin list template *}
{if not empty($column.field)}
	{$column = [$column]}{* if there is only one column then it will not be in an array *}
{/if}
<div class="table-responsive ts-wrapperdiv">
	<table class="table normal table-hover table-striped">
		<thead>
			{$header=false}
			{foreach from=$column item=col}
				{if !empty($col.label) or !empty($col.sort)}
					{$header=true}
					{break}
				{/if}
			{/foreach}
			{if $header}
				{$fieldcount = 0}
				<tr>
					{if $actions}
						{$fieldcount = 1}
						<th>
							<input type="checkbox" class="form-check-input listexecute-select-all" name="selectall" value="">
							<input type="hidden" name="objects{$iListExecute}[]" value="" class="listexecute-all">
						</th>
					{/if}
					{foreach from=$column item=col}
						{$fieldcount = $fieldcount + 1}
						<th{if not empty($col.class)} class="{$col.class}"{/if}>
							{if isset($col.sort) && $col.sort}
								{if !empty($sort_jsvar) and !empty($_onclick)}
									{$order = '_asc'}
									{if !empty($smarty.request.sort_mode) and stristr($smarty.request.sort_mode, $col.sort) neq false}
										{if stristr($smarty.request.sort_mode, '_asc')}
											{$order = '_desc'}
										{elseif stristr($smarty.request.sort_mode, '_nasc')}
											{$order = '_ndesc'}
										{elseif stristr($smarty.request.sort_mode, '_desc')}
											{$order = '_asc'}
										{elseif stristr($smarty.request.sort_mode, '_ndesc')}
											{$order = '_nasc'}
										{/if}
									{/if}
									{$click = $sort_jsvar|cat:'=\''|cat:$col.sort|cat:$order|cat:'\';'|cat:$_onclick}
									{if isset($col.translatelabel) && $col.translatelabel == 'y'}
										{self_link _onclick=$click _ajax='y' _sort_arg='sort_mode' _sort_field=$col.sort}{$col.label|tra|escape}{/self_link}
									{else}
										{self_link _onclick=$click _ajax='y' _sort_arg='sort_mode' _sort_field=$col.sort}{$col.label|escape}{/self_link}
									{/if}
								{else}
									{if isset($col.translatelabel) && $col.translatelabel == 'y'}
										{self_link _sort_arg=$sort_arg _sort_field=$col.sort}{$col.label|tra|escape}{/self_link}
									{else}
										{self_link _sort_arg=$sort_arg _sort_field=$col.sort}{$col.label|escape}{/self_link}
									{/if}
								{/if}
							{else}
								{if isset($col.translatelabel) && $col.translatelabel == 'y'}
									{$col.label|tra|escape}
								{else}
									{$col.label|escape}
								{/if}
							{/if}
						</th>
					{/foreach}
				</tr>
			{/if}
		</thead>
		<tbody>
			{foreach from=$results item=row}
				<tr>
					{foreach from=$column item=col}
						<td{if not empty($col.class)} class="{$col.class}"{/if}>
							{if isset($col.mode) && $col.mode eq 'raw'}
								{if !empty($row[$col.field])}{$row[$col.field]}{/if}
							{else}
								{if !empty($row[$col.field])}{$row[$col.field]|escape}{/if}
							{/if}
						</td>
					{/foreach}
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
