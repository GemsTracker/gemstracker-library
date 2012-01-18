

// Creating the widget
jQuery.widget("ui.pullProgressPanel", {

    // default options
    options: {
        autoStart: false,
        // target: the element whose content is replaced
        timeout: 2000
        // url: the request url
    },

    _init: function() {
        if (this.options.autoStart) {
            this.start();
        }
    },

    complete: function (request, status) {
        this.request = null;

        // Check for changes
        // - if the input field was changed since the last request
        //   filter() will search on the new value
        // - if the input field has not changed, then no new request
        //   is made.
        // this.start();
    },

    error: function (request, status) {
        console.log(status);
        /* if (request.status === 401) {
            location.href = location.href;
        } // */
    },

    start: function() {
        if (this.request == null) {
            if (this.options.url) {
                var self = this;
                this.request = jQuery.ajax({
                    url: this.options.url,
                    type: "GET",
                    dataType: "json",
                    // data: postData,
                    error: function(request, status, error) {self.error(request, status);},
                    complete: function(request, status) {self.complete(request, status);},
                    success: function(data, status, request) {self.success(data, status, request);}
                    });

            }
        }
    },

    success: function (data, status, request) {
        // console.log(stringdata);
        // data = jQuery.parseJSON(stringdata);
        console.log(data);

        text = data.percent + '%';
        if (data.text) {
            text = text + data.text;
        }

        jQuery(this.options.target).html(text);
    },

    request: null
});

jQuery(document).ready(function() {
    jQuery("#{ID}").pullProgressPanel({"url":"{URL_START}","autoStart":__AUTOSTART__,"target":"#{ID} {TEXT_TAG}.{TEXT_CLASS}"});
});

function FUNCTION_PREFIX_Finish()
{
    main = jQuery("#{ID}");
    main.progressbar( "option", "value", 100);

    inner = main.find('{TEXT_TAG}.{TEXT_CLASS}');
    if (inner) {
        inner.empty();
        inner.append('100% Done!');
    }
}
