import { convertToUnixTimestamp, formatDate, goToURLWithData } from "../helpers/helpers";
import * as dateFns from "date-fns";
import moment from "moment-timezone";
import { vi, expect } from "vitest";

vi.mock("date-fns", () => ({
    format: vi.fn(),
}));

describe("convertToUnixTimestamp", () => {
    test("returns the unix timestamp of the given date in the browser timezone when useBrowserTimezone is true", () => {
        const date = new Date("2021-01-01T00:00:00Z").getTime();
        const useBrowserTimezone = true;

        const result = convertToUnixTimestamp(date, useBrowserTimezone);
        const expected = moment(date).unix();

        expect(result).toBe(expected);
    });

    test("returns the unix timestamp of the given date in the custom timezone", () => {
        const date = new Date("2021-01-01T00:00:00Z").getTime();
        const useBrowserTimezone = false;
        const customTimezone = "America/New_York";

        const result = convertToUnixTimestamp(date, useBrowserTimezone, customTimezone);
        const browserOffset = new Date().getTimezoneOffset() * 60;
        const selectedTzOffset = moment.tz(customTimezone).utcOffset() * 60;
        const expected = moment(date).unix() + browserOffset + selectedTzOffset;

        expect(result).toBe(expected);
    });
});

describe("formatDate", () => {
    test("should return the localized formatted date string without time", () => {
        const date = new Date("2021-01-01");
        const withTime = false;
        const locale = "en-US";

        const expected = "1/1/2021";
        vi.spyOn(dateFns, "format").mockReturnValueOnce(expected);

        const result = formatDate(date, withTime, locale);

        expect(result).toBe(expected);
        expect(dateFns.format).toHaveBeenCalledWith(date, "P", { locale });
    });

    test("should return the localized formatted date range string without time", () => {
        const date = [new Date("2021-01-01"), new Date("2021-01-02")];
        const withTime = false;
        const locale = "en-US";

        const expected = "1/1/2021 - 1/2/2021";
        vi.spyOn(dateFns, "format").mockReturnValueOnce("1/1/2021").mockReturnValueOnce("1/2/2021");

        const result = formatDate(date, withTime, locale);

        expect(result).toBe(expected);
        expect(dateFns.format).toHaveBeenCalledWith(date[0], "P", { locale });
        expect(dateFns.format).toHaveBeenCalledWith(date[1], "P", { locale });
    });

    test("should return the localized formatted date string with time", () => {
        const date = new Date("2021-01-01");
        const withTime = true;
        const locale = "en-US";

        const expected = "1/1/2021 12:00 AM";
        vi.spyOn(dateFns, "format").mockReturnValueOnce(expected);

        const result = formatDate(date, withTime, locale);

        expect(result).toBe(expected);
        expect(dateFns.format).toHaveBeenCalledWith(date, "Pp", { locale });
    });
});

describe("goToURLWithData", () => {
    test("should change window location to the given URL with the updated data", () => {
        const updatedData = new Date("2021-01-01");
        const goToURLOnChange = "/foo";
        const unixTimestamp = 1612051200;
        const toUnixTimestamp = 1612137600;
        const selectedTz = "America/New_York";

        const url = new URL(goToURLOnChange, window.location.origin);
        url.searchParams.set("todate", unixTimestamp);
        url.searchParams.set("enddate", toUnixTimestamp);
        url.searchParams.set("tzname", selectedTz);
        url.searchParams.set("tzoffset", moment.tz(selectedTz).utcOffset());

        const spy = vi.spyOn(window.location, "href", "set");
        goToURLWithData(updatedData, goToURLOnChange, unixTimestamp, toUnixTimestamp, selectedTz);

        expect(spy).toHaveBeenCalledWith(url);
    });

    test("should reset the URL search params when updatedData is null", () => {
        const updatedData = null;
        const goToURLOnChange = "/foo";
        const unixTimestamp = 1612051200;
        const toUnixTimestamp = 1612137600;
        const selectedTz = "America/New_York";

        const url = new URL(goToURLOnChange, window.location.origin);

        const spy = vi.spyOn(window.location, "href", "set");
        goToURLWithData(updatedData, goToURLOnChange, unixTimestamp, toUnixTimestamp, selectedTz);

        expect(spy).toHaveBeenCalledWith(url);
    });

    test("should call the global callback function with the updated data", () => {
        const updatedData = new Date("2021-01-01");
        const goToURLOnChange = "";
        const unixTimestamp = 1612051200;
        const toUnixTimestamp = 1612137600;
        const selectedTz = "America/New_York";
        const globalCallback = "callback";

        window[globalCallback] = vi.fn();

        goToURLWithData(updatedData, goToURLOnChange, unixTimestamp, toUnixTimestamp, selectedTz, globalCallback);

        expect(window[globalCallback]).toHaveBeenCalledWith({
            date: unixTimestamp,
            enddate: toUnixTimestamp,
            tzname: selectedTz,
            tzoffset: moment.tz(selectedTz.offset).utcOffset(),
        });
    });
});
