import { afterEach, beforeEach, expect, test, vi } from "vitest";
import { Window } from "happy-dom";
import $ from "jquery";
import applySelect from "../../utils/applySelect";
import * as applySelectHelpers from "../../helpers/select/applySelect";

describe("applySelect", () => {
    beforeEach(() => {
        window.$ = $;
    });

    afterEach(() => {
        global.window = new Window();
        global.document = window.document;
    });

    test("transforms select element into element-plus-ui components", async () => {
        window.elementPlus = {
            select: {},
        };

        const observeSelectElementMutationsSpy = vi.spyOn(applySelectHelpers, "observeSelectElementMutations");
        const syncSelectOptionsSpy = vi.spyOn(applySelectHelpers, "syncSelectOptions");
        const attachChangeEventHandlerSpy = vi.spyOn(applySelectHelpers, "attachChangeEventHandler");

        applySelect();

        const givenSelects = [];
        for (let i = 0; i < 5; i++) {
            const select = document.createElement("select");
            select.setAttribute("placeholder", `Select ${i}`);
            if (i % 2 === 0) {
                select.multiple = true;
                select.setAttribute("data-max", i);
            }
            const option = document.createElement("option");
            const value = Math.random().toString(36).substring(7);
            option.value = `Option ${value}`;
            option.text = `Option ${value}`;
            option.disabled = i % 2 === 0;
            select.appendChild(option);
            givenSelects.push(select);
        }

        document.body.append(...givenSelects);

        await window.happyDOM.waitUntilComplete();

        givenSelects.forEach((select, index) => {
            const elementPlusUi = select.nextElementSibling;
            expect(elementPlusUi).toBeTruthy();
            expect(elementPlusUi.tagName).toBe("ELEMENT-PLUS-UI");
            expect(elementPlusUi.getAttribute("component")).toBe("Select");
            expect(elementPlusUi.getAttribute("placeholder")).toBe(select.getAttribute("placeholder"));
            expect(elementPlusUi.getAttribute("multiple")).toBe(select.multiple ? "multiple" : null);
            expect(elementPlusUi.getAttribute("max")).toBe(select.getAttribute("data-max"));
            expect(select.getAttribute("element-plus-ref")).toBe(elementPlusUi.id);

            expect(select.style.display).toBe("none");

            expect(observeSelectElementMutationsSpy).toHaveBeenNthCalledWith(index + 1, select, elementPlusUi);
            expect(syncSelectOptionsSpy).toHaveBeenNthCalledWith(index + 1, elementPlusUi, select);
            expect(attachChangeEventHandlerSpy).toHaveBeenNthCalledWith(index + 1, elementPlusUi, select);
        });
    });

    test.each([
        [{ clearable: true, collapseTags: true, maxCollapseTags: 2, filterable: true, allowCreate: true }, "true"],
        [{ clearable: false, collapseTags: false, maxCollapseTags: 0, filterable: false, allowCreate: false }, "false"],
    ])("applies the right attributes to the element-plus-ui component based on the preferences", async (preferences, expectedBoolean) => {
        window.elementPlus = {
            select: preferences,
        };

        applySelect();

        const givenSelect = document.createElement("select");
        document.body.appendChild(givenSelect);

        await window.happyDOM.waitUntilComplete();

        const elementPlusUi = document.querySelector("element-plus-ui");
        expect(elementPlusUi.getAttribute("clearable")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("collapse-tags")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("max-collapse-tags")).toBe(preferences.maxCollapseTags.toString());
        expect(elementPlusUi.getAttribute("filterable")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("allow-create")).toBe(expectedBoolean);
    });
});
