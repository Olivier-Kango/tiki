import { render, screen } from "@testing-library/vue";
import { describe, expect, test, vi } from "vitest";
import Input, { DATA_TEST_ID } from "../../components/Input/Input.vue";
import { ElInput } from "element-plus";
import { h } from "vue";

vi.mock("element-plus", () => {
    return {
        ElInput: vi.fn((props) => h("div", props)),
    };
});

vi.mock("@element-plus/icons-vue", () => {
    return {
        "mockPrefix-icon": "mockPrefix-icon",
        "mockSuffix-icon": "mockSuffix-icon",
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

describe("Input", () => {
    afterEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    test("renders correctly with some basic props", () => {
        const givenProps = {
            value: "foo",
            placeholder: "foo",
            _expose: vi.fn(),
            _emit: vi.fn(),
        };

        render(Input, { props: givenProps });

        expect(screen.getByTestId(DATA_TEST_ID.INPUT)).to.exist;
        expect(ElInput).toHaveBeenCalledWith(
            expect.objectContaining({
                modelValue: givenProps.value,
                placeholder: givenProps.placeholder,
                "show-password": false,
                "prefix-icon": null,
                "suffix-icon": null,
                clearable: false,
            }),
            null
        );
        expect(givenProps._expose).toHaveBeenCalledWith({ value: expect.objectContaining({ _value: givenProps.value, __v_isRef: true }) });
    });

    test("renders correctly with all props", () => {
        const givenProps = {
            value: "foo",
            placeholder: "foo",
            showPassword: "true",
            prefixIcon: "mockPrefix-icon",
            suffixIcon: "mockSuffix-icon",
            clearable: "true",
            _expose: vi.fn(),
            _emit: vi.fn(),
        };

        render(Input, { props: givenProps });

        expect(screen.getByTestId(DATA_TEST_ID.INPUT)).to.exist;
        expect(ElInput).toHaveBeenCalledWith(
            expect.objectContaining({
                modelValue: givenProps.value,
                placeholder: givenProps.placeholder,
                "show-password": true,
                "prefix-icon": givenProps.prefixIcon,
                "suffix-icon": givenProps.suffixIcon,
                clearable: true,
            }),
            null
        );
    });

    test.each([["input"], ["change"]])("emits the %s event to the context when it is triggered from within the element", (eventType) => {
        const givenProps = {
            value: "foo",
            placeholder: "foo",
            _expose: vi.fn(),
            _emit: vi.fn(),
        };

        ElInput = {
            emits: ["input", "change"],
            setup(_, { emit }) {
                return () =>
                    h("input", {
                        "data-testid": DATA_TEST_ID.INPUT,
                        onInput: () => emit("input", "bar"),
                        onChange: () => emit("change", "bar"),
                    });
            },
        };

        render(Input, { props: givenProps });

        const input = screen.getByTestId(DATA_TEST_ID.INPUT);
        input.dispatchEvent(new Event(eventType));

        expect(givenProps._emit).toHaveBeenCalledWith(eventType, "bar");
    });
});
