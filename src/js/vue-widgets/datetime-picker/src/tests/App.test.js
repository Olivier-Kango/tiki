import { render, screen, fireEvent, within } from "@testing-library/vue";
import VueDatePicker from "@vuepic/vue-datepicker";
import moment from "moment";
import { describe, test, expect, vi, beforeEach } from "vitest";
import { h } from "vue";
import DatetimePicker, { DATA_TEST_ID, TEXT } from "../App.vue";
import * as Helpers from "../helpers/helpers";

vi.mock("@vuepic/vue-datepicker", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        default: vi.fn().mockImplementation(() => h("div", { "data-testid": DATA_TEST_ID.DATE_PICKER })),
    };
});

vi.mock("../helpers/helpers", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        convertToUnixTimestamp: vi.fn(),
        formatDate: vi.fn(),
        goToURLWithData: vi.fn(),
    };
});

describe("DatetimePicker", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe("render tests", () => {
        test("renders correctly with the given props", () => {
            const givenProps = {
                themeCss: "themes/default/css/default.css",
                inputName: "date",
                timestamp: "10",
                enableTimezonePicker: 1,
                enableTimePicker: 1,
                selectText: "Select",
                cancelText: "Cancel",
                timezone: "America/New_York",
                language: "en",
                rangePicker: true,
                toTimestamp: "20",
                timezoneInput: document.createElement("input"),
                dateTimeInput: document.createElement("input"),
                toDateTimeInput: document.createElement("input"),
            };

            // GIVEN that the call to convertToUnixTimestamp function will return some value
            const expectedConvertedTimestamp = "foo";
            vi.spyOn(Helpers, "convertToUnixTimestamp").mockReturnValue(expectedConvertedTimestamp);

            render(DatetimePicker, {
                props: {
                    ...givenProps,
                },
            });

            const expectedCssThemeLinkElement = screen.getByTestId(DATA_TEST_ID.CSS_THEME);
            expect(expectedCssThemeLinkElement).to.exist;
            expect(expectedCssThemeLinkElement.getAttribute("href")).toBe(givenProps.themeCss);

            const containerElement = screen.getByTestId(DATA_TEST_ID.CONTAINER);
            expect(containerElement).to.exist;

            const datePickerElement = within(containerElement).getByTestId(DATA_TEST_ID.DATE_PICKER);
            expect(datePickerElement).to.exist;
            expect(VueDatePicker).toHaveBeenCalledWith(
                expect.objectContaining({
                    modelValue: [givenProps.timestamp * 1000, givenProps.toTimestamp * 1000],
                    cancelText: givenProps.cancelText,
                    selectText: givenProps.selectText,
                    timezone: { timezone: givenProps.timezone },
                    range: givenProps.rangePicker,
                    locale: givenProps.language,
                    "enable-time-picker": givenProps.enableTimezonePicker,
                }),
                null
            );

            expect(Helpers.convertToUnixTimestamp).toHaveBeenCalledWith(
                givenProps.timestamp * 1000,
                Boolean(givenProps.enableTimezonePicker && !givenProps.timezone),
                givenProps.timezone
            );
            // expect the hidden input value to be whatever the convertToUnixTimestamp function returned
            expect(givenProps.dateTimeInput.value).toBe(expectedConvertedTimestamp);

            expect(Helpers.convertToUnixTimestamp).toHaveBeenCalledWith(
                givenProps.toTimestamp * 1000,
                Boolean(givenProps.enableTimezonePicker && !givenProps.timezone),
                givenProps.timezone
            );
            expect(givenProps.toDateTimeInput.value).toBe(expectedConvertedTimestamp);
            // convertToUnixTimestamp should have been called twice by now
            expect(Helpers.convertToUnixTimestamp).toHaveBeenCalledTimes(2);

            const timezoneContainerElement = within(containerElement).queryByTestId(DATA_TEST_ID.TIMEZONE_CONTAINER);
            expect(timezoneContainerElement).to.exist;

            const timezoneContainerLabelElement = within(timezoneContainerElement).getByTestId(DATA_TEST_ID.LABEL_TIMEZONE_CONTAINER);
            expect(timezoneContainerLabelElement).to.exist;
            expect(timezoneContainerLabelElement.textContent).toBe(TEXT.LABEL_TIMEZONE_CONTAINER);

            const timezoneSelectElement = within(timezoneContainerElement).getByTestId(DATA_TEST_ID.TIMEZONE_SELECT);
            expect(timezoneSelectElement).to.exist;
            const timezones = moment.tz.names();
            expect(timezoneSelectElement.children).toHaveLength(timezones.length);
            timezones.forEach((timezone, index) => {
                const optionElement = timezoneSelectElement.children[index];
                expect(optionElement.value).toBe(timezone);
                expect(optionElement.textContent).toEqual(expect.stringContaining(timezone));
            });

            // snapshot testing
            expect(containerElement).toMatchSnapshot();
        });

        test("renders correctly when optional props are not provided", () => {
            const givenProps = {
                dateTimeInput: document.createElement("input"),
            };

            render(DatetimePicker, {
                props: {
                    ...givenProps,
                },
            });

            expect(VueDatePicker).toHaveBeenCalledWith(
                expect.objectContaining({
                    cancelText: TEXT.LABEL_DEFAULT_CANCEL_BUTTON,
                    selectText: TEXT.LABEL_DEFAULT_SELECT_BUTTON,
                    range: false,
                    locale: "en",
                }),
                null
            );

            const containerElement = screen.getByTestId(DATA_TEST_ID.CONTAINER);
            expect(containerElement).to.exist;

            const timezoneContainerElement = within(containerElement).queryByTestId(DATA_TEST_ID.TIMEZONE_CONTAINER);
            expect(timezoneContainerElement).toBeNull();

            // snapshot testing
            expect(containerElement).toMatchSnapshot();
        });
    });

    describe("Action tests", () => {
        test.each([
            ["the range picker is enabled", [new Date("2021-01-01"), new Date("2021-01-02")], true],
            ["the range picker is disabled", [new Date("2021-01-01")], false],
        ])(
            "should update the hidden timestamp inputs when the date picker value changes and %s and call the emitValueChange prop",
            async (_, givenUpdatedDates, rangePicker) => {
                VueDatePicker = {
                    emits: ["update:modelValue"],
                    setup(props, { emit }) {
                        const handleClick = () => emit("update:modelValue", givenUpdatedDates);
                        return () => h("div", {}, h("button", { onClick: handleClick }, "Select date"));
                    },
                };
                const givenProps = {
                    emitValueChange: vi.fn(),
                    dateTimeInput: document.createElement("input"),
                    toDateTimeInput: document.createElement("input"),
                    rangePicker,
                };
                render(DatetimePicker, {
                    props: givenProps,
                });

                const pickDateButton = screen.getByText("Select date");
                await fireEvent.click(pickDateButton);

                givenUpdatedDates.forEach((date, index) => {
                    expect(Helpers.convertToUnixTimestamp).toHaveBeenCalledWith(date, expect.anything(), expect.anything());
                    expect(givenProps.emitValueChange).toHaveBeenCalledWith({
                        value: expect.anything(),
                        unixTimestamp: Helpers.convertToUnixTimestamp.mock.results[index].value,
                        toUnixTimestamp: Helpers.convertToUnixTimestamp.mock.results[index].value,
                        timezone: expect.anything(),
                    });
                });

                expect(givenProps.dateTimeInput.value).toBe(Helpers.convertToUnixTimestamp.mock.results[0].value);
                if (rangePicker) {
                    expect(givenProps.toDateTimeInput.value).toBe(Helpers.convertToUnixTimestamp.mock.results[1].value);
                }
            }
        );

        test.each([["goToUrlOnChange"], ["globalCallback"]])(
            "should call the goToURLWithData helper function when the date picker value changes and the prop %s is set",
            async (prop) => {
                const givenUpdatedDate = new Date("2021-01-01");
                const givenProps = {
                    [prop]: "foo",
                    timestamp: "10",
                    toTimestamp: "20",
                    timezone: "America/New_York",
                    dateTimeInput: document.createElement("input"),
                };
                VueDatePicker = {
                    emits: ["update:modelValue"],
                    setup(props, { emit }) {
                        const handleClick = () => emit("update:modelValue", [givenUpdatedDate]);
                        return () => h("div", {}, h("button", { onClick: handleClick }, "Select date"));
                    },
                };
                render(DatetimePicker, {
                    props: givenProps,
                });

                const pickDateButton = screen.getByText("Select date");
                await fireEvent.click(pickDateButton);

                expect(Helpers.goToURLWithData).toHaveBeenCalledWith(
                    [givenUpdatedDate],
                    givenProps.goToUrlOnChange,
                    expect.anything(),
                    expect.anything(),
                    expect.anything(),
                    givenProps.globalCallback
                );
            }
        );

        test("should render the updated date when the timezone select value changes and call the emitValueChange prop", async () => {
            VueDatePicker = vi.fn();
            const givenProps = {
                enableTimezonePicker: 1,
                timezone: "foo",
                timezoneFieldName: "timezone",
                emitValueChange: vi.fn(),
                dateTimeInput: document.createElement("input"),
                timezoneInput: document.createElement("input"),
            };

            render(DatetimePicker, {
                props: givenProps,
            });

            expect(VueDatePicker).toHaveBeenCalledWith(expect.objectContaining({ timezone: { timezone: givenProps.timezone } }), null);

            const timezoneSelectElement = screen.getByTestId(DATA_TEST_ID.TIMEZONE_SELECT);
            const selectedTimezone = timezoneSelectElement.children[1].value;
            await fireEvent.update(timezoneSelectElement, selectedTimezone);

            expect(VueDatePicker).toHaveBeenCalledWith(expect.objectContaining({ timezone: { timezone: selectedTimezone } }), null);
            expect(Helpers.convertToUnixTimestamp).toHaveBeenCalledWith(expect.anything(), expect.anything(), selectedTimezone);
            expect(givenProps.emitValueChange).toHaveBeenCalledWith({
                value: expect.anything(),
                unixTimestamp: expect.anything(),
                toUnixTimestamp: expect.anything(),
                timezone: selectedTimezone,
            });
        });
    });
});
