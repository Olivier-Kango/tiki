{strip}
    <div class="adminoptionbox preference mb-3 row clearfix {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}" style="text-align: left;">
        <label class="col-form-label col-sm-4" for="{$p.id|escape}">{$p.name|escape}</label>
        <div class="col-sm-8">
            <textarea name="{$p.preference|escape}" id="{$p.id|escape}" {if $syntax} data-syntax="{$syntax|escape}" data-codemirror="{$codemirror|escape}" {/if} class="form-control" {if !empty($p.size)} rows="{$p.size|escape}"{/if} {$p.params}>
                {$p.value|escape}
            </textarea>
            {include file="prefs/shared.tpl"}
        </div>
    </div>
{/strip}
