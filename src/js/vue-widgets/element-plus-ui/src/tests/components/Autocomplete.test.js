import { fireEvent, render, screen } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import { h } from "vue";
import Autocomplete, { DATA_TEST_ID, TEXT } from "../../components/Autocomplete.vue";
import { ElAutocomplete } from "element-plus/dist/index.full.mjs";
import { fetchSuggestions } from "../../helpers/autocomplete/remote";

vi.mock("element-plus/dist/index.full.mjs", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElAutocomplete: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
    };
});

vi.mock("../../helpers/autocomplete/remote", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        fetchSuggestions: vi.fn(),
    };
});

describe("Autocomplete", () => {
    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    afterEach(() => {
        vi.clearAllMocks();
        vi.resetAllMocks();
    });

    describe("render tests", () => {
        test("renders correctly with default props value", () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
            };

            render(Autocomplete, { props: givenProps });

            const autocompleteElement = screen.getByTestId(DATA_TEST_ID.AUTOCOMPLETE_ELEMENT);

            expect(autocompleteElement).to.exist;

            expect(ElAutocomplete).toHaveBeenCalledWith(
                expect.objectContaining({
                    placeholder: TEXT.INPUT_PLACEHOLDER,
                    "value-key": "value",
                }),
                expect.any(Object)
            );

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });

        test("logs an error to the console when required props are not provided", () => {
            render(Autocomplete, { props: {} });

            expect(consoleErrorSpy).toHaveBeenCalledWith(TEXT.ERROR_NO_REQUIRED_PROPS);
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });

        test("renders correctly with all props provided", () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                sourceList: JSON.stringify(["foo", "bar"]),
                value: "foo",
                placeholder: "Search",
                valueKey: "name",
                selectCb: () => {},
            };

            render(Autocomplete, { props: givenProps });
            expect(ElAutocomplete).toHaveBeenCalledWith(
                expect.objectContaining({
                    modelValue: givenProps.value,
                    debounce: 500,
                    placeholder: givenProps.placeholder,
                    "trigger-on-focus": false,
                    "value-key": givenProps.valueKey,
                }),
                expect.any(Object)
            );

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });
    });

    describe("action tests", () => {
        test("calls props.emitCustomEvent when ElAutocomplete emits a keyup.enter event", async () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                emitCustomEvent: vi.fn(),
            };

            ElAutocomplete = {
                emits: ["keyup.enter"],
                setup(_, { emit }) {
                    return () =>
                        h(
                            "div",
                            {},
                            h("input", {
                                "data-testid": "autocomplete-input",
                                onKeyup: (e) => {
                                    if (e.key === "Enter") {
                                        emit("keyup.enter");
                                    }
                                },
                            })
                        );
                },
            };

            render(Autocomplete, { props: givenProps });

            const input = screen.getByTestId("autocomplete-input");
            await fireEvent.keyUp(input, { key: "Enter" });

            expect(givenProps.emitCustomEvent).toHaveBeenCalledWith("pressEnter", undefined);
        });

        test.each([
            ["the remote source URL is provided", "https://foo/bar", null],
            ["the source list is provided", null, JSON.stringify([{ value: "foo" }, { value: "bar" }])],
        ])("calls the fetchSuggestions function when %s", async (_, remoteSourceUrl, sourceList) => {
            const givenProps = {
                remoteSourceUrl,
                sourceList,
            };

            ElAutocomplete = {
                props: ["fetchSuggestions"],
                setup(props) {
                    return () =>
                        h(
                            "div",
                            {},
                            h("input", { "data-testid": "autocomplete-input", onChange: (e) => props.fetchSuggestions(e.target.value, () => {}) })
                        );
                },
            };

            render(Autocomplete, { props: givenProps });
            const input = screen.getByTestId("autocomplete-input");
            await fireEvent.change(input, { target: { value: "foo" } });

            expect(fetchSuggestions).toHaveBeenCalledWith("foo", expect.any(Function), remoteSourceUrl, sourceList ? JSON.parse(sourceList) : []);
        });

        test("calls props.emitCustomEvent when ElAutocomplete emits a select event", async () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                emitCustomEvent: vi.fn(),
            };

            ElAutocomplete = {
                emits: ["select"],
                setup(_, { emit }) {
                    return () =>
                        h(
                            "div",
                            {},
                            h("div", { "data-testid": "autocomplete-suggestion", onClick: () => emit("select", "suggestion") }, "Suggestion")
                        );
                },
            };

            render(Autocomplete, { props: givenProps });

            const suggestion = screen.getByTestId("autocomplete-suggestion");
            await fireEvent.click(suggestion);

            expect(givenProps.emitCustomEvent).toHaveBeenCalledWith("select", "suggestion");
        });

        test("calls props.emitCustomEvent when ElAutocomplete emits a input event", async () => {
            const givenProps = {
                remoteSourceUrl: "https://foo/bar",
                emitCustomEvent: vi.fn(),
            };

            ElAutocomplete = {
                emits: ["update:modelValue"],
                setup(_, { emit }) {
                    return () =>
                        h(
                            "div",
                            {},
                            h("input", {
                                "data-testid": "autocomplete-input",
                                onChange: (e) => emit("input", e.target.value),
                            })
                        );
                },
            };

            render(Autocomplete, { props: givenProps });

            const input = screen.getByTestId("autocomplete-input");
            await fireEvent.change(input, { target: { value: "foo" } });

            expect(givenProps.emitCustomEvent).toHaveBeenCalledWith("input", "foo");
        });
    });
});
