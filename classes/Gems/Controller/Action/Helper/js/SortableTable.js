/*
 * Replacement variables: 
 *  jQuery
 *  AJAXURL
 *  IDPARAM
 */

// Return a helper with preserved width of cells
var fixHelper = function (e, ui) {
    ui.children().each(function () {
        $(this).width($(this).width());
    });
    return ui;
};

function getURLParameter(url, name) {
    return (RegExp("/" + name + "/" + "(.+?)(/|$)").exec(url) || [, null])[1];
}


jQuery("#sort").click(function () {
    jQuery("#sort").toggle();
    jQuery("#sort-ok").toggle();
    jQuery("#sort-cancel").toggle();

    $("table.browser.table tbody").sortable({
        helper: fixHelper,
        placeholder: "ui-sortable-placeholder"
    }).disableSelection();
});

jQuery("#sort-ok").click(function () {
    var sortables = [];
    jQuery("table.browser.table:not(.fixed) tbody tr").each(function () {
        href = jQuery(this).find("td.table-button").first().find("a").attr("href");
        id = getURLParameter(href, "IDPARAM");
        sortables.push(id);
    });
    jQuery.ajax({
        url: "AJAXURL",
        type: "POST",
        dataType: "html",
        data: {ids: sortables},
        error: function (request, status, error) {
            errorContainer = $("#error");
            if (errorContainer.length == 0) {
                // Insert error container
                $("body").append("<div id=\"error\"></div>");
            }
            $("#error").html(request.responseText);
            $("#error").dialog({
                modal: true,
                width: 200,
                position: {my: "left top", at: "left top", of: "#main"}
            })
        },
        success: function (data, status, request) {
            jQuery("#AUTO_SEARCH_TEXT_BUTTON").click();
        }
    })
});

jQuery("#sort-cancel").click(function () {
    jQuery("#AUTO_SEARCH_TEXT_BUTTON").click();
});
