{if $prefs.theme_unified_admin_backend eq 'y'}
    <nav class="navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-admin-aside-nav-{$prefs.theme_navbar_color_variant_admin} d-flex align-items-start flex-column{if not empty($smarty.cookies.sidebar_collapsed)} narrow{/if}" role="navigation">
        <div class="was-accordion accordion-flush w-100 border-end" id="admin-menu-accordion">
            <div class="accordion-item pb-2">
                <form method="post" class="d-flex justify-content-center my-md-0 ms-auto" role="form">
                    <div class="my-1 mx-4">
                        <input type="hidden" name="filters">
                        <div class="input-group">
                            <input type="text" name="lm_criteria" value="{$lm_criteria|escape}" class="form-control form-control-sm" placeholder="{tr}Search preferences{/tr}...">
                            <button type="submit" class="btn btn-primary btn-sm"{if $indexNeedsRebuilding} class="tips" title="{tr}Configuration search{/tr}|{tr}Note: The search index needs rebuilding, this will take a few minutes.{/tr}"{/if}>{icon name="search"}</button>
                        </div>
                    </div>
                </form>
            </div>
            {if not empty($smarty.request.page)}
                <div class="nav-item was-accordion-item tips right" title="{tr}Control Panels{/tr}|{tr}Go back to or reload the Control Panels / Administration Dashboard{/tr}" {* style="padding: var(--bs-accordion-btn-padding-y) var(--bs-accordion-btn-padding-x);" *}>
                    <div class="accordion-header">
                    <a href="tiki-admin.php" class="nav-link px-4 py-2 fw-semibold">
                        {icon name='home' iclass='fa-fw'}
                        <span class="narrow-hide">{tr}Admin Dashboard{/tr}</span>
                    </a>
                    </div>
                </div>
            {/if}
            {foreach $admin_icons as $section => $secInfo}
                <div class="nav-item was-accordion-item tips right" title="{$secInfo.title}|{$secInfo.description}">
                    <div class="accordion-header" id="flush-heading-{$section}">
                        <a class="nav-link was-accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-{$section}" aria-expanded="false" aria-controls="flush-collapse-{$section}">
                            {icon name=$secInfo.icon iclass='fa-fw'}
                                    <span class="ms-1 narrow-hide">{$secInfo.title}</span>
                        </a>
                    </div>
                    <div id="flush-collapse-{$section}" class="accordion-collapse collapse" aria-labelledby="flush-heading-{$section}" data-bs-parent="#admin-menu-accordion">
                        <div class="accordion-body p-0">
                           <div class="dropdown-menu show position-relative {* {if $prefs.theme_navbar_color_variant_admin eq 'dark'}dropdown-menu-dark{/if} *}border-0 rounded-0">
                            {foreach $secInfo.children as $page => $info}
                                <a href="{if not empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}"
                                        class="tips right icon dropdown-item{if !empty($info.selected)} active{/if}{if !empty($info.disabled)} item-disabled text-muted{/if}"
                                        data-alt="{$info.title} {$info.description}" title="{$info.title}|{$info.description}">
                                    {icon name="admin_$page" iclass='fa-fw'}
                                    <span class="ms-1">{$info.title}</span>
                                </a>
                            {/foreach}
                        </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
        <div class="admin-menu-collapser navbar-{$navbar_color_variant} bg-{$navbar_color_variant}">
            {if not empty($smarty.cookies.sidebar_collapsed)}
                {icon name='angle-double-right' class="nav-link float-end pt-1 pe-4" title='{tr}Collapse/expand this sidebar{/tr}'}
            {else}
                {icon name='angle-double-left' class="nav-link float-end pt-1 pe-4" title='{tr}Collapse/expand this sidebar{/tr}'}
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
