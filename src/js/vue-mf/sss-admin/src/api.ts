// The encryption controller's service actions, the one place the SSS admin front-end builds their URLs and calls
// fetch. Each call hands back the response as it came, so every page keeps its own reading of ok, json and errors.

const SERVICE = "tiki-ajax_services.php?controller=encryption&action=";

function get(action: string, params: Record<string, string | number> = {}): Promise<Response> {
    const query = Object.entries(params)
        .map(([name, value]) => `&${name}=${encodeURIComponent(String(value))}`)
        .join("");
    return fetch(SERVICE + action + query);
}

// The mutating actions (save_key, delete_key) require the CSRF ticket, added last to the form.
async function post(action: string, fields: URLSearchParams): Promise<Response> {
    fields.set("ticket", await getCsrfTicket());
    return fetch(SERVICE + action, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: fields,
    });
}

// The ticket is the stable per-session token: fetched once from the admin-gated get_ticket action, the in-flight
// promise cached so concurrent callers share one request. A failed fetch clears the cache so a later action can retry.
let ticketPromise: Promise<string> | null = null;

export function getCsrfTicket(): Promise<string> {
    if (!ticketPromise) {
        ticketPromise = get("get_ticket")
            .then((res) => (res.ok ? res.json() : Promise.reject(new Error("ticket fetch failed"))))
            .then((data) => String(data?.ticket ?? ""))
            .catch(() => {
                ticketPromise = null;
                return "";
            });
    }
    return ticketPromise;
}

// Test-only: drop the cached ticket so each test starts from a clean slate.
export function __resetCsrfTicket(): void {
    ticketPromise = null;
}

export const encryptionApi = {
    getKeys: () => get("get_keys"),
    getKey: (keyId: number) => get("get_key", { keyId }),
    getUsers: (find = "") => get("get_users", find ? { find } : {}),
    getAlgos: () => get("get_algos"),
    getShareForKey: (keyId: number) => get("get_share_for_key", { keyId }),
    saveKey: (fields: URLSearchParams) => post("save_key", fields),
    deleteKey: (keyId: number) => post("delete_key", new URLSearchParams({ keyId: String(keyId) })),
};
