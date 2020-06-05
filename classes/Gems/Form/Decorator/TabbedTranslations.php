<?php


class Gems_Form_Decorator_TabbedTranslations extends \Zend_Form_Decorator_Abstract
{
    /**
     * @param string $content
     * @return string
     * @throws Zend_Form_Decorator_Exception
     */
    public function render($content)
    {
        $element = $this->getElement();
        $id = $element->getId();
        $view = $element->getView();

        $js = $this->getJs($id);

        $view->headScript()->appendScript($js);

        return $content;
    }

    protected function getJs($id)
    {

        $locales = $this->getOption('locales');
        $defaultLocale = $this->getOption('defaultLocale');

        $tabs = '<ul class="translate-tabs nav nav-tabs">';
        if (isset($locales[$defaultLocale])) {
            $defaultLocaleName = $locales[$defaultLocale];
            unset($locales[$defaultLocale]);
            $tabs .= "<li data-target=\"none\" class=\"active\"><a>$defaultLocaleName</a>";
        }



        foreach($locales as $isoLang=>$translatedName) {
            $tabs .= "<li data-target=\"{$isoLang}\"><a>{$translatedName}</a>";
        }
        $tabs .= "<li data-target=\"all\"><a>All</a>";
        $tabs .= '</ul>';

        $js = "
            (function($) {
                var element = $('#{$id}');
                var form = element.closest('form');
                if (form.find('.translate-tabs').length === 0) {
                    form.prepend('".$tabs."');
                }
                console.log(element);
                console.log(form);
                
                element.find('.input-group').hide();
                
                form.on('click', '.translate-tabs li', function() {
                    let tab = $(this);
                    tab.siblings().removeClass('active');
                    tab.addClass('active');
                    
                    let target = tab.attr('data-target');
                    
                    if (target == 'all') {
                        element.find('.input-group').show();
                    } else if (target == 'none') {
                        element.find('.input-group').hide();
                    } else {
                        element.find('.input-group').hide();
                        element.find('input[type=hidden][value='+target+']').closest('.input-group').show();
                    }
                    
                });
                
            } (jQuery));
        ";

        return $js;
    }
}
