
/*jslint browser: true, unparam: true */

// Creating the widget
jQuery.widget("ui.pullProgressPanel", {

    // default options
    options: {
        // finishUrl: the request url
        // formId: an optional form id for post parameters
        // panelId: text id:,
        // runUrl: the request url
        // targetId: search for the element whose content is replaced
        timeout: 60000
    },

    _init: function () {
        "use strict";

        this.progressTarget = jQuery(this.options.panelId);
        if (this.progressTarget.length) {
            this.textTarget = this.progressTarget.find(this.options.targetId);
            // this.textTarget = this.find(this.options.targetId);

            if (this.textTarget.length) {
                this.start();
            } else {
                alert('Did not find the text element: "' + this.options.targetId + '" in element id: "' + this.options.panelId + '".');
            }
        } else {
            alert('Did not find the panel id: "' + this.options.panelId + '".');
        }
    },

    complete: function (request, status) {
        "use strict";

        this.request = null;
    },

    error: function (request, status, error) {
        "use strict";

        // alert('Communication error: ' + status);
        this.progressTarget.after('<h3>Communication error</h3><p><strong>' + status + '</strong><br/>' + request.responseText + '</p>');
        // console.log(request);
    },

    progressTarget: null,

    start: function () {
        "use strict";

        var fd, form, self, verb;

        if (null === this.request) {
            if (this.options.runUrl) {
                fd   = null;
                self = this;
                verb = "GET";

                if (this.options.formId) {
                    form = jQuery("form#" + this.options.formId);
                    if (form.size()) {
                        fd   = form.serialize();
                        verb = "POST";
                    }
                }

                this.request = jQuery.ajax({
                    url:      this.options.runUrl,
                    type:     verb,
                    dataType: "json",
                    processData: "GET" === verb,
                    data:     fd,
                    error:    function (request, status, error) {self.error(request, status, error); },
                    complete: function (request, status) {self.complete(request, status); },
                    success:  function (data, status, request) {self.success(data, status, request); }
                });
            } else {
                alert("No runUrl specified.");
            }
        }
    },

    success: function (data, status, request) {
        "use strict";

        var form, text;

        if (! data) {
            data.finished = false;
            data.percent = 'xx';
            data.text = 'An error occured, no data was returned, check the error logs.';
        }
        
        // console.log(data);
        if (data.finished) {
            data.percent = 100;
            data.text = false;
        }

        // For some reason the next two lines are both needed for the code to work
        //this.progressTarget.progressbar("option", "value", data.percent);
        this.progressTarget.progressbar({value: data.percent});

        text = data.percent + '%';
        if (data.text) {
            text = text + ' ' + data.text;
        }

        this.textTarget.html(text);

        if (data.finished) {
            if (this.options.formId) {
                form = jQuery("form#" + this.options.formId);
                if (form.size()) {
                    if (this.options.finishUrl.length) {
                        form.attr('action', this.options.finishUrl);
                    }
                    form.submit();
                    return;
                }
            }
            if (this.options.finishUrl.length) {
                location.href = this.options.finishUrl;
            }
        } else {
            this.request = null;
            this.start();
        }
    },

    textTarget: null,

    request: null
});

function FUNCTION_PREFIX_Start() {
    "use strict";

    jQuery("{PANEL_ID}").pullProgressPanel({
        "finishUrl": "{URL_FINISH}",
        "formId":    "{FORM_ID}",
        "panelId":   "{PANEL_ID}",
        "runUrl":    "{URL_START_RUN}",
        "targetId":  "{TEXT_ID}"
    });
}

if (__AUTOSTART__) {
    jQuery().ready(FUNCTION_PREFIX_Start());
}
