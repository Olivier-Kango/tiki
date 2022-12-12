{ticket}
<div class="mb-3 row">
    <label class="col-form-label col-sm-3">
        {tr}Name{/tr}
    </label>
    <div class="col-sm-9">
        <input type="text" name="name" id="name" value="{$webhook.name|escape}" maxlength="200" class="form-control">
    </div>
</div>
<div class="mb-3 row">
    <label class="col-form-label col-sm-3">
        {tr}User{/tr}
        <a class="tikihelp text-info" title="{tr}User account:{/tr} {tr}Requests that successuflly verify with this webhook handler will execute as the chosen user here.{/tr}">
            {icon name=information}
        </a>
    </label>
    <div class="col-sm-9">
        {user_selector id="user_selector_webhook" realnames="n" user=$webhook.user}
    </div>
</div>
<div class="mb-3 row">
    <label class="col-form-label col-sm-3">
        {tr}Verification type{/tr}
        <a class="tikihelp text-info" title="{tr}Verification:{/tr} {tr}What type of verification should be applied on the incoming request?{/tr}">
            {icon name=information}
        </a>
    </label>
    <div class="col-sm-9">
        <select name="verification" id="verification" class="form-control">
            {foreach from=$verification_types item=type}
                <option value="{$type|escape}" {if $webhook.verification eq $type}selected{/if}>{$type|escape}</option>
            {/foreach}
        </select>
    </div>
</div>
<div class="mb-3 row">
    <label class="col-form-label col-sm-3">
        {tr}Algorithm{/tr}
        <a class="tikihelp text-info" title="{tr}Algorithm:{/tr} {tr}Which algorithm should be used during verification?{/tr}">
            {icon name=information}
        </a>
    </label>
    <div class="col-sm-9">
        <select name="algo" id="algo" class="form-control">
            {foreach from=$algos item=algo}
                <option value="{$algo|escape}" {if $webhook.algo eq $algo}selected{/if}>{$algo|escape}</option>
            {/foreach}
        </select>
    </div>
</div>
<div class="mb-3 row">
    <label class="col-form-label col-sm-3">
        {tr}Signature header{/tr}
        <a class="tikihelp text-info" title="{tr}Signature header:{/tr} {tr}Name of the HTTP header to retrieve the webhook signature from.{/tr}">
            {icon name=information}
        </a>
    </label>
    <div class="col-sm-9">
        <input type="text" name="signature_header" id="signature_header" value="{$webhook.signatureHeader|escape}" maxlength="100" class="form-control" placeholder="{tr}e.g. Webhook-Signature{/tr}">
    </div>
</div>
<div class="mb-3 row">
    <label class="col-form-label col-sm-3">
        {tr}Webhook secret{/tr}
        <a class="tikihelp text-info" title="{tr}Webhook secret:{/tr} {tr}Shared secret used to compute and verify the signature. Copy this from 3rd party source of the webhook.{/tr}">
            {icon name=information}
        </a>
    </label>
    <div class="col-sm-9">
        <textarea name="secret" id="secret" class="form-control" rows="5">{$webhook.secret|escape}</textarea>
    </div>
</div>
