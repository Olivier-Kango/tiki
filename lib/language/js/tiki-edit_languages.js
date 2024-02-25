$(function() {
    $('form#select_action .translation_action').each(function() {
        $(this).on("change", function() {
            $('form#select_action').submit();
        });
    });
});
