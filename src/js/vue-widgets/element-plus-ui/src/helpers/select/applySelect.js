export function observeSelectElementMutations(select, elementPlusUi) {
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length) {
                syncSelectOptions(elementPlusUi, select);
            }

            // jquery-validation error highlighting
            if (mutation.attributeName === "class") {
                if (mutation.target.classList.contains("is-invalid")) {
                    $(elementPlusUi).attr("is-invalid", true);
                } else {
                    $(elementPlusUi).removeAttr("is-invalid");
                }
            } else if (mutation.attributeName) {
                const attributeName = mutation.attributeName.replace("data-", "");
                $(elementPlusUi).attr(attributeName, mutation.target.getAttribute(mutation.attributeName));
            }
        });
    }).observe(select, { childList: true, attributes: true });
}

export function syncSelectOptions(elementPlusSelect, select) {
    const options = $(select)
        .find("option")
        .map(function () {
            return {
                value: $(this).val(),
                label: $(this).text().trim(),
                disabled: $(this).prop("disabled"),
                group: $(this).parent("optgroup").attr("label"),
            };
        })
        .get();
    $(elementPlusSelect).attr("options", JSON.stringify(options));
    $(elementPlusSelect).attr("value", JSON.stringify($(select).val()));
    if (options.find((option) => option.group)) {
        $(elementPlusSelect).attr("group", true);
    }
}

export function attachChangeEventHandler(elementPlusSelect, select) {
    $(elementPlusSelect).on("select-change", function (event) {
        const selectedValues = event.detail[0].value;

        // Adding new items to the select list
        const appendOption = (value) => {
            if (!$(select).find(`option[value="${value}"]`).length) {
                const option = $("<option></option>").val(value).text(value);
                $(select).append(option);
            }
        };
        if (Array.isArray(selectedValues)) {
            selectedValues.forEach(appendOption);
        } else {
            appendOption(selectedValues);
        }

        $(select).val(selectedValues);
        $(select).trigger("change");
    });
}

/**
 * Determines whether a given element or any of its ancestors
 * has an attribute that starts with "data-v-".
 *
 * This is useful for identifying elements that are part of
 * rendered Vue app, where Vue uses dynamic scoped attributes
 * such as "data-v-xxxx" for encapsulation.
 *
 * @param {HTMLElement} el - The element to check.
 * @returns {boolean} True if the element or any parent element has a "data-v-" attribute, false otherwise.
 */
export function hasVueScopedAttribute(el) {
    while (el) {
        if (el.attributes && Array.from(el.attributes).some((attr) => attr.name.startsWith("data-v-"))) {
            return true;
        }
        el = el.parentElement;
    }
    return false;
}
