import { afterEach, describe, expect, test, vi } from "vitest";
import { watchEffect } from "vue";
import {
    draggedItem,
    startDrag,
    endDrag,
    setRefusal,
    refusalFor,
    isRefusing,
    isCopyRequested,
    wasReleasedOutside,
} from "../../../helpers/select/dragBetweenFields";

describe("Select drag between fields helper functions", () => {
    afterEach(() => {
        endDrag();
        vi.restoreAllMocks();
    });

    test("startDrag when called, keeps the value of the dragged item", () => {
        startDrag({ value: "foo", label: "Foo" });

        expect(draggedItem.value).toEqual({ value: "foo" });
    });

    test("startDrag when called without a value, keeps nothing", () => {
        startDrag({ label: "Foo" });

        expect(draggedItem.value).toBe(null);
    });

    test("startDrag when called, marks the page as carrying a value for the stylesheet", () => {
        startDrag({ value: "foo" });

        expect(document.body.classList.contains("carrying-a-value")).toBe(true);
    });

    test("endDrag when called, forgets the dragged item and leaves that mark behind", () => {
        startDrag({ value: "foo" });

        endDrag();

        expect(draggedItem.value).toBe(null);
        expect(document.body.classList.contains("carrying-a-value")).toBe(false);
    });

    test("endDrag when called, forgets a refusal standing at the time", () => {
        const givenField = {};
        startDrag({ value: "foo" });
        setRefusal(givenField, "duplicate");

        endDrag();

        expect(isRefusing()).toBe(false);
        expect(refusalFor(givenField)).toBe("");
    });

    test("setRefusal when called, speaks for the field having its say only", () => {
        const givenRefusingField = {};
        const givenOtherField = {};

        setRefusal(givenRefusingField, "duplicate");

        expect(refusalFor(givenRefusingField)).toBe("duplicate");
        expect(refusalFor(givenOtherField)).toBe("");
        expect(isRefusing()).toBe(true);
    });

    test("setRefusal when called without a reason, takes the refusal back", () => {
        const givenField = {};
        setRefusal(givenField, "duplicate");

        setRefusal(givenField, "");

        expect(refusalFor(givenField)).toBe("");
        expect(isRefusing()).toBe(false);
    });

    test("setRefusal when called by another field, has the last word", () => {
        const givenRefusingField = {};
        const givenAcceptingField = {};
        setRefusal(givenRefusingField, "duplicate");

        setRefusal(givenAcceptingField, "");

        expect(refusalFor(givenRefusingField)).toBe("");
        expect(isRefusing()).toBe(false);
    });

    test("the shared state when it changes, wakes up whoever reads it, with no subscription", () => {
        const seen = [];
        const stopWatching = watchEffect(() => seen.push(refusalFor("foo")), { flush: "sync" });

        setRefusal("foo", "duplicate");
        setRefusal("foo", "");
        stopWatching();

        expect(seen).toEqual(["", "duplicate", ""]);
    });

    test("isCopyRequested when the modifier the system copies with is held, is true", () => {
        expect(isCopyRequested({ ctrlKey: true })).toBe(true);
        expect(isCopyRequested({ altKey: true })).toBe(true);
    });

    test("isCopyRequested without that modifier, or without an event at all, is false", () => {
        expect(isCopyRequested({ ctrlKey: false, altKey: false })).toBe(false);
        expect(isCopyRequested({ metaKey: true })).toBe(false);
        expect(isCopyRequested(undefined)).toBe(false);
    });

    test("wasReleasedOutside when the pointer was on the field taking the value, is false", () => {
        const givenTag = document.createElement("div");
        const givenField = document.createElement("div");
        givenField.appendChild(givenTag);
        vi.spyOn(document, "elementFromPoint").mockReturnValue(givenTag);

        expect(wasReleasedOutside({ to: givenField, originalEvent: { clientX: 7, clientY: 9 } })).toBe(false);
    });

    test("wasReleasedOutside when the pointer was away from that field, is true", () => {
        const givenField = document.createElement("div");
        vi.spyOn(document, "elementFromPoint").mockReturnValue(document.createElement("div"));

        expect(wasReleasedOutside({ to: givenField, originalEvent: { clientX: 7, clientY: 9 } })).toBe(true);
    });

    test("wasReleasedOutside when the field is drawn in a shadow root, looks through it", () => {
        const givenTag = document.createElement("div");
        const givenField = document.createElement("div");
        givenField.appendChild(givenTag);
        const givenHost = document.createElement("div");
        givenHost.attachShadow({ mode: "open" }).appendChild(givenField);
        vi.spyOn(document, "elementFromPoint").mockReturnValue(givenHost);
        givenHost.shadowRoot.elementFromPoint = () => givenTag;

        expect(wasReleasedOutside({ to: givenField, originalEvent: { clientX: 7, clientY: 9 } })).toBe(false);
    });

    test("wasReleasedOutside without a drop to read the pointer from, is false", () => {
        expect(wasReleasedOutside(undefined)).toBe(false);
        expect(wasReleasedOutside({ to: document.createElement("div") })).toBe(false);
        expect(wasReleasedOutside({ originalEvent: { clientX: 7, clientY: 9 } })).toBe(false);
    });
});
