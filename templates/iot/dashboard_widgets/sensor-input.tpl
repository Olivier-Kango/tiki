{literal}
    <div style="background-color:${borderColor}">
        <div class="grid-stack-item-content" style="background-color:${bgColor}; color:${textColor}">
            <div>
                <div style="${widgetIcon == "Please specify the widget icon" ? "display:none;" : ""}">${widgetIcon}</div>
                <div style="${widgetLabel == "" ? "display:none;" : ""}"><small>${widgetLabel}</small></div>
                <div class="d-flex display-5 gap-1 mt-1 justify-content-center">
                    <div data-widget-data-source="${dataSource}" data-widget-type="${widget}"></div>
                    <div style="${dataUnit == "" ? "display:none;" : ""}">${dataUnit}</div>
                </div>
            </div>
        </div>
    </div>
{/literal}
