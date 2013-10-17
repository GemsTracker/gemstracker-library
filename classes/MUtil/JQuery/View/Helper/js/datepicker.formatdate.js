/*jslint browser: true*/
/*global jQuery */

(function () {
    "use strict";

    var datePick;

    datePick = jQuery('#ELEM_ID');

    // Make sure the correct format is applied when the field has been edited
    datePick.blur(function () {
        var dateformat, dateused;

        dateformat = datePick.PICKER('option', 'dateFormat');
        dateused = datePick.attr('value');
        dateused = jQuery.PICKER.parseDate(dateformat, dateused);
        datePick.attr('value', jQuery.PICKER.formatDate(dateformat, dateused));
    });
}());
