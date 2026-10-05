<fieldset class="mb-3">
    <legend>{tr}SEO meta tag overrides{/tr}</legend>
    <p class="form-text">{tr}Leave a field empty to use category metadata or the usual site defaults. Translated wiki pages have their own values.{/tr}</p>
    {foreach from=$seo_metatags key=field item=value}
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="seo_{$field|escape}">{if $field eq 'description'}{tr}Meta description{/tr}{elseif $field eq 'keywords'}{tr}Meta keywords{/tr}{else}{tr}Meta robots{/tr}{/if}</label>
            <div class="col-sm-9"><input type="text" class="form-control" id="seo_{$field|escape}" name="seo_metatags[{$field|escape}]" value="{$value|escape}"></div>
        </div>
    {/foreach}
</fieldset>
