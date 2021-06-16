{if not empty($item.children)}
	<li class="nav-item dropdown{if $item.selected|default:null} active{/if} {$item.class|escape}">
		<a href="{$item.sefurl|escape}" class="{if $sub|default:false}dropdown-item{else}nav-link{/if} dropdown-toggle" data-toggle="dropdown">{tr}{$item.name}{/tr}</a>
		<ul class="dropdown-menu">
			{* {if $sub}
				<li class="dropdown-header">{tr}{$item.name}{/tr}</li>
				<li class="dropdown-divider"></li>
			{/if} *}
			{foreach from=$item.children item=sub}
				{include file='bootstrap_smartmenu_children.tpl' item=$sub sub=true}
			{/foreach}
		</ul>
	</li>
{else}
	<li class="nav-item {$item.class|escape}{if $item.selected|default:null} active{/if}">
		{if $item.block}
			{* mega-menu class prevents error (TypeError: Cannot read property 'parentNode' of null - jquery.smartmenus.js:line 664) when block items contains <ul> elements  *}
			<ul class="mega-menu block--container">
				{tr}{$item.name}{/tr}
			</ul>
		{else}
			<a class="{if $sub|default:false}dropdown-item{else}nav-link{/if}" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a>
		{/if}
	</li>
{/if}
