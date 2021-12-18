{* $Id$ *}
{if $prefs.feature_ajax eq 'y'}
<div id="ajaxLoading">{tr}Loading...{/tr}</div>
<div id="ajaxLoadingBG">&nbsp;</div>
<div id="ajaxDebug"></div>
<div id="single-spa-application:@vue-mf/kanban" class="container wp-kanban"></div>

{jq}
    window.registerApplication({
        name: "@vue-mf/kanban",
        app: () => System.import("@vue-mf/kanban"),
        activeWhen: (location) => {
            let condition = true;
            return condition;
        },
        // Custom data
        customProps: {
            kanbanData: [],
        },
    });
{/jq}
{/if}
