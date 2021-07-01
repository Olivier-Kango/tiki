{strip}
	{if $toc_type eq 'admin'}
		<div class='col-sm-12'>
			<ol class="admintoc depth-{$cur_depth}" data-params='{$json_params}'>
	{else}
		<div class='col-sm-12'>
			<ul class="toc">
	{/if}
{/strip}