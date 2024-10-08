{literal}
    <div style="background-color:${borderColor}">
        <div class="grid-stack-item-content" style="background-color:${bgColor}; color:${textColor}">
            <div>
                <div style="${widgetIcon == "Please specify the widget icon" ? "display:none;" : ""}">${widgetIcon}</div>
                <div class="form-check form-switch">
                    <input data-widget-data-source="${dataSource}" data-widget-type="${widget}" class="form-check-input"
                        type="checkbox" role="switch">
                    <label style="${widgetLabel == "" ? "display:none;" : ""}"
                        class="form-check-label">${widgetLabel}</label>
                </div>
            </div>
        </div>
    </div>
{/literal}
