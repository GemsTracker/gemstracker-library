
function FUNCTION_PREFIX_Start()
{
    var iFrame = document.createElement('iframe');
    iFrame.setAttribute('style', 'position: absolute; left: -100px; top: -100px; width: 10px; height: 10px; overflow: hidden;');
    // iFrame.setAttribute('style', 'position: absolute; left: 0px; top: 0px; width: 100px; height: 100px; overflow: hidden;');
    document.getElementsByTagName('body')[0].appendChild(iFrame);
    iFrame.src = '{URL}';
}

function FUNCTION_PREFIX_Update(data)
{
    main = jQuery("#{ID}");
    main.progressbar( "option", "value", data.percent);
    main.progressbar({
        value: data.percent
    });

    inner = main.find('.{TEXT_CLASS}');
    if (inner) {
        inner.empty();
        inner.append(data.percent + '%');
        if (data.text) {
            inner.append(data.text);
        }
    }
}

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


// FUNCTION_PREFIX_Start();

// jQuery().ready(FUNCTION_PREFIX_Update({percent: 20, text: 'Hi'}));
// jQuery().ready(FUNCTION_PREFIX_Update({percent: 20, text: ''}));
jQuery().ready(FUNCTION_PREFIX_Start());
