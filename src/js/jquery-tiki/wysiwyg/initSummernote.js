import "summernote";
import formatTikiToolbars from "./formatTikiToolbars";
import * as Handlers from "./handlers/index";

export default function (areaId, toolbar, lang) {
    const target = $(`#${areaId}`);

    const { tools, icons, customButtons, renderCallbacks } = formatTikiToolbars(toolbar);

    target.summernote({
        lang,
        toolbar: tools,
        icons,
        buttons: customButtons,
        callbacks: {
            onInit: function () {
                renderCallbacks.forEach((cbName) => {
                    if (typeof cbName === "string") {
                        window[cbName]();
                    } else {
                        cbName();
                    }
                });
            },
            onKeydown: function (event) {
                if (event.key === "@") {
                    event.preventDefault();
                    renderUserMentionModal(areaId);
                }
            },
            onCodeviewToggled: function () {
                Handlers.customCodeview(target);
            },
        },
    });

    Handlers.formSubmission(target);
    Handlers.pluginEdit(areaId);
}
