import { format } from "date-fns";
import moment from "moment-timezone/builds/moment-timezone-with-data-10-year-range.js";

/**
 * @param {Number} date timestamp
 * @returns {string}
 * @description
 * Returns the Unix timestamp of the given date.
 */
export function convertToUnixTimestamp(date) {
    return moment(date).unix();
}

/**
 * @param {Number} date timestamp
 * @param {string} tz
 * @returns {string}
 * @description
 * Returns the daylight difference between current browser's timezone and custom timzeone
 * between now and a specific timestamp. Used as a fix to vue-datepicker bug.
 */
export function daylightDiffAgainstBrowserTz(date, tz) {
    if (tz) {
        return (moment().tz(tz).utcOffset() - moment(date).tz(tz).utcOffset() + moment(date).utcOffset() - moment().utcOffset()) * 60;
    } else {
        return (moment(date).utcOffset() - moment().utcOffset()) * 60;
    }
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
        } else {
            url.searchParams.delete("todate");
            url.searchParams.delete("enddate");
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
