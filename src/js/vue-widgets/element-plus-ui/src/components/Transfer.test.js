import { fireEvent, render, screen, within } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import Transfer, { DATA_TEST_ID, DRAG_HANDLER_CLASS } from "./Transfer.vue";
import { ElTransfer } from "element-plus/dist/index.full.mjs";
import { h } from "vue";
import Sortable from "sortablejs";

vi.mock("element-plus/dist/index.full.mjs", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElTransfer: vi.fn(),
    };
});

vi.mock("sortablejs", async () => {
    return {
        default: vi.fn(),
    };
});

describe("Transfer", () => {
    const props = {
        data: { a: "Item A", b: "Item B", c: "Item C" },
        fieldName: "testField",
        filterable: true,
        defaultValue: ["a", "b"],
        sourceListTitle: "Source List",
        targetListTitle: "Target List",
        filterPlaceholder: "Filter items",
        ordering: false,
    };

    afterEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    test("renders correctly with given props", async () => {
        render(Transfer, { props });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        expect(selectElement).to.exist;
        // should be hidden
        expect(selectElement.style).to.have.property("display", "none");
        expect(selectElement.getAttribute("aria-hidden")).to.equal("true");
        // should have correct name
        expect(selectElement.name).to.equal(props.fieldName);
        // its options should be the selected values
        assertSelectElementToHaveOptions(selectElement, props.defaultValue);

        // el-transfer should be rendered with correct props
        const elTransferData = Object.entries(props.data).map(([key, value]) => ({ key, label: value }));
        expect(ElTransfer).toHaveBeenCalledWith(
            expect.objectContaining({
                data: elTransferData,
                filterable: props.filterable,
                "filter-placeholder": props.filterPlaceholder,
                titles: [props.sourceListTitle, props.targetListTitle],
            }),
            null
        );
    });

    test("renders correctly when the given prop data and defaultValue are JSON strings", async () => {
        const data = JSON.stringify(props.data);
        const defaultValue = JSON.stringify(props.defaultValue);
        render(Transfer, { props: { ...props, data, defaultValue } });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        assertSelectElementToHaveOptions(selectElement, props.defaultValue);
        assertElTransferToBeCalledWith(props);
    });

    test("renders correctly when the defaultValue prop is not set", async () => {
        render(Transfer, { props: { ...props, defaultValue: undefined } });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        assertSelectElementToHaveOptions(selectElement, []);
        assertElTransferToBeCalledWith(props);
    });

    test("should correctly initialize SortableJS when ordering prop is true", async () => {
        ElTransfer = {
            setup() {
                return () =>
                    h("div", {}, [h("div", { class: "el-transfer-panel__list" }, "list"), h("div", { class: "el-transfer-panel__list" }, "list 2")]);
            },
        };
        render(Transfer, { props: { ...props, ordering: true } });

        expect(Sortable).toHaveBeenCalledWith(
            screen.getByText("list 2"),
            expect.objectContaining({
                handle: "." + DRAG_HANDLER_CLASS,
            })
        );
    });

    test("should keep hidden select in sync with el-transfer", async () => {
        ElTransfer = {
            props: ["data", "filterable", "filter-placeholder", "titles"],
            emits: ["update:modelValue"],
            setup(props, { emit }) {
                const handleClick = () => emit("update:modelValue", ["c"]);
                return () => h("div", {}, h("button", { onClick: handleClick }, "Transfer Item"));
            },
        };

        render(Transfer, { props });

        const selectElement = screen.getByTestId(DATA_TEST_ID.HIDDEN_SELECT);
        const transferButton = screen.getByText("Transfer Item");

        await fireEvent.click(transferButton);

        // check hidden select's value
        expect(selectElement.options).toHaveLength(1);
        expect(selectElement.options[0].value).to.equal("c");
    });
});

function assertElTransferToBeCalledWith(props) {
    const elTransferData = Object.entries(props.data).map(([key, value]) => ({ key, label: value }));
    expect(ElTransfer).toHaveBeenCalledWith(
        expect.objectContaining({
            data: elTransferData,
            filterable: props.filterable,
            "filter-placeholder": props.filterPlaceholder,
            titles: [props.sourceListTitle, props.targetListTitle],
        }),
        null
    );
}

function assertSelectElementToHaveOptions(selectElement, values) {
    expect(selectElement.options).toHaveLength(values.length);
    values.forEach((value) => {
        const option = within(selectElement)
            .getAllByRole("option", { hidden: true })
            .find((el) => el.value === value);
        expect(option).to.exist;
    });
}
