{if $prefs.user_register_prettytracker eq 'y' and $prefs.user_register_prettytracker_tpl and $prefs.socialnetworks_user_firstlogin != 'y'}
    {if isset($theChoiceGroup)}
        <input type="hidden" name="chosenGroup" value="{$theChoiceGroup|escape}">
    {elseif isset($listgroups)}
        {foreach item=gr from=$listgroups}
            {if $gr.registrationChoice eq 'y'}
                <div class="registergroup">
                    <input type="radio" name="chosenGroup" id="gr_{$gr.id}" value="{$gr.groupName|escape}">
                    <label for="gr_{$gr.id}">
                        {if !empty($gr.groupDesc)}
                            {tr}{$gr.groupDesc|escape}{/tr}
                        {else}
                            {$gr.groupName|escape}
                        {/if}
                    </label>
                </div>
            {/if}
        {/foreach}
    {/if}
{else}
    {* Groups *}
    {if isset($theChoiceGroup)}
        <input type="hidden" name="chosenGroup" value="{$theChoiceGroup|escape}">
        {jq}
$.getJSON('group_tracker_ajax.php', {chosenGroup:'{{$theChoiceGroup}}'}, function(data) {
    $("#registerTracker").html(data['res']).tikiModal();
});
        {/jq}
        <div id="registerTracker"></div>
    {elseif isset($listgroups)}
        <div class="mb-3 row">
            <label class="col-sm-4 col-form-label">
                {tr}Group{/tr}{if $prefs.user_must_choose_group eq 'y'} {if $trackerEditFormId}<strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}{/if}
            </label>
            <div class="col-sm-8">
                {foreach item=gr from=$listgroups}
                    {if $gr.registrationChoice eq 'y'}
                        <div class="registergroup">
                            <input type="radio" name="chosenGroup" id="gr_{$gr.id}" value="{$gr.groupName|escape}"
                                    {if !empty($smarty.post.chosenGroup) and $smarty.post.chosenGroup eq $gr.groupName|escape}checked="checked"{/if}>
                            <label for="gr_{$gr.id}">
                                {if !empty($gr.groupDesc)}
                                    {tr}{$gr.groupDesc|escape}{/tr}
                                {else}
                                    {$gr.groupName|escape}
                                {/if}
                            </label>
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
        <div id="registerTracker" class="col-sm-8 offset-sm-4">
            {if $trackerEditFormId}
                <div class="col-sm-9 offset-sm-3">
                    <div class="text-center alert alert-danger">
                        <em class='mandatory_note'>{tr}Fields marked with an * are mandatory.{/tr}</em>
                    </div>
                </div>
            {/if}
        </div>
        {jq}
$("input[name='chosenGroup']").on("change", function() {
    $("#registerTracker").tikiModal("{tr}Loading...{/tr}");
    var gr = $("input[name='chosenGroup']:checked").val();
    $.getJSON('group_tracker_ajax.php',{chosenGroup:gr}, function(data) {
        if ($("#registerTracker").children().length === 0) {
            $(".trackerplugindesc").parents("tr").remove();
        }
        $("#registerTracker").html(data['res']).tikiModal();
        $("input[name^=captcha]").parents("tr").show();
        $("input[name=register]").prop("disabled", false);
        $("#registerTracker").parents("table").first().css({borderSpacing:"0 !important",borderCollapse:"collapse !important"});
        $("tr td", "#registerTracker").first().width($("#registerTracker").parents('table').first().find("td").first().width());
    });
}){{if !empty($smarty.post.chosenGroup)}}.trigger("change"){{/if}};
{{if $prefs.user_must_choose_group eq 'y'}
$("input[name^=captcha]").parents("tr").hide();
$("input[name=register]").prop("disabled", true);
{/if}}
        {/jq}
    {elseif $prefs.userTracker eq 'y'}
        <input type="hidden" name="chosenGroup" value="Registered">
    {/if}
{/if}
