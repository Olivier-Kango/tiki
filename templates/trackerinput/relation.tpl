{if $data.relationshipTracker}
    <input type="hidden" name="{$field.ins_id}[meta]" value="{$data.meta|escape}">
    {if $data.relationshipBehaviour && $data.relationshipBehaviour->isMultiple()}
        {object_selector_multi _name=$field.ins_id|cat:'[objects]' _value=$data.existing _relations=$data.relations _relationshipTrackerId=$data.relationshipTrackerId _filter=$data.filter _format=$data.format _parent=$data.parent _parentkey=$data.parentkey}
    {else}
        {object_selector _name=$field.ins_id|cat:'[objects]' _value=$data.existing _relations=$data.relations _relationshipTrackerId=$data.relationshipTrackerId _filter=$data.filter _format=$data.format _parent=$data.parent _parentkey=$data.parentkey}
    {/if}
{else}
    {object_selector_multi _name=$field.ins_id|cat:'[objects]' _value=$data.existing _filter=$data.filter _format=$data.format _parent=$data.parent _parentkey=$data.parentkey}
{/if}
