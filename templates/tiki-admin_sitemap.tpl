{* $Id$ *}

{remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
    {tr}We centralize all the options that participate in the improvement of the SEO of the Tiki application. Next Tiki version will contain more options here. Go to Advanced -> SEO preferences to generate your sitemap or on <a href="tiki-admin.php?page=seoprefs" class="alert-link"> tiki-admin.php?page=seoprefs</a>{/tr}
{/remarksbox}
{title help="Sitemap" admpage="general&cookietab=3&highlight=sitemap_enable"}{tr}Sitemap{/tr}{/title}

{button href="tiki-admin_sitemap.php?rebuild=1" _icon_name="sitemap" class="btn btn-primary" _text="{tr}Rebuild sitemap{/tr}"}

<br/><h2>{tr}Submit the Sitemap{/tr}</h2>
{remarksbox type="info" title="{tr}Submit the Sitemap{/tr}" close="n"}
{if $sitemapAvailable}
    {tr}You can submit the sitemap for processing in all major search engines using the following URL:{/tr}
    <br>
    <br>
    <a href="{$url}" target="_blank">{$url}</a>
{else}
    {tr}The URL that you will need to use for submitting the sitemap will be available after you rebuild the sitemap.{/tr}
{/if}
{/remarksbox}
{remarksbox type="info" title="{tr}Automate Sitemap generation{/tr}" close="n"}
    <p>
        {tr}You can automate the sitemap generation by using the scheduler functionality:
            <a href="https://doc.tiki.org/Scheduler" class="alert-link">https://doc.tiki.org/Scheduler</a>
        {/tr}
    </p>
    <p>
        {tr}Or you can use directly the command line:{/tr} <code>php console.php sitemap:generate {$base_url}</code>
    </p>
{/remarksbox}
