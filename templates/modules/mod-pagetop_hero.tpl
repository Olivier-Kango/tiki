{tikimodule error=$module_error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
<div id="pagetop-hero" class="pagetop-hero w-100 p-4">
    <div class="bg-image-wrapper" id="bg-image-wrapper" {if $bgimage neq ''}style="background-image: url('{$bgimage}')"{/if}></div>
    <div class="row">
        <div class="content">
            <h1 class="pagetop-hero-title">{tr}{$pagetitle|escape}{/tr}</h1>
            {if count($breadcrumbs) gt 0}
                <div class="breadcrumbs">
                    {foreach from=$breadcrumbs item=item name=object }
                        {if $smarty.foreach.object.last}
                            <span>{tr}{$item}{/tr}</span>
                        {else}
                            <b>{tr}{$item}{/tr}</b> /
                        {/if}
                    {/foreach}
                </div>
            {else}
                {if $description neq ''}
                    <p class="pagetop-hero-description">{tr}{$description|escape}{/tr}</p>
                {/if}
            {/if}
        </div>
    </div>
</div>
{/tikimodule}

<style>
    /* Pagetop Hero */
    .pagetop-hero {
        -webkit-box-shadow: 0px 5px 16px 2px rgba(68, 68, 68, 0.58);
        box-shadow: 0px 5px 16px 2px rgba(68, 68, 68, 0.58);
        min-height: 300px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pagetop-hero-title,
    .breadcrumbs,
    .pagetop-hero-description {
        text-align: center
    }


    .pagetop-hero .bg-image-wrapper {
        min-height: 300px;
        max-width: 100%;
        overflow: hidden;
    }

    .pagetop-hero .row {
        z-index: 1;
        width: 100% !important;
    }

    .pagetop-hero .row .pagetop-hero-title {
        flex-wrap: wrap !important;
        color: #fff;
        width: 100%;
    }

    .bg-image-wrapper {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 0;
        background-position: center center;
        background-size: cover;
        background-color: rgb(60, 60, 60);
        filter: brightness(65%);
        -webkit-filter: brightness(65%);
        -moz-filter: brightness(65%);
    }

    .breadcrumbs,
    .breadcrumbs a,
    .breadcrumbs span {
        color: #fff !important;
    }
</style>

{if $content_position eq 'topleft'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: left
        }

        .pagetop-hero {
            align-items: flex-start;
        }
    </style>
{/if}

{if $content_position eq 'leftcenter'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: left
        }

        .pagetop-hero {
            align-items: center;
        }
    </style>
{/if}

{if $content_position eq 'topcenter'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: center
        }

        .pagetop-hero {
            align-items: flex-start;
        }
    </style>
{/if}

{if $content_position eq 'topright'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: right
        }

        .pagetop-hero {
            align-items: flex-start;
        }
    </style>
{/if}

{if $content_position eq 'bottomleft'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: left
        }

        .pagetop-hero {
            align-items: flex-end;
        }
    </style>
{/if}

{if $content_position eq 'bottomcenter'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: center
        }

        .pagetop-hero {
            align-items: flex-end;
        }
    </style>
{/if}

{if $content_position eq 'bottomright'}
    <style>
        .pagetop-hero-title,
        .breadcrumbs,
        .pagetop-hero-description {
            text-align: right
        }

        .pagetop-hero {
            align-items: flex-end;
        }
    </style>
{/if}
