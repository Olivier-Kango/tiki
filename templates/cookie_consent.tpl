{* $Id$ *}
{strip}
	<div id="{$prefs.cookie_consent_dom_id}"
		{if $prefs.javascript_enabled eq 'y' and not empty($prefs.cookie_consent_mode)}
			style="display:none;" class="{$prefs.cookie_consent_mode}"
		{/if}
	>
		<form method="POST">
			<div class="description mb-1">
				{wiki}{tr}{$prefs.cookie_consent_description}{/tr}{/wiki}
			</div>
			{if !empty($prefs.cookie_consent_question)}
			<div class="form-check mb-1">
				<input class="form-check-input" type="checkbox" name="cookie_consent_checkbox" id="cookie_consent_checkbox">
				<label class="form-check-label question" for="cookie_consent_checkbox">
					{wiki}{tr}{$prefs.cookie_consent_question}{/tr}{/wiki}
				</label>
			</div>
			{else}
				<input type="hidden" name="cookie_consent_checkbox" value="1">
			{/if}
			<input type="submit" class="btn btn-primary" id="cookie_consent_button" name="cookie_consent_button" value="{tr}{$prefs.cookie_consent_button}{/tr}">
		</form>
	</div>
	{jq}
		$("#cookie_consent_button").click(function(){
			if ($("input[name=cookie_consent_checkbox]:checked").length || $("input[name=cookie_consent_checkbox]:hidden").val()) {
				var exp = new Date();
				exp.setTime(exp.getTime()+(24*60*60*1000*{{$prefs.cookie_consent_expires}}));
				jqueryTiki.no_cookie = false;
				setCookie("{{$prefs.cookie_consent_name}}", "y", "", exp);
				$(document).trigger("cookies.consent.agree");
			}
			$container = $("#cookie_consent_div").parents(".ui-dialog");
			if ($container.length) {
				$("#cookie_consent_div").dialog("close");
			} else {
				$("#cookie_consent_div").fadeOut("fast");
			}
			return false;
		});
	{/jq}
	{if $prefs.cookie_consent_mode eq 'banner'}
		{jq}
			setTimeout(function () {$("#cookie_consent_div").slideDown("slow");}, 500);
		{/jq}
	{elseif $prefs.cookie_consent_mode eq 'dialog'}
		{jq}
			setTimeout(function () {$("#cookie_consent_div").dialog({modal:true});}, 500);
		{/jq}
	{/if}
{/strip}
