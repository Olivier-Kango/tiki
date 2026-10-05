{foreach from=$seo_category_languages item=language}
    <details class="mb-3">
        <summary>{$language.name|escape} ({$language.code|escape})</summary>
        <p class="form-text">{tr}Leave empty to use the category default for this field.{/tr}</p>
        {foreach from=$language.values key=field item=value}
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="seo_category_{$language.code|escape}_{$field|escape}">{if $field eq 'description'}{tr}Meta description{/tr}{elseif $field eq 'keywords'}{tr}Meta keywords{/tr}{else}{tr}Meta robots{/tr}{/if}</label>
                <div class="col-sm-9"><input type="text" class="form-control" id="seo_category_{$language.code|escape}_{$field|escape}" name="seo_category_languages[{$language.code|escape}][{$field|escape}]" value="{$value|escape}"></div>
            </div>
        {/foreach}
    </details>
{/foreach}
