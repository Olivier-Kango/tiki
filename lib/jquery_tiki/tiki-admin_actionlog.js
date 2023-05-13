// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
(function ($) {

    $(document).on('click', '#select_all_recorded', function () {
        if ($(this).is(':checked')) {
            $("div.all_recorded input:visible:not(:checked)").click();
        } else {
            $("div.all_recorded input:visible:checked").click();
        }
    });

    $(document).on('click', '#select_all_reported', function () {
        if ($(this).is(':checked')) {
            $("div.all_reported input:visible:not(:checked)").click();
        } else {
            $("div.all_reported input:visible:checked").click();
        }
    });

}(jQuery));
