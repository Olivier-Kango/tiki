/**
 * Fetch suggestions that match the given query from a given source of items
 * @param {String} query search term
 * @param {Function} callback function to call with the results
 * @param {String|null} sourceRemoteUrl remote URL to fetch suggestions from
 * @param {Array} sourceList suggestions to filter from instead of fetching from the remote URL
 * @param {String} remoteQueryKey query parameter key to use when fetching suggestions from the remote URL, defaults to 'q'
 * @param {Boolean} wildcardSearch wrap the query with wildcards, for remote sources that only match whole words (e.g. the search index)
 * @param {AbortSignal|null} signal optional signal to cancel the request
 */
export function fetchSuggestions(
    query,
    callback,
    sourceRemoteUrl = null,
    sourceList = [],
    remoteQueryKey = "q",
    wildcardSearch = false,
    signal = null
) {
    if (sourceList.length) {
        const filteredList = sourceList.filter((item) => item.value.toLowerCase().includes(query.toLowerCase()));
        return callback(filteredList);
    }

    if (!sourceRemoteUrl) {
        console.error("Either sourceRemoteUrl or sourceList must be provided to fetch suggestions"); // eslint-disable-line no-console
        return;
    }

    if (wildcardSearch) {
        query = "*" + query + "*";
    }

    const url = new URL(sourceRemoteUrl);
    url.searchParams.append(remoteQueryKey, query);
    const fetchOptions = {
        headers: {
            Accept: "application/json",
        },
    };
    if (signal) {
        fetchOptions.signal = signal;
    }
    fetch(url.href, fetchOptions)
        .then((response) => {
            if (response.ok === false) {
                throw new Error("Autocomplete request failed with status " + response.status);
            }
            return response.json();
        })
        .then((data) => {
            if (data?.length) {
                if (typeof data[0] === "string") {
                    data = data.map((item) => ({ value: item }));
                }
            }
            callback(data);
        })
        .catch((error) => {
            if (error?.name !== "AbortError") {
                console.error(error); // eslint-disable-line no-console
            }
            callback([]); // stop the loading state, including when the request was cancelled
        });
}
