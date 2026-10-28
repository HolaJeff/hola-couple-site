jQuery(function ($) {
    var defaultFormat = 'yy-mm-dd';

    $('.hw-datepicker').each(function () {
        $(this).datepicker({
            dateFormat: $(this).data('date-format') || defaultFormat
        });
    });
});
