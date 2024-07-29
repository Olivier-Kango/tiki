// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$(function() {

    let listDirty = false;

    Sortable.create(document.querySelector('.surveyquestions tbody'), {
        onUpdate: function (event) {
            $(".save_list").show("fast").parent().show("fast");
            listDirty = true;
        }
    });

    $(window).on("beforeunload", function() {
        if (listDirty) {
            return tr("You have unsaved changes to your survey, are you sure you want to leave the page without saving?");
        }
    });

    $(".save_list").on("click", function(){

        var $ids = $(this).parent().find(".surveyquestions td.id");
        $(".surveyquestions").tikiModal(tr("Saving..."));

        var data = $ids.map(function () {
            return $(this).text();
        }).get().join();

        listDirty = false;
        $("input[name=questionIds]", "#reorderForm").val(data);
        $("#reorderForm").trigger("submit");

        return false;
    });

});

