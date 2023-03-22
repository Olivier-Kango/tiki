<div class="adminoptionbox preference mb-3 row d-flex {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
    <label class="col-form-label col-sm-3" for="{$p.id|escape}">{$p.name|escape}
        {include file="prefs/shared-help-icon.tpl"}
    </label>
    <div class="col">
        {object_selector_multi _simplename=$p.preference _simpleid=$p.id _simplevalue=$p.value _separator=$p.separator type=$p.selector_type _format=$p.format|default:null}
        {include file="prefs/shared-form-text.tpl"}
    </div>
    <div class="tikihelp-reset-wrapper col-sm-2">
        {include file="prefs/shared.tpl"}
    </div>
</div>
