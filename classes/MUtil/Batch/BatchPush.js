
function FUNCTION_PREFIX_Start()
{
    // console.log('Starting');
    var iFrame;

    iFrame = document.createElement('iframe');
    iFrame.setAttribute('style', 'position: absolute; left: -100px; top: -100px; width: 10px; height: 10px; overflow: hidden;');
    document.getElementsByTagName('body')[0].appendChild(iFrame);
    iFrame.src = '{URL_START_RUN}';
}

function FUNCTION_PREFIX_Update(data)
{
    var main, inner, text;

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

function FUNCTION_PREFIX_Finish()
{
    var main, inner, url;

    main = jQuery("{PANEL_ID}");
    main.progressbar({value: 100});

    inner = main.find('{TEXT_ID}');
    if (inner) {
        inner.empty();
        inner.append('100% Done!');
    }

    url = '{URL_FINISH}';
    if (url.length > 0) {
        location.href = url;
    }
}

if (__AUTOSTART__) {
    jQuery().ready(FUNCTION_PREFIX_Start());
}