
/*jslint browser: true, unparam: true */

function FUNCTION_PREFIX_Start() {
    "use strict";

    // console.log('Starting');
    var form, formId, iFrame, oldAction, oldTarget;

    formId = '{FORM_ID}';

    iFrame = document.createElement('iframe');
    iFrame.setAttribute('name', '__iframe___{FORM_ID}');
    iFrame.setAttribute('style', 'position: absolute; left: -100px; top: -100px; width: 10px; height: 10px; overflow: hidden;');
    document.getElementsByTagName('body')[0].appendChild(iFrame);

    if (formId) {
        form = document.getElementById(formId);

        if (form) {
            oldAction = form.action;
            oldTarget = form.target;

            form.action = '{URL_START_RUN}';
            form.target = '__iframe___{FORM_ID}';
            form.submit();

            // Restore normal form workings
            form.action = oldAction;
            form.target = oldTarget;
            return;
        }
    }

    iFrame.src = '{URL_START_RUN}';
}

function FUNCTION_PREFIX_Update(data) {
    "use strict";

    var inner, main, text;

    main = jQuery("{PANEL_ID}");
    main.progressbar({value: data.percent});

    inner = main.find('{TEXT_ID}');
    if (inner) {
        text = data.percent + '%';
        if (data.text) {
            text = text + ' ' + data.text;
        }
        inner.html(text);
    }
}

function FUNCTION_PREFIX_Finish() {
    "use strict";

    var form, formId, inner, main, url;

    formId = '{FORM_ID}';

    main = jQuery("{PANEL_ID}");
    main.progressbar({value: 100});

    inner = main.find('{TEXT_ID}');
    if (inner) {
        inner.empty();
        inner.append('100% Done!');
    }

    url = '{URL_FINISH}';

    if (formId) {
        form = document.getElementById(formId);

        if (form) {
            if (url) {
                form.action = url;
            }
            form.submit();
            return;
        }
    }

    if (url) {
        location.href = url;
    }
}

if (__AUTOSTART__) {
    jQuery().ready(FUNCTION_PREFIX_Start());
}
