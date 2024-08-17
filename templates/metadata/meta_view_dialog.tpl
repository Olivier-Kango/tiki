<div id="{$id|escape}" title="{tr}Image Metadata for{/tr} {$filename|escape}" style="display:none">
    {if $type eq 'data'}
        {if isset($metarray.basiconly) and $metarray.basiconly}
            <span>
                <em>{tr}Note: only basic metadata processed for this file type{/tr}</em>
            </span>
        {/if}
        <ul>
            {foreach $metarray as $subtypes}
                {if $subtypes@key ne 'basiconly'}
                    <li>
                        <a href="#tabs-{$subtypes@iteration}">
                            {if $subtypes@key|count_words gt 1}
                                {tr}{$subtypes@key|escape}{/tr}
                            {else}
                                {$subtypes@key|upper|escape}
                            {/if}
                        </a>
                    </li>
                {/if}
            {/foreach}
        </ul>
        {foreach $metarray as $subtypes}
            <table id="tabs-{$subtypes@iteration}">
                {foreach $subtypes as $fields}
                    {if $fields|count gt 0 and $subtypes@key ne 'basiconly'}
                        <tr>
                            <td colspan="2">
                                <div class="meta-section">
                                    {tr}{$fields@key|lower|capitalize|escape}{/tr}
                                </div>
                            </td>
                        </tr>
                        {foreach $fields as $fieldarray}
                            <tr>
                                <td>
                                    <div class="meta-col1">
                                        {if isset($fieldarray.label) && $fieldarray.label ne 'li'}
                                            {tr}{$fieldarray.label|escape}{/tr}
                                        {else}
                                            {tr}{$fieldarray@key|escape}{/tr}
                                        {/if}
                                    </div>
                                </td>
                                <td>
                                    <div class="meta-col2">
                                        {$fieldarray.newval|escape}
                                        {if isset($fieldarray.suffix)}
                                            {if !empty($fieldarray.newval)}
                                                &nbsp;
                                            {/if}
                                            {tr}{$fieldarray.suffix|escape}{/tr}
                                        {/if}
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                {/foreach}
            </table>
        {/foreach}
    {else}
        {tr}No metadata found{/tr}
    {/if}
</div>

{jq}
    $("#{{$id_link}}").on("click", function() {
        $.openModal({
            title: $("#{{$id}}").attr("title"),
            content: $("#{{$id}}").html(),
            dialogVariants: ["scrollable", "center"],
            open: function() {
                $('.modal-body', this).tabs({
                    heightStyle: "content",
                    collapsible: true
                });
            }
        });
        return false;
    });
{/jq}
