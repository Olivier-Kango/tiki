import { format } from "date-fns";
import moment from "moment-timezone";

/**
 * @param {Number} date timestamp
 * @param {boolean} useBrowserTimezone
 * @param {string} customTimezone
 * @returns {string}
 * @description
 * Returns the Unix timestamp of the given date.
 */
export function convertToUnixTimestamp(date, useBrowserTimezone, customTimezone) {
    if (useBrowserTimezone) {
        return moment(date).unix();
    }

    const browserOffset = new Date().getTimezoneOffset() * 60;
    const selectedTzOffset = moment.tz(customTimezone).utcOffset() * 60;
    return moment(date).unix() + browserOffset + selectedTzOffset;
}

/**
 * @param {Date|Date[]} date
 * @param {boolean} withTime
 * @param {Locale} locale
 * @returns {string}
 * @description
 * Returns the formatted date string.
 */
export function formatDate(value, withTime, locale) {
    const formatValue = (date) => format(date, withTime ? "Pp" : "P", { locale });
    return Array.isArray(value) ? value.map(formatValue).join(" - ") : formatValue(value);
}

/**
 * @param {Date|Date[]|null} updatedData
 * @param {string} goToURLOnChange
 * @param {number} unixTimestamp
 * @param {number} toUnixTimestamp
 * @param {string} selectedTz
 * @param {string|undefined} globalCallback
 * @returns {void}
 * @description
 * Called when there is the need to navigate to a given URL when the datetime is changed instead of updating the model value.
 */
export function goToURLWithData(updatedData, goToURLOnChange, unixTimestamp, toUnixTimestamp, selectedTz, globalCallback) {
    if (goToURLOnChange) {
        const url = new URL(goToURLOnChange, window.location.origin);
        if (updatedData) {
            url.searchParams.set("todate", unixTimestamp);
            url.searchParams.set("enddate", toUnixTimestamp);
            url.searchParams.set("tzname", selectedTz);
            url.searchParams.set("tzoffset", moment.tz(selectedTz).utcOffset());
        } else {
            url.searchParams.delete("todate");
            url.searchParams.delete("enddate");
            url.searchParams.delete("tzname");
            url.searchParams.delete("tzoffset");
        }
        window.location.href = url;
    } else if (globalCallback && window[globalCallback]) {
        window[globalCallback]({
            date: unixTimestamp,
            enddate: toUnixTimestamp,
            tzname: selectedTz,
            tzoffset: moment.tz(selectedTz.offset).utcOffset(),
        });
    }
}
