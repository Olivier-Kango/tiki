{* $Id$ *}<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="page_{$page_id}"{/if}>
<head>
    {include file='header.tpl'}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {* The following style block makes sense to be used only with this fixed top bar layout so lets put it here only *}
    {if $prefs.theme_navbar_fixed_topbar_offset ne ''}<style type="text/css">
    {literal}
    /* Prevent target anchors from being covered by fixed top navbar */
    h1:target:before,
    h2:target:before,
    h3:target:before,
    h4:target:before,
    h5:target:before,
    h6:target:before {
        content: "";
        display: block;
        height: {/literal}{$prefs.theme_navbar_fixed_topbar_offset}{literal}px; /* fixed header height*/
        margin: -{/literal}{$prefs.theme_navbar_fixed_topbar_offset}{literal}px 0 0; /* negative fixed header height */
    }{/literal}
    </style>{/if}
</head>
<body{html_body_attributes class="navbar-padding"}{if $prefs.theme_navbar_fixed_topbar_offset ne ''} style="padding-top: {$prefs.theme_navbar_fixed_topbar_offset}px"{/if}>
    {$cookie_consent_html}

    {include file="layout_fullscreen_check.tpl"}

    {if $prefs.feature_ajax eq 'y'}
        {include file='tiki-ajax_header.tpl'}
    {/if}
    <div class="middle_outer" id="middle_outer">
        {if $smarty.session.fullscreen ne 'y'}
                {if $prefs.theme_unified_admin_backend eq 'y' && $smarty.server.SCRIPT_NAME eq $url_path|cat:'tiki-admin.php'}
                    {modulelist zone=top class="top_modules d-flex justify-content-between top navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent w-100 mb-sm"}
                {/if}
            <div class="fixed-topbar"></div>
        {/if}
        <div class="topbar-wrapper navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent">
            <div class="topbar container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std navbar-{$navbar_color_variant} bg-{$navbar_color_variant}" id="topbar">
                {modulelist zone=topbar class='topbar_modules d-flex justify-content-between w-100'}
            </div>
        </div>
        <div class="middle-wrapper">
        <div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std middle" id="middle">

            <div class="row row-middle" id="row-middle">
                {if (zone_is_empty('left') or $prefs.feature_left_column eq 'n') and (zone_is_empty('right') or $prefs.feature_right_column eq 'n')}
                    <div class="d-flex flex-row row flex-wrap w-100 gx-4">
                    <div class="col col1 col-md-12 pb-4" id="col1">
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop}
                        {/if}
                        {feedback}
                        {block name=quicknav}{/block}
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        {block name=content}{/block}
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3'}
                        {/if}
                    </div>
                </div>
                {elseif zone_is_empty('left') or $prefs.feature_left_column eq 'n'}
                    {if $prefs.feature_right_column eq 'user'}
                        <div class="col-md-12 side-col-toggle-container justify-content-end">
                            {$icon_name = (not empty($smarty.cookies.hide_zone_right)) ? 'toggle-left' : 'toggle-right'}
                            {icon name=$icon_name class='toggle_zone right btn btn-xs btn-info' href='#' title='{tr}Toggle right modules{/tr}'}
                        </div>
                    {/if}
                <div class="d-flex flex-row row flex-wrap w-100 gx-4">
                    <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} pb-4" id="col1">
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop}
                        {/if}
                        {feedback}
                        {block name=quicknav}{/block}
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        {block name=content}{/block}
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3'}
                        {/if}
                    </div>
                    <div class="col col3 col-md-12 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if}" id="col3">
                        {modulelist zone=right}
                    </div>
                </div>
                {elseif zone_is_empty('right') or $prefs.feature_right_column eq 'n'}
                    {if $prefs.feature_left_column eq 'user'}
                        <div class="col-md-12 side-col-toggle-container justify-content-start">
                            {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
                            {icon name=$icon_name class='toggle_zone left btn btn-xs btn-info' href='#' title='{tr}Toggle left modules{/tr}'}
                        </div>
                    {/if}
                <div class="d-flex flex-row row flex-wrap w-100 gx-4">
                    <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} order-md-1 order-lg-2 pb-4" id="col1">
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop}
                        {/if}
                        {feedback}
                        {block name=quicknav}{/block}
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        {block name=content}{/block}
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3'}
                        {/if}
                    </div>
                    <div class="col col2 col-md-12 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if} order-sm-2 order-md-2 order-lg-1" id="col2">
                        {modulelist zone=left}
                    </div>
                </div>
                {else}
                    <div class="col-sm-12 side-col-toggle-container d-flex py-1">
                        {if $prefs.feature_left_column eq 'user'}
                            <div class="text-start side-col-toggle flex-fill">
                                {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
                                {icon name=$icon_name class='toggle_zone left btn btn-xs btn-info' href='#' title='{tr}Toggle left modules{/tr}'}
                            </div>
                        {/if}
                        {if $prefs.feature_right_column eq 'user'}
                            <div class="text-end side-col-toggle flex-fill">
                                {$icon_name = (not empty($smarty.cookies.hide_zone_right)) ? 'toggle-left' : 'toggle-right'}
                                {icon name=$icon_name class='toggle_zone right btn btn-xs btn-info' href='#' title='{tr}Toggle right modules{/tr}'}
                            </div>
                        {/if}
                    </div>
                <div class="d-flex flex-row row flex-wrap w-100 gx-4">
                    <div class="col col1 col-sm-12 col-lg-8 order-xs-1 order-lg-2 pb-4" id="col1">
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop}
                        {/if}
                        {feedback}
                        {block name=quicknav}{/block}
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        {block name=content}{/block}
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3'}
                        {/if}
                    </div>
                    <div class="col col2 col-sm-6 col-lg-2 order-md-2 order-lg-1" id="col2">
                        {modulelist zone=left}
                    </div>
                    <div class="col col3 col-sm-6 col-lg-2 order-md-3" id="col3">
                        {modulelist zone=right}
                    </div>
                </div>
                {/if}

            </div> {* row *}
        </div> {* container *}
    </div> {* middle_outer *}
    </div>
    {if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
        <footer class="footer main-footer" id="footer">
            <div class="footer_liner">
                <div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std">

                    {modulelist zone=bottom class='bottom_modules p-3 mx-n2point5'} <!-- div.modules -->
                </div>
            </div>
        </footer>
        {if $prefs.theme_unified_admin_backend neq 'y' or $smarty.server.SCRIPT_NAME|strpos:'tiki-admin.php' === false}
            <header class="navbar navbar-expand-md navbar-{$navbar_color_variant} bg-{$navbar_color_variant} fixed-top">
                <div class="container-fluid">
                    {modulelist zone=top class="top_modules d-flex justify-content-between w-100 navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent"}
                </div>
            </header>
        {/if}
    {/if}

    {include file='footer.tpl'}
</body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
