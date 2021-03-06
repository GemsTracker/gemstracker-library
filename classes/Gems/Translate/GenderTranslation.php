<?php


namespace Gems\Translate;


use Gems\Event\EventDispatcher;

trait GenderTranslation
{
    /**
     * @var EventDispatcher
     */
    protected $event;

    /**
     * @var \Zend_Translate[]
     */
    protected $genderTranslates;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null, $gender = null)
    {
        if ($gender && isset($this->genderTranslates[$gender])) {
            $adapter = $this->genderTranslates[$gender]->getAdapter();
            if ($adapter instanceof \Zend_Translate_Adapter && $adapter->isTranslated($text, false, $locale)) {
                return $adapter->_($text, $locale);
            }
        }

        return $this->translateAdapter->_($text, $locale);
    }

    /**
     * Function that checks the setup of this class/traight
     *
     * This function is not needed if the variables have been defined correctly in the
     * source for this object and theose variables have been applied.
     *
     * return @void
     */
    protected function initTranslateable()
    {
        if ($this->translateAdapter instanceof \Zend_Translate_Adapter) {
            // OK
            $this->initGenderTranslations();
            return;
        }

        if ($this->translate instanceof \Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            $this->initGenderTranslations();
            return;
        }

        if ($this->translate instanceof \Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            $this->initGenderTranslations();
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new \MUtil_Translate_Adapter_Potemkin();

        $this->initGenderTranslations();
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }


    public function initGenderTranslations()
    {
        $this->genderTranslates = [
            'F' => $this->getGenderAdapter('female'),
            'M' => $this->getGenderAdapter('male'),
        ];
    }

    protected function getGenderAdapter($gender)
    {
        $language = $this->translate->getAdapter()->getLocale();

        /*
         * Scan for files with -<languagecode> and disable notices when the requested
         * language is not found
         */
        $options = [
            'adapter'         => 'gettext',
            'content'         => GEMS_LIBRARY_DIR . '/languages/gender/'.$gender,
            'disableNotices'  => true,
            'locale'          => $language,
            'scan'            => \Zend_Translate::LOCALE_FILENAME
        ];

        $translate = new \Zend_Translate($options);

        // If we don't find the needed language, use a fake translator to disable notices
        if (! $translate->isAvailable($language)) {
            $translate = \MUtil_Translate_Adapter_Potemkin::create();
        }

        // Add other languages through Event (e.g. Modules)
        $event = new \Gems\Event\Application\ZendTranslateEvent($translate, $language, $options);
        $this->event->dispatch($event, $event::NAME . '.gender.' . $gender);

        //Now if we have a project specific language file, add it to the event
        $projectLanguageDir = APPLICATION_PATH . '/languages/gender/' . $gender;
        if (file_exists($projectLanguageDir)) {
            $event->addTranslationByDirectory($projectLanguageDir);
        }
        $translate = $event->getTranslate();

        return $translate;
    }
}
