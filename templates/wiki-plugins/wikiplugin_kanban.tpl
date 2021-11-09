<div id="single-spa-application:@vue-mf/kanban-{$kanbanData.id|escape}" class="wp-kanban"></div>

{jq}
    window.registerApplication({
        name: "@vue-mf/kanban-{{$kanbanData.id|escape}}",
        app: () => System.import("@vue-mf/kanban"),
        activeWhen: (location) => {
            let condition = true;
            return condition;
        },
        // Custom data
        customProps: {
            kanbanData: {{$kanbanData|json_encode}},
        },
    });
{/jq}
