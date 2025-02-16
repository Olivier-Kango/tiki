{strip}
{* Simple remarks box used by Smarty entity block.remarksbox.php & wikiplugin_remarksbox.php *}
<div {if $remarksbox_id}id="{$remarksbox_id|escape}"{/if} class="alert {$remarksbox_class|escape}{if $remarksbox_close} alert-dismissible{/if}{if $remarksbox_highlight} {$remarksbox_highlight}{/if}{if $remarksbox_hidden} d-none{/if}">
    {if $remarksbox_close}
        <button type="button" id="btn-close" class="btn btn-sm bg-transparent position-absolute top-0 end-0" data-bs-dismiss="alert" aria-label="{tr}Close{/tr}">{icon name="close"}</button>
    {/if}
    {if !empty($remarksbox_title)}
        <{$remarksbox_title_tag} class="{$remarksbox_title_class}">
            {if not empty($remarksbox_icon)}{icon name=$remarksbox_icon}&nbsp;{/if}

            <span class="rboxtitle">{tr}{$remarksbox_title|escape}{/tr}</span>
        </{$remarksbox_title_tag}>
    {else}
        {if not empty($remarksbox_icon)}{icon name=$remarksbox_icon}{/if}
    {/if}
    <div class="rboxcontent" style="display: inline">{$remarksbox_content}</div>
</div>
{/strip}

{if $remarksbox_cookie}
{jq}
$("button#btn-close", "#{{$remarksbox_id|escape}}").on("click", function() {
    setCookie("{{$remarksbox_cookiehash}}", "1", "rbox");
});
{/jq}
{/if}
