{* $Id$ *}
{if $prefs.feature_ajax eq 'y'}
<div id="ajaxLoading">{tr}Loading...{/tr}</div>
<div id="ajaxLoadingBG">&nbsp;</div>
<div id="ajaxDebug"></div>

{* Temporary for testing *}
<div id="single-spa-application:@vue-mf/pop-up-builder" class="wp-pop-up-builder"></div>
{jq}
    window.registerApplication({
        name: "@vue-mf/pop-up-builder",
        app: () => System.import("@vue-mf/pop-up-builder"),
        activeWhen: (location) => {
            let condition = true;
            return condition;
        },
        // Custom data
        customProps: {
            data: [], // Initial config
        },
    });

    onDOMElementRemoved("single-spa-application:@vue-mf/pop-up-builder", function () {
        window.unregisterApplication("@vue-mf/pop-up-builder");
    });
{/jq}
{/if}
