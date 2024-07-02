{* Template for Plugin List to generate RSS feeds

Notes: Syntax likely to change and improve before being added as an official list template

Example wiki syntax

{LIST()}
  {filter type="trackeritem"}
  {filter field="tracker_id" exact="1"}
  {OUTPUT(template="templates/examples/search/rss.tpl")}
    {settings title="test rss" description="from plugin list" link="http://example.com" pubDate="{{lastModif}}"}
    {item label="title" field="title"}
    {item label="description" field="productsDescription"}
    {item label="pubDate" field="pubDate"}
    {item label="guid" field="guid"}
    {item label="link" field="guid"}
{OUTPUT}
  {FORMAT(name="pubDate")}{display name="date" format="datetime" dateFormat="%c"}{FORMAT}
  {FORMAT(name="guid")}http://example.com/product-{display  name="object_id"}{FORMAT}
  {FORMAT(name="productsDescription")}{display name="tracker_field_productsDescription" format="trackerrender"}
{display name="wikiplugin_img" format="wikiplugin" fileId="tracker_field_productsImages" max="200" lazyLoad="n" responsive="n" default="fileId=42"}
{FORMAT}
{LIST}
*}{strip}
    {if not empty($column.field)}
        {$column = [$column]}{* if there is only one column then it will not be in an array *}
    {/if}
    {if strpos($smarty.server.PHP_SELF, 'tiki-index_raw.php') !== false}
        {$lt='<'}
        {$gt='>'}
        {$lt='&lt;'}
        {$gt='&gt;'}
        {$br=''}
        {$tab=''}
    {else}
        {$lt='&lt;'}
        {$gt='&gt;'}
        {$br='<br>'}
        {$tab='&nbsp;&nbsp;&nbsp;&nbsp;'}
    {/if}
{/strip}{$lt}?xml version="1.0" encoding="UTF-8" ?{$gt}{$br}
{$lt}rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"{$gt}{$br}
{$lt}channel{$gt}{$br}
{*{debug}*}
{foreach $settings as $label => $value}
    {if $label eq 'pubDate' and $value|date_format:'r'}
        {$value=$value|date_format:'r'}
    {/if}
    {$tab}{$lt}{$label|escape}{$gt}{$value|escape}{$lt}/{$label|escape}{$gt}{$br}
{/foreach}
{foreach $results as $row}
    {$tab}{$lt}item{$gt}{$br}
    {$socials = ''}
    {foreach $item as $it}
        {if !empty($row[$it.field])}{strip}
            {$value = $row[$it.field]|nonp}
            {if $it.label eq 'socials'}
                {$socs = ','|explode:$value}
                {foreach $socs as $soc}
                    {if strpos($soc, '@none') === false}
                        {if strpos($soc, '://') !== false}
                            {$soc = $soc|replace:'@':''}
                        {/if}
                        {if $socials}{$socials = $socials|cat:', '}{/if}
                        {$socials = $socials|cat:$soc}
                    {/if}
                {/foreach}
                {if $socials}{$socials = "<br>\n"|cat:$socials|cat:"<br>\n"}{/if}
                {continue}
            {elseif $it.label eq 'description'}
                {$value = "<![CDATA[`$value`]]>"}
                {$value = $value|replace:"\n":''|replace:"\r":''}
                {$value = $value|replace:"%socials%":$socials}
            {/if}
            {$tab}{$tab}{$lt}{$it.label|escape}{$gt}{$value|escape}{$lt}/{$it.label|escape}{$gt}
        {/strip}{$br}
    {/if}{/foreach}
    {$tab}{$lt}/item{$gt}{$br}
{/foreach}
{$lt}/channel{$gt}{$br}
{$lt}/rss{$gt}{$br}
