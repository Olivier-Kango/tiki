import "summernote";
import formatTikiToolbars from "./formatTikiToolbars";
import * as Handlers from "./handlers/index";

export default function (areaId, toolbar, options) {
    const target = $(`#${areaId}`);

    const { tools, icons, customButtons } = formatTikiToolbars(toolbar);

    target.summernote({
        lang: options.lang,
        toolbar: tools,
        icons,
        buttons: customButtons,
        height: options.height,
        callbacks: {
            onInit: function () {
                const toolbar = $(this).data("summernote").layoutInfo.toolbar;
                toolbar.find(".custom-btn-wrapper").each(function () {
                    $(this).children().unwrap();
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
    Handlers.dirtyCheck(target);
    Handlers.pluginEdit(areaId);
}
