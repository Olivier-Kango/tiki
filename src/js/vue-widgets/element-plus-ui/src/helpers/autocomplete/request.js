import { fetchSuggestions } from "./remote";

/**
 * Create a suggestions requester that enforces a minimum query length, cancels the previous request
 * when a new one starts and ignores responses for older queries
 * @param {Function} getOptions returns { remoteSourceUrl, sourceList, remoteQueryKey, wildcardSearch, minChars }. It is a function and not a
 * plain object because the options come from reactive component props, so they are read again on every request
 * @returns {{ request: Function, cancel: Function }}
 */
export function createSuggestionsRequester(getOptions) {
    let controller = null;
    let lastCallback = null;
    // Used to ignore responses for older queries
    let sequence = 0;

    /**
     * Cancel the pending request and close the loading state of its suggestions list
     */
    const cancel = () => {
        sequence++;
        controller?.abort();
        controller = null;
        lastCallback?.([]);
        lastCallback = null;
    };

    /**
     * @param {String} query search term
     * @param {Function} callback receives the suggestions
     */
    const request = (query, callback) => {
        const { remoteSourceUrl, sourceList = [], remoteQueryKey, wildcardSearch = false, minChars = 1 } = getOptions();

        controller?.abort();
        controller = null;
        const current = ++sequence;
        lastCallback = callback;

        const done = (results) => {
            if (current !== sequence) {
                return; // stale response
            }
            lastCallback = null;
            callback(results);
        };

        // Local lists are filtered without any request
        if (sourceList.length) {
            fetchSuggestions(query, done, remoteSourceUrl, sourceList, remoteQueryKey, wildcardSearch);
            return;
        }

        if ((query || "").trim().length < Math.max(1, Number(minChars) || 1)) {
            done([]);
            return;
        }

        controller = new AbortController();
        fetchSuggestions(query, done, remoteSourceUrl, sourceList, remoteQueryKey, wildcardSearch, controller.signal);
    };

    return { request, cancel };
}
