<ol>
    {foreach from=$data.videos item=video}
        {assign var=vid value=$video.shortUUID|default:$video.uuid|default:$video.id}
        {assign var=cid value="`$field.ins_id|escape`_`$vid|escape`"}
        <li>
            <input
                type="checkbox"
                class="form-check-input"
                name="{$field.ins_id|escape}[]"
                id="{$cid}"
                value="{$vid|escape}"
                checked="checked">
            <input type="hidden" name="old_{$field.ins_id|escape}[]" value="{$vid|escape}">
            <label for="{$cid}" class="form-check-label">
                {$video.name|default:$video.title|escape}
            </label>
        </li>
    {/foreach}
</ol>

<a class="add-peertube-media btn btn-primary btn-sm"
   href="{service controller=peertube action=upload}"
   data-target-name="{$field.ins_id|escape}[]">{tr}Add Media{/tr}</a>

<a class="list-peertube-media btn btn-primary btn-sm"
   href="{service controller=peertube action=list targetName=$field.ins_id|escape|cat:'[]'}"
   data-target-name="{$field.ins_id|escape}[]">{tr}List Media{/tr}</a>

{foreach from=$data.extras item=entryId}
    <input type="hidden" name="{$field.ins_id|escape}[]" value="{$entryId|escape}">
{/foreach}
{if $data.extras|count}
    <span class="highlight">+{$data.extras|count}</span>
{/if}

{jq}
    $('.list-peertube-media').on('click', function (e) {
        var link = this;
        if ($.fn.serviceDialog) {
            e.preventDefault();
            var $bsModal = $("#bootstrap-modal").hide();
            $(link).serviceDialog({
                title: $(link).text(),
                width: 710,
                height: 450,
                hideButtons: true,
                data: {
                    targetName: $(link).data('target-name'),
                    formId: $(link).closest('form').attr('id') || ''
                },
                close: function () { $bsModal.show(); }
            });
        }
    });
{/jq}
