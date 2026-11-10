import { describe, expect, test, vi, beforeEach, afterEach } from "vitest";
import { encryptionApi, getCsrfTicket, __resetCsrfTicket } from "../src/api";

const SERVICE = "tiki-ajax_services.php?controller=encryption&action=";
const TICKET = "TESTTICKET-123";

function respond(url) {
    const body = url.includes("action=get_ticket") ? { ticket: TICKET } : {};
    return Promise.resolve({ ok: true, json: () => Promise.resolve(body) });
}

const writes = () => global.fetch.mock.calls.filter(([url]) => !url.includes("action=get_ticket"));

beforeEach(() => {
    __resetCsrfTicket();
    global.fetch = vi.fn().mockImplementation(respond);
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe("encryptionApi reads", () => {
    test("getKeys asks for every key", async () => {
        await encryptionApi.getKeys();
        expect(global.fetch).toHaveBeenCalledWith(`${SERVICE}get_keys`);
    });

    test("getKey asks for one key by id", async () => {
        await encryptionApi.getKey(7);
        expect(global.fetch).toHaveBeenCalledWith(`${SERVICE}get_key&keyId=7`);
    });

    test("getUsers asks for every user, or for those matching a search, encoded", async () => {
        await encryptionApi.getUsers();
        await encryptionApi.getUsers("a&b c");
        expect(global.fetch.mock.calls.map(([url]) => url)).toEqual([`${SERVICE}get_users`, `${SERVICE}get_users&find=a%26b%20c`]);
    });

    test("getAlgos asks for the available algorithms", async () => {
        await encryptionApi.getAlgos();
        expect(global.fetch).toHaveBeenCalledWith(`${SERVICE}get_algos`);
    });

    test("getShareForKey asks for the current user's share of a key", async () => {
        await encryptionApi.getShareForKey(3);
        expect(global.fetch).toHaveBeenCalledWith(`${SERVICE}get_share_for_key&keyId=3`);
    });

    test("a read hands back the response as it came, and a network failure rejects", async () => {
        const res = { ok: false, status: 403, json: () => Promise.resolve({}) };
        global.fetch = vi.fn().mockResolvedValue(res);
        await expect(encryptionApi.getKeys()).resolves.toBe(res);
        global.fetch = vi.fn().mockRejectedValue(new Error("network error"));
        await expect(encryptionApi.getKey(1)).rejects.toThrow("network error");
    });
});

describe("encryptionApi writes", () => {
    test("saveKey posts the form with the CSRF ticket last", async () => {
        const fields = new URLSearchParams({ name: "Payroll", shares: "2" });
        fields.append("users[]", "alice");
        await encryptionApi.saveKey(fields);
        const [[url, init]] = writes();
        expect(url).toBe(`${SERVICE}save_key`);
        expect(init.method).toBe("POST");
        expect(init.headers).toEqual({ "Content-Type": "application/x-www-form-urlencoded" });
        expect([...init.body.entries()]).toEqual([
            ["name", "Payroll"],
            ["shares", "2"],
            ["users[]", "alice"],
            ["ticket", TICKET],
        ]);
    });

    test("deleteKey posts the key id with the CSRF ticket", async () => {
        await encryptionApi.deleteKey(9);
        const [[url, init]] = writes();
        expect(url).toBe(`${SERVICE}delete_key`);
        expect(init.method).toBe("POST");
        expect([...init.body.entries()]).toEqual([
            ["keyId", "9"],
            ["ticket", TICKET],
        ]);
    });

    test("two writes share one ticket request", async () => {
        await encryptionApi.deleteKey(1);
        await encryptionApi.deleteKey(2);
        expect(global.fetch.mock.calls.filter(([url]) => url === `${SERVICE}get_ticket`)).toHaveLength(1);
    });

    test("a failed ticket request still lets the write go, with an empty ticket, and the next write asks again", async () => {
        global.fetch = vi
            .fn()
            .mockImplementation((url) =>
                url.includes("action=get_ticket") ? Promise.resolve({ ok: false, json: () => Promise.resolve({}) }) : respond(url)
            );
        expect(await getCsrfTicket()).toBe("");
        await encryptionApi.deleteKey(4);
        expect(writes()[0][1].body.get("ticket")).toBe("");
        expect(global.fetch.mock.calls.filter(([url]) => url === `${SERVICE}get_ticket`)).toHaveLength(2);
    });
});
