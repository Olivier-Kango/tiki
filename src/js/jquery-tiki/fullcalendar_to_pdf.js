// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

import moment from "moment";

$.fn.addFullCalendarPrint = function (buttonId, calendar) {
    let viewContainer = $(this);
    var calendarId = "#" + $(this).attr("id");
    if (!viewContainer) {
        console.warn(calendarId + " not found");
        return;
    }
    viewContainer.append($(buttonId));
    $(buttonId).show();
    // We need to remove previoud binds to avoid $(".icon-pdf").parent() to trigger page to PDF
    $(buttonId).off("click");
    $(buttonId).on("click", function (event) {
        event.preventDefault();
        var elementToPrint = $(calendarId + " .fc-view");
        $("html, body").animate({ scrollTop: 0 }, 0);
        setTimeout(function () {
            html2canvas(elementToPrint[0], {
                scrollY: 0,
                scrollX: 0,
            }).then(function (canvas) {
                var date = moment(calendar.getDate());
                var monthName = date.format("MMMM");
                var year = date.format("YYYY");
                var imgData = canvas.toDataURL("image/jpeg", 1.0);
                var imgWidth = 180;
                var pageHeight = 250;
                var imgHeight = (canvas.height * imgWidth) / canvas.width;
                var heightLeft = imgHeight;
                var doc = new jsPDF("p", "mm");
                doc.setFontSize(14);
                doc.text((210 - imgWidth) / 2, 20, monthName + " " + year);

                if (imgHeight > pageHeight) {
                    imgHeight = pageHeight;
                    imgWidth = (canvas.width * imgHeight) / canvas.height;
                }

                doc.addImage(imgData, "JPEG", (210 - imgWidth) / 2, 30, imgWidth, heightLeft > pageHeight ? pageHeight : heightLeft);

                doc.save(monthName + year + ".pdf");
            });
        }, 200);
    });
};
