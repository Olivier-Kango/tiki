{* $Id$ *}
<form method="post" action="{query _type=relative _keepall=y}" style="display: inline;" class="wp_addtocart_form"{$form_data}>
    <div class="form-row align-items-center">
        <input type="hidden" name="code" value="{$params.code|escape}">
        {if $onbehalf == 'y'}
            <div class="col-12">
                {tr}Buy on behalf of:{/tr}
                <select name="buyonbehalf">
                    <option value="">{tr}None{/tr}</option>
                    {foreach key=id item=one from=$cartuserlist}
                        <option value="{$one|escape}">{$one|escape}</option>
                    {/foreach}
                </select>
            </div>
        {/if}

        {if $hideamountfield eq 'y'}
            <div class="col-12">
                <table>
                    {if $hideamountfield eq 'y'}
                        <input type="hidden" name="quantity" value="1">
                    {else}
                        <tr>
                            <th style="text-align: right;">{tr}Qty:{/tr}</th>
                            <td><input type="text" name="quantity" value="1" size="2"></td>
                        </tr>
                    {/if}
                </table>
            </div>
        {else}
            {if $params.hidequantity eq 'y'}
                <input type="hidden" name="quantity" value="1">
            {else}
                <div class="col-auto">
                    <input type="number" name="quantity" value="1" class="form-control form-control-sm tips" title="{tr}|Quantity{/tr}" style="width:3rem">
                </div>
            {/if}
        {/if}

        <div class="col-auto">
            <input type="submit" class="btn btn-secondary btn-sm" value="{tr}{$params.label|escape}{/tr}">
        </div>

        {if $params.exchangeorderitemid}
            <input type="hidden" value="{$params.exchangeorderitemid|escape}" name="exchangeorderitemid">
            <input type="hidden" value="{$params.exchangetoproductid|escape}" name="exchangetoproductid">
        {/if}
        {if not empty($params.weight)}
            <input type="hidden" value="{$params.weight|escape}" name="weight">
        {/if}
    </div>
</form>

