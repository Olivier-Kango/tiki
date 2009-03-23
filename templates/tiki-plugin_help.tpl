<!-- START of {$smarty.template} --><h3>{$plugin.name|escape}
<a href="javascript:void(0)" onclick="needToConfirm=false;popup_plugin_form('{$plugin_name|lower|@addslashes}')">{tr}Insert{/tr}</a>
{if $plugin.documentation}
<a href="{$plugin.documentation|escape}">{tr}Documentation{/tr}</a>
{/if}
</h3>
<div class="plugin-desc">
{$plugin.description}
</div>
<div class="plugin-sample">
{if $plugin.body}
&#123;{$plugin_name}(
{foreach key=name item=param from=$plugin.params}
	<div class="plugin-param">
	{if $param.required}
		{$name}=<em>"{$param.description|escape}"</em>
	{else}
		[ {$name}=<em>"{$param.description|escape}"</em> ]
	{/if}
	</div>
{/foreach}
)&#125;
<div class="plugin-param">
{$plugin.body}
</div>
</div>
&#123;{$plugin_name}&#125;
{else}
&#123;{$plugin_name|@lower}
{foreach key=name item=param from=$plugin.params}
	<div class="plugin-param">
	{if $param.required}
		{$name}=<em>"{$param.description|escape}"</em>
	{else}
		[ {$name}=<em>"{$param.description|escape}"</em> ]
	{/if}
	</div>
{/foreach}
&#125;
</div>
{/if}<!-- END of {$smarty.template} -->
