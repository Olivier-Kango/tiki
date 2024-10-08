{title url="tiki-iot_dashboard.php" help="Iot"}{tr}Tiki IoT Dashboard{/tr}{/title}
{if $app.app_active eq 'y'}
<div id="iot_dashboard" class="h-100 bg-white">
    <div class="row row-dashboard-ui">
        <div class="col-md-12 h-100 px-4 py-3 designer-tab">
            <div class="d-flex bg-primary py-3 px-2 align-items-center text-white rounded-top gap-1">
                <div class="h4">{icon name="dice-d6"}</div>
                <h2 class="mt-0 mb-0 position-relative">{$app.app_name} <span class="position-absolute top-0 mb-1 start-100 translate-middle badge rounded-pill text-bg-warning realtime-status">{tr}connecting...{/tr}</span></h2>
            </div>
            <div class="grid-stack" data-session-token="{$app.session_id}" data-app-name-plain="{$app.app_name}" data-app-id-plain="{$app.app_uuid}" data-grid-stack id="dasboard-{$app.app_uuid}">{if !empty($app.dashboard_config)}{$app.dashboard_config}{/if}</div>
        </div>
    </div>
    <button class="btn btn-primary position-absolute" id="fullscreen-button">{icon name="fullscreen"}&nbsp;{tr}Go Fullscreen{/tr}</button>
</div>
{/if}
