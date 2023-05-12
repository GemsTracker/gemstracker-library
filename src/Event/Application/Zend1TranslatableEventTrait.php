<?php


namespace Gems\Event\Application;


trait Zend1TranslatableEventTrait
{
    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    public function setTranslatorAdapter(\Zend_Translate_Adapter $translateAdapter)
    {
        $this->translateAdapter = $translateAdapter;
    }

    /**
     * @return \Zend_Translate_Adapter
     */
    public function getTranslatorAdapter()
    {
        return $this->translateAdapter;
    }
}
