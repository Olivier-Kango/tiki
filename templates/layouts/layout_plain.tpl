<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="page_{$page_id}"{/if}>
    <head>
        {include file='header.tpl'}
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body{html_body_attributes}>
        {$cookie_consent_html}

        {include file="layout_fullscreen_check.tpl"}

        {if $prefs.feature_ajax eq 'y'}
            {include file='tiki-ajax_header.tpl'}
        {/if}

        <div class="container{if isset($smarty.session.fullscreen) && $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std middle" id="middle">
{if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
            <div class="row">
                <header class="page-header w-100" id="page-header">
                    {modulelist zone=top class='top_modules d-flex justify-content-between'}
                </header>
            </div>
{/if}
            <div class="row">
                <div class="col-md-12">
                    {modulelist zone=topbar}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12" id="col1">
                    {block name=title}{/block}
                    {block name=navigation}{/block}
                    {feedback}
                    {block name=content}{/block}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 well">
                    {modulelist zone=bottom}
                </div>
            </div>
        </div>

        {include file='footer.tpl'}
    </body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
