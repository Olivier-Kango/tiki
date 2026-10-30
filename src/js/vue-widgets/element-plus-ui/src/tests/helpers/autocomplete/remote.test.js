import { describe, test, vi, expect, afterEach, beforeEach } from "vitest";
import { fetchSuggestions } from "../../../helpers/autocomplete/remote";

describe("Autocomplete remote helper functions", () => {
    describe("fetchSuggestions", () => {
        beforeEach(() => {
            window.jqueryTiki = {};
        });

        afterEach(() => {
            vi.clearAllMocks();
        });

        test("does not add wildcards to username lookups", async () => {
            window.jqueryTiki.tiki_object_selector_wildcardsearch = true;
            vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn().mockResolvedValueOnce(["alice"]) });
            const callback = vi.fn();

            fetchSuggestions("a", callback, "https://example.com/tiki-ajax_services.php?listonly=users", [], "q");
            await window.happyDOM.waitUntilComplete();

            const requestUrl = new URL(window.fetch.mock.calls.at(-1)[0]);
            expect(requestUrl.searchParams.get("q")).toBe("a");
            expect(callback).toHaveBeenCalledWith([{ value: "alice" }]);
        });

        test.each([
            ["wraps the query with wildcards when wildcardSearch is enabled", true, "*a*"],
            ["sends the query as is when wildcardSearch is disabled", false, "a"],
            ["sends the query as is when wildcardSearch is not provided", undefined, "a"],
        ])("%s", async (_, wildcardSearch, expectedQuery) => {
            window.jqueryTiki.tiki_object_selector_wildcardsearch = true;
            vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn().mockResolvedValueOnce(["alice"]) });
            const callback = vi.fn();

            fetchSuggestions("a", callback, "https://example.com/tiki-listpages.php?listonly", [], "q", wildcardSearch);
            await window.happyDOM.waitUntilComplete();

            const requestUrl = new URL(window.fetch.mock.calls.at(-1)[0]);
            expect(requestUrl.searchParams.get("q")).toBe(expectedQuery);
            expect(callback).toHaveBeenCalledWith([{ value: "alice" }]);
        });

        test.each([
            ["foo", ["foo", "bar"], ["foo"]],
            ["foo", ["bar"], []],
            ["f", ["foo", "bar"], ["foo"]],
            ["o", ["foo", "bar"], ["foo"]],
            ["oo", ["foo", "bar"], ["foo"]],
            ["a", ["foo", "bar"], ["bar"]],
            ["a", ["foo", "bar", "baz"], ["bar", "baz"]],
        ])(`given a sourceList, calls the callback with items that match the query`, (query, sourceList, expected) => {
            sourceList = sourceList.map((item) => ({ value: item }));
            expected = expected.map((item) => ({ value: item }));
            const callback = vi.fn();

            fetchSuggestions(query, callback, null, sourceList);

            expect(callback).toHaveBeenCalledWith(expected);
        });

        test.each([
            ["an array of strings", ["foo", "bar"], [{ value: "foo" }, { value: "bar" }]],
            ["an array of objects", [{ value: "foo" }, { value: "bar" }], [{ value: "foo" }, { value: "bar" }]],
        ])(
            "calls the callback with the results fetched from the remote URL in the correct shape, when the result is %s",
            async (_, expectedSuggestions, expectedCallbackArg) => {
                const query = "foo";
                const callback = vi.fn();
                const sourceRemoteUrl = "https://foo/bar";
                const sourceList = [];

                vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn().mockResolvedValueOnce(expectedSuggestions) });

                fetchSuggestions(query, callback, sourceRemoteUrl, sourceList);

                await new Promise((resolve) => setTimeout(resolve, 0));

                expect(callback).toHaveBeenCalledWith(expectedCallbackArg);
            }
        );

        test("logs an error and stop execution when neither sourceRemoteUrl nor sourceList is provided", () => {
            const callback = vi.fn();

            vi.spyOn(console, "error");

            fetchSuggestions("foo", callback);

            expect(console.error).toHaveBeenCalledWith("Either sourceRemoteUrl or sourceList must be provided to fetch suggestions"); // eslint-disable-line no-console
            expect(callback).not.toHaveBeenCalled();
        });

        test.each([
            ["without a custom remote query key", undefined, "q"],
            ["with a custom remote query key", "foo", "foo"],
        ])(
            "(%s), the URL is correctly constructed with the query parameter and Fetch is instructed to only accept json response data",
            async (_, customRemoteQueryKey, expectedQueryKey) => {
                const query = "foo";
                const callback = vi.fn();
                const sourceRemoteUrl = "https://foo/bar";
                const sourceList = [];

                vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn() });

                fetchSuggestions(query, callback, sourceRemoteUrl, sourceList, customRemoteQueryKey);

                await window.happyDOM.waitUntilComplete();

                const expectedUrl = new URL(sourceRemoteUrl);
                expectedUrl.searchParams.append(expectedQueryKey, query);

                expect(window.fetch).toHaveBeenCalledWith(expectedUrl.href, {
                    headers: {
                        Accept: "application/json",
                    },
                });
            }
        );
    });
});
