{* The 3 elements below are displayed with simple parsing (parse_data_simple()), which is probably better than using parse_data(), for performance and to obtain a more predictable parsing.
Converting these elements to HTML may still be better. Chealer -- Moved from shared.tpl to display under input. g-c-l *}
{if !empty($p.shorthint)}
    <div class="form-text form-text-test">{$p.shorthint|parse:true}</div>
{/if}
{if !empty($p.detail)}
    <div class="form-text  form-text-test">{$p.detail|parse:true}</div>
{/if}
{if !empty($p.hint)}
    <div class="form-text form-text-test">{$p.hint|parse:true}</div>
{/if}
