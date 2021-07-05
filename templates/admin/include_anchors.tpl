{if $prefs.theme_unified_admin_backend eq 'y'}
	<nav class="{if $prefs.theme_navbar_color_variant eq 'dark'}navbar-dark bg-dark{else}navbar-light bg-light{/if}
	 		d-flex align-items-start flex-column{if not empty($smarty.cookies.sidebar_collapsed)} narrow{/if}" role="navigation">
		<ul class="nav navbar-nav mb-auto" id="admin-menu">
			<li class="nav-item">
				<form method="post" class="form-inline my-2 my-md-0 ml-auto" role="form">
					<div class="form-group row mx-0">
						<input type="hidden" name="filters">
						<div class="input-group">
							<input type="text" name="lm_criteria" value="{$lm_criteria|escape}" class="form-control form-control-sm" placeholder="{tr}Search preferences{/tr}...">
							<div class="input-group-append">
								<button type="submit" class="btn btn-primary btn-sm"{if $indexNeedsRebuilding} class="tips" title="{tr}Configuration search{/tr}|{tr}Note: The search index needs rebuilding, this will take a few minutes.{/tr}"{/if}>{icon name="search"}</button>
							</div>
						</div>
					</div>
				</form>
			</li>
			<li class="nav-item sections-header mt-2"><a href="tiki-admin.php" class="tips right nav-link" title="{tr}Control Panels Dashboard{/tr}|{tr}Go back to or reload the Control Panels / Administration Dashboard{/tr}">{icon name='home' iclass='fa-fw'}<span>{tr}Control Panels{/tr}</span></a></li>
			{foreach $admin_icons as $section => $secInfo}
				<li class="nav-item">
					<a href="#" class="tips right nav-link icon collapse-toggle" data-toggle="collapse" data-target="#collapse{$section}"
							title="{$secInfo.title}|{$secInfo.description}">
						{icon name=$secInfo.icon iclass='fa-fw'}
						<span>{$secInfo.title}</span>
					</a>
					<div class="collapse {if not empty($secInfo.selected)}show{/if}" id="collapse{$section}" data-parent="#admin-menu">
						{foreach $secInfo.children as $page => $info}

								<a href="{if not empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}"
										class="tips right icon dropdown-item{if !empty($info.selected)} active{/if}{if $info.disabled} item-disabled text-muted{/if}"
										data-alt="{$info.title} {$info.description}" title="{$info.title}|{$info.description}">
									{icon name="admin_$page" iclass='fa-fw'}
									<span>{$info.title}</span>
								</a>

						{/foreach}
					</div>
				</li>
			{/foreach}
		</ul>
		<div class="admin-menu-collapser">
			{if not empty($smarty.cookies.sidebar_collapsed)}
				{icon name='angle-double-right' title='{tr}Collapse/expand this sidebar{/tr}'}
			{else}
				{icon name='angle-double-left' title='{tr}Collapse/expand this sidebar{/tr}'}
			{/if}
		</div>
	</nav>
{else}
	{foreach from=$admin_icons key=page item=info}
		{if ! $info.disabled}
			<li>
				<a href="{if !empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}"
						data-alt="{$info.title} {$info.description}" class="tips bottom slow icon nav-link" title="{$info.title}|{$info.description}">
					{icon name="admin_$page"}
					{$info.title}
				</a>
			</li>
		{/if}
	{/foreach}
{/if}
