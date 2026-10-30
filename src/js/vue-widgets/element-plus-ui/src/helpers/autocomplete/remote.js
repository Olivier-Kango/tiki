/**
 * Fetch suggestions that match the given query from a given source of items
 * @param {String} query search term
 * @param {Function} callback function to call with the results
 * @param {String|null} sourceRemoteUrl remote URL to fetch suggestions from
 * @param {Array} sourceList suggestions to filter from instead of fetching from the remote URL
 * @param {String} remoteQueryKey query parameter key to use when fetching suggestions from the remote URL, defaults to 'q'
 * @param {Boolean} wildcardSearch wrap the query with wildcards, for remote sources that only match whole words (e.g. the search index)
 */
export function fetchSuggestions(query, callback, sourceRemoteUrl = null, sourceList = [], remoteQueryKey = "q", wildcardSearch = false) {
    if (sourceList.length) {
        const filteredList = sourceList.filter((item) => item.value.toLowerCase().includes(query.toLowerCase()));
        return callback(filteredList);
    }

    if (!sourceRemoteUrl) {
        console.error("Either sourceRemoteUrl or sourceList must be provided to fetch suggestions"); // eslint-disable-line no-console
        return;
    }

    const url = new URL(sourceRemoteUrl);
    if (wildcardSearch) {
        query = "*" + query + "*";
    }

    url.searchParams.append(remoteQueryKey, query);
    fetch(url.href, {
        headers: {
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data?.length) {
                if (typeof data[0] === "string") {
                    data = data.map((item) => ({ value: item }));
                }
            }
            callback(data);
        });
}
