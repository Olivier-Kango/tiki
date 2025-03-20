export default function (areaId) {
    $(document).on("click", `#${areaId} + .note-editor img.plugin_icon`, function (e) {
        const wrapper = $(this).closest(".tiki_plugin");
        const args = {};
        const argsStr = wrapper.data("args");
        if (argsStr) {
            argsStr.split("&").forEach((arg) => {
                const [key, value] = arg.split("=");
                args[key] = value;
            });
        }

        const body = wrapper.data("syntax").match(/}(.*){/)?.[1];

        popupPluginForm(areaId, wrapper.data("plugin"), 0, "", args, false, body ?? "", null, null, null, {
            target: wrapper,
        });
    });
}
