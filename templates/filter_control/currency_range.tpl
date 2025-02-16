<div class="d-flex flex-wrap mx-0 align-items-center">
  <div class="col-auto"> {* Prevent input from overflowing in narrow screens *}
    <input type="number" class="currency_number numeric form-control" name="{$control.field|escape}_from"
  {if !empty($control.meta.size)}size="{$control.meta.size|escape}" maxlength="{$control.meta.size|escape}"{/if}
  value="{$control.from|escape}" id="{$control.field|escape}_from" step="0.01">
  </div>
  <div class="col-auto">
    {if !empty($control.meta.currencies)}
      <select name="{$control.field|escape}_from_currency" id="{$control.field|escape}_from_currency" class="currency_code form-select">
      <option value=""></option>
        {foreach from=$control.meta.currencies item=c}
          <option value="{$c}" {if $c eq $control.fromCurrency}selected{/if}>{$c}</option>
        {/foreach}
      </select>
    {/if}

    {if !empty($control.meta.error)}
      {$control.meta.error}
    {/if}
  </div>
</div>
<div class="d-flex flex-wrap mx-0 align-items-center">
  <div class="col-auto"> {* Prevent input from overflowing in narrow screens *}
    <input type="number" class="currency_number numeric form-control" name="{$control.field|escape}_to"
  {if !empty($control.meta.size)}size="{$control.meta.size|escape}" maxlength="{$control.meta.size|escape}"{/if}
  value="{$control.to|escape}" id="{$control.field|escape}_to" step="0.01">
  </div>
  <div class="col-auto">
    {if !empty($control.meta.currencies)}
      <select name="{$control.field|escape}_to_currency" id="{$control.field|escape}_to_currency" class="currency_code form-select">
      <option value=""></option>
        {foreach from=$control.meta.currencies item=c}
          <option value="{$c}" {if $c eq $control.toCurrency}selected{/if}>{$c}</option>
        {/foreach}
      </select>
    {/if}

    {if !empty($control.meta.error)}
      {$control.meta.error}
    {/if}
  </div>
</div>
