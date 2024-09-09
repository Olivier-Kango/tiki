import { render, screen, waitFor } from "@testing-library/vue";
import { describe, test, expect, vi, beforeEach, afterEach } from "vitest";
import App from "../App.vue";
import { ElConfigProvider } from "element-plus/dist/index.full.mjs";
import { h } from "vue";
import Transfer from "../components/Transfer.vue";

vi.mock("/public/generated/js/vendor_dist/element-plus/dist/locale/en.min.mjs", () => ({
    default: {
        name: "locale",
    },
}));

vi.mock("element-plus/dist/index.full.mjs", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElConfigProvider: vi.fn((props, { slots }) => {
            return h("div", { "data-testid": "config-provider" }, slots.default ? slots.default() : null);
        }),
    };
});

vi.mock("../components/Transfer.vue", async (importOriginal) => {
    return {
        default: vi.fn((props) => h("div", props)),
    };
});

describe("App", () => {
    test("renders the correct component with the given props", async () => {
        const givenComponentProps = {
            foo: "bar",
            baz: "qux",
        };
        render(App, {
            props: {
                component: "Transfer",
                language: "en",
                ...givenComponentProps,
            },
        });

        expect(screen.getByTestId("app-component").textContent).to.exist;
        expect(Transfer).toHaveBeenCalledWith(expect.objectContaining(givenComponentProps), null);
    });

    test("loads and applies the correct locale", async () => {
        render(App, {
            props: {
                component: "div",
                language: "en",
            },
        });

        await waitFor(() =>
            expect(ElConfigProvider).toHaveBeenCalledWith(
                expect.objectContaining({
                    locale: { name: "locale" },
                }),
                expect.anything()
            )
        );
    });

    test("logs an error if locale fails to load", async () => {
        const consoleErrorSpy = vi.spyOn(console, "error");
        render(App, {
            props: {
                component: "div",
                language: "unknown",
            },
        });

        await waitFor(() => expect(consoleErrorSpy).toHaveBeenCalled());
    });
});
