<form action="{$smarty.server.SCRIPT_NAME}?{query}" method="post">
    <input type="hidden" name="{$wp_addrelation_id|escape}" value="{$wp_addrelation_action|escape}">
    {if $wp_addrelation_action eq 'y'}
        <input type="submit" class="{$button_class|escape}" value="{$label_add|escape}">
    {elseif $wp_addrelation_action eq 'n'}
        <input id="wp_addrelation_added_{$wp_addrelation_id|escape}" type="submit" class="{$button_class|escape}" value="{$label_added|escape}">
    {/if}
</form>
{jq}
    $('#wp_addrelation_added_{{$wp_addrelation_id|escape}}').on("mouseenter", function() {
        $(this).val('{{$label_remove|escape}}');
    }).on("mouseleave",
    function() {
        $(this).val('{{$label_added|escape}}');
    });
{/jq}
