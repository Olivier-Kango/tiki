import { fireEvent, render, screen, within } from "@testing-library/vue";
import { describe, expect, test, vi } from "vitest";
import { h } from "vue";
import Select, { DATA_TEST_ID } from "../../components/Select.vue";
import { ElOption, ElSelect } from "element-plus/dist/index.full.mjs";
import * as SortableHelper from "../../helpers/select/sortable";
import Sortable from "sortablejs";

vi.mock("element-plus/dist/index.full.mjs", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElOption: vi.fn((props) => h("div", props)),
        ElSelect: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
    };
});

vi.mock("sortablejs", async () => {
    return {
        default: vi.fn(),
    };
});

vi.mock("../../helpers/select/sortable", async () => {
    return {
        sortOptions: vi.fn(),
    };
});

describe("Select", () => {
    const basicProps = {
        options: JSON.stringify([
            { value: "foo", label: "Foo" },
            { value: "bar", label: "Bar" },
        ]),
        placeholder: "Select",
        value: JSON.stringify("foo"),
    };

    afterEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    test("renders correctly with some basic props", () => {
        render(Select, { props: basicProps });

        const selectWrapper = screen.getByTestId(DATA_TEST_ID.SELECT_WRAPPER);
        expect(selectWrapper).to.exist;

        const select = within(selectWrapper).getByTestId(DATA_TEST_ID.SELECT_ELEMENT);
        expect(select).to.exist;
        const selectOptions = within(select).getAllByTestId(DATA_TEST_ID.SELECT_OPTION);
        const givenOptios = JSON.parse(basicProps.options);
        expect(selectOptions).toHaveLength(givenOptios.length);
        givenOptios.forEach((option) => {
            expect(ElOption).toHaveBeenCalledWith(expect.objectContaining(option), null);
        });

        expect(ElSelect).toHaveBeenCalledWith(expect.objectContaining({ modelValue: JSON.parse(basicProps.value) }), expect.any(Object));
    });

    test("renders correctly with some advanced props", () => {
        const givenProps = {
            ...basicProps,
            clearable: "true",
            filterable: "true",
            multiple: "true",
            allowCreate: "true",
            collapseTags: "true",
            maxCollapseTags: "2",
            max: "2",
        };

        render(Select, { props: givenProps });

        expect(ElSelect).toHaveBeenCalledWith(
            expect.objectContaining({
                clearable: true,
                filterable: true,
                multiple: givenProps.multiple,
                "allow-create": true,
                "collapse-tags": true,
                "max-collapse-tags": givenProps.maxCollapseTags,
                "multiple-limit": givenProps.max,
            }),
            expect.any(Object)
        );
    });

    describe("Behavior", () => {
        test("renders the select wrapper with the invalid class when the isInvalid prop is true", () => {
            const givenProps = {
                ...basicProps,
                isInvalid: "true",
            };

            render(Select, { props: givenProps });

            const selectWrapper = screen.getByTestId(DATA_TEST_ID.SELECT_WRAPPER);
            expect(selectWrapper.getAttribute("class")).to.include("invalid");
        });

        test("Updates the select model value when the value prop changes", async () => {
            const givenProps = {
                ...basicProps,
                value: JSON.stringify("bar"),
            };

            const { rerender } = render(Select, { props: givenProps });

            expect(ElSelect.mock.calls[0][0].modelValue).toBe("bar");

            await rerender({ ...givenProps, value: JSON.stringify("foo") });

            expect(ElSelect.mock.calls[1][0].modelValue).toBe("foo");
        });

        test("initializes sortable for a multiple select when the ordering prop is true", async () => {
            const givenProps = {
                ...basicProps,
                ordering: "true",
                multiple: "true",
            };

            ElSelect = {
                setup() {
                    return () => h("div", { class: "el-select__selection" }, "Selection");
                },
            };

            render(Select, { props: givenProps });

            expect(Sortable).toHaveBeenCalledWith(
                screen.getByText("Selection"),
                expect.objectContaining({
                    onSort: expect.any(Function),
                })
            );
        });
    });

    describe("Actions", () => {
        test("calls the emitValueChange method when the select value changes", async () => {
            const givenProps = {
                ...basicProps,
                emitValueChange: vi.fn(),
            };

            const expectedValueOnChange = "bar";

            ElSelect = {
                setup(_, { emit }) {
                    const handleClick = () => {
                        emit("update:modelValue", expectedValueOnChange);
                        emit("change", expectedValueOnChange);
                    };
                    return () => h("div", { onClick: handleClick }, "Option");
                },
            };

            render(Select, { props: givenProps });

            const option = screen.getByText("Option");
            await fireEvent.click(option);

            expect(givenProps.emitValueChange).toHaveBeenCalledWith({ value: expectedValueOnChange });
        });

        test("reorders the select options when the sort operation is triggered", async () => {
            const givenProps = {
                ...basicProps,
                ordering: "true",
                multiple: "true",
            };
            render(Select, { props: givenProps });

            Sortable.mock.calls[0][1].onSort();

            expect(SortableHelper.sortOptions).toHaveBeenCalledWith(screen.getByTestId(DATA_TEST_ID.SELECT_WRAPPER), JSON.parse(givenProps.options));
        });
    });
});
