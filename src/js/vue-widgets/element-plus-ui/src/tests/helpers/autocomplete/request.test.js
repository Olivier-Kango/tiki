import { afterEach, describe, expect, test, vi } from "vitest";
import { createSuggestionsRequester } from "../../../helpers/autocomplete/request";
import { fetchSuggestions } from "../../../helpers/autocomplete/remote";

vi.mock("../../../helpers/autocomplete/remote", () => ({
    fetchSuggestions: vi.fn(),
}));

describe("createSuggestionsRequester", () => {
    const remoteOptions = { remoteSourceUrl: "https://foo/bar", sourceList: [], remoteQueryKey: "q", minChars: 3 };

    afterEach(() => {
        vi.clearAllMocks();
    });

    test("does not search below minChars and returns an empty list right away", () => {
        const callback = vi.fn();
        const { request } = createSuggestionsRequester(() => remoteOptions);

        request("fo", callback);

        expect(fetchSuggestions).not.toHaveBeenCalled();
        expect(callback).toHaveBeenCalledWith([]);
    });

    test("filters a local sourceList whatever the query length", () => {
        const sourceList = [{ value: "foo" }];
        const { request } = createSuggestionsRequester(() => ({ ...remoteOptions, sourceList }));

        request("f", vi.fn());

        expect(fetchSuggestions).toHaveBeenCalledWith("f", expect.any(Function), remoteOptions.remoteSourceUrl, sourceList, "q", false);
    });

    test("aborts the previous request and ignores its response when a newer query is requested", () => {
        const callback = vi.fn();
        const { request } = createSuggestionsRequester(() => remoteOptions);

        request("foobar", callback);
        const [, firstCallback, , , , , firstSignal] = fetchSuggestions.mock.calls[0];
        request("foobarbaz", callback);

        expect(firstSignal.aborted).toBe(true);
        firstCallback([{ value: "stale" }]);
        expect(callback).not.toHaveBeenCalled();

        fetchSuggestions.mock.calls[1][1]([{ value: "fresh" }]);
        expect(callback).toHaveBeenCalledTimes(1);
        expect(callback).toHaveBeenCalledWith([{ value: "fresh" }]);
    });

    test("cancel aborts the pending request and stops its loading state", () => {
        const callback = vi.fn();
        const { request, cancel } = createSuggestionsRequester(() => remoteOptions);

        request("foobar", callback);
        const [, pendingCallback, , , , , signal] = fetchSuggestions.mock.calls[0];
        cancel();

        expect(signal.aborted).toBe(true);
        expect(callback).toHaveBeenCalledTimes(1);
        expect(callback).toHaveBeenCalledWith([]);

        pendingCallback([{ value: "late" }]);
        expect(callback).toHaveBeenCalledTimes(1);
    });
});
