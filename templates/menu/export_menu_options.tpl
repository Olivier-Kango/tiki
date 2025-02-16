{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <form action="{service controller=menu action=export_menu_options menuId=$menuId}" method="post" class="no-ajax form">
        {remarksbox type="tip" title="{tr}Export CSV{/tr}" close="n"}
            {tr}Menu:{/tr} {$menuInfo.name|escape} ({tr}Id:{/tr} {$menuInfo.menuId|escape})
            {if $menuSymbol}
                <span class="form-text">
                    {tr}Symbol:{/tr}{$menuSymbol.object} ({tr}Profile Name:{/tr}{$menuSymbol.profile}, {tr}Profile Source:{/tr}{$menuSymbol.domain})
                </span>
            {/if}
        {/remarksbox}
        {* hiding enconding as it does not seem to work - this template is therefore not being used currently *}
        <div class="mb-3 hidden" >
            <label for="encoding" class="col-form-label">
                {tr}Encoding{/tr}
            </label>
            <select name="encoding" class="form-control">
                <option value="UTF-8">UTF-8</option>
                <option value="ISO-8859-1">ISO-8859-1</option>
            </select>
        </div>
        <div class="submit">
            {ticket mode=confirm}
            <button type="submit" class="btn btn-primary" name="export">{tr}Export{/tr}</button>
        </div>
    </form>
{/block}
