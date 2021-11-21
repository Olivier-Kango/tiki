{if $row.tracker_field_productInventoryTotal|nonp gt 0}
    {if $row.use_minicart|nonp}
        {wikiplugin _name='paypal' button_type="_cart" cart_action="add" item_name=$row.title amount=$row.tracker_field_productPrice paypal_button="small_button" item_number=$row.object_id}{/wikiplugin}
    {else}
        {wikiplugin _name='addtocart' code=$row.object_id description=$row.title|nonp price=$row.tracker_field_productPrice ajaxaddtocart='y' href=$link weight=$row.tracker_field_productWeight}{/wikiplugin}
    {/if}
{/if}
