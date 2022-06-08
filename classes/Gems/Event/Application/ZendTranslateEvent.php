<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class ZendTranslateEvent extends Event
{
    const NAME = 'gems.translations.get';

    /**
     * @var string Current language
     */
    protected $language;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * ZendTranslateEvent constructor.
     *
     * @param \Zend_Translate $translate
     * @param string          $language
     * @param array           $options
     */
    public function __construct(\Zend_Translate $translate, $language, array $options)
    {
        $this->translate = $translate;
        $this->language  = $language;
        $this->options   = $options;
    }

    /**
     * Get the current translate object
     *
     * @return \Zend_Translate
     */
    public function getTranslate()
    {
        return $this->translate;
    }

    /**
     * Add translation options array to the current Translate
     * content: directory of the translation files. Required
     * disableNotices: disables notices. Default true
     *
     * @param array $options
     * @return bool
     * @throws \Zend_Translate_Exception
     */
    public function addTranslateOptions(array $options)
    {
        $options = $options + $this->options;
        if (!isset($options['content']) || !file_exists($options['content'])) {
            return false;
            // throw new \Zend_Translate_Exception('Translation directory not found');
        }

        $newTranslate = new \Zend_Translate($options);
        return $this->addTranslate($newTranslate);
    }

    /**
     * Add translation options array to the current Translate
     * content: directory of the translation files. Required
     * disableNotices: disables notices. Default true
     *
     * @param array $options
     * @return bool
     * @throws \Zend_Translate_Exception
     */
    public function addTranslationByDirectory($directory)
    {
        return $this->addTranslateOptions(['content' => $directory]);
    }

    /**
     * Add a \Zend_Translate object to the current Translate if the current language exists
     *
     * @param \Zend_Translate $newTranslate
     * @return bool
     */
    public function addTranslate(\Zend_Translate $newTranslate)
    {
        if ($newTranslate->isAvailable($this->language)) {
            $this->translate->addTranslation($newTranslate);
            return true;
        }
        return false;
    }
}
