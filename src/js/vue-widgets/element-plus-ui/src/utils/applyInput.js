import { handleAffixes, handleFileInput } from "../helpers/input/applyInput";

export default function applyInput() {
    transformContainerInputs(document.body);
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            transformContainerInputs(mutation.target);
        });
    }).observe(document.body, { childList: true, subtree: true });
}

function transformContainerInputs(containerElement) {
    const inputs = $(containerElement)
        .find("input")
        .filter(function () {
            if ($(this).attr("element-plus-ref")) return false;
            if ($(this).css("display") === "none") return false;
            if ($(this).closest(".cypht-layout").length) return false;
            if ($(this).closest(".tiki-webmail").length) return false;

            return [undefined, "text", "number", "email", "password", "search", "url", "tel", "file"].includes($(this).attr("type"));
        });

    inputs.each(function () {
        if ($(this).attr("type") === "file") {
            return handleFileInput(this);
        }

        const elementUniqueId = "el-" + Math.random().toString(36).substring(7);
        const elementPlusUi = $("<el-input></el-input>");

        elementPlusUi.attr("placeholder", $(this).attr("placeholder"));
        elementPlusUi.attr("id", elementUniqueId);
        elementPlusUi.attr("value", $(this).val());

        if ($(this).attr("type") === "password") {
            elementPlusUi.attr("show-password", true);
        } else {
            elementPlusUi.attr("type", $(this).attr("type"));
        }

        if ($(this).prop("disabled")) {
            elementPlusUi.attr("disabled", true);
        }
        if ($(this).prop("autofocus")) {
            elementPlusUi.attr("autofocus", true);
        }

        if ($(this).attr("autocomplete")) {
            elementPlusUi.attr("autocomplete", $(this).attr("autocomplete"));
        }

        if ($(this).attr("name")) {
            elementPlusUi.attr("name", $(this).attr("name"));
        }

        if ($(this).attr("style")) {
            elementPlusUi.attr("style", $(this).attr("style"));
        }

        if ($(this).attr("role")) {
            elementPlusUi.attr("role", $(this).attr("role"));
        }

        handleAffixes(this, elementPlusUi);

        $(this).attr("element-plus-ref", elementUniqueId);
        $(this).after(elementPlusUi);
        $(this).hide();

        elementPlusUi.on("change", (event) => {
            $(this).val(event.detail[0]);
            $(this).trigger("change");
        });

        elementPlusUi.on("input", (event) => {
            $(this).val(event.detail?.[0]);
            $(this).trigger("input");
        });

        ["blur", "focus", "keyup", "keydown"].forEach((event) => {
            elementPlusUi.on(event, () => {
                $(this).trigger(event);
                $(this).val(elementPlusUi.val());
            });
        });

        elementPlusUi.on("enter", () => {
            if ($(this).attr("type") === "search" || $(this).attr("role") === "search") {
                $(this).closest("form").trigger("submit");
                $(this).val(elementPlusUi.val());
            }
        });
    });
}
