<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 24-apr-2014 14:46:04
 */
class TokenAnswerTranslator extends \Gems\Model\Translator\AnswerTranslatorAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Tracker\Token\TokenLibrary
     */
    protected $tokenLibrary;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if (! $this->tokenLibrary instanceof \Gems\Tracker\Token\TokenLibrary) {
            $this->tokenLibrary = $this->loader->getTracker()->getTokenLibrary();
        }
    }

    /**
     * If the token can be created find the respondent track for the token
     *
     * @param array $row
     * @return int|null
     */
    protected function findRespondentTrackFor(array $row)
    {
        return null;
    }

    /**
     * Find the token id using the passed row data and
     * the other translator parameters.
     *
     * @param array $row
     * @return string|null
     */
    protected function findTokenFor(array $row)
    {
        if (isset($row['token']) && $row['token']) {
            return $this->tokenLibrary->filter($row['token']);
        }

        return null;
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil\Model\ModelException
     */
    public function getFieldsTranslations()
    {
        if (! $this->_targetModel instanceof \MUtil\Model\ModelAbstract) {
            throw new \MUtil\Model\ModelTranslateException(sprintf('Called %s without a set target model.', __FUNCTION__));
        }
        $this->_targetModel->set('gto_id_token', 'label', $this->_('Token'),
                'import_descr', $this->loader->getTracker()->getTokenLibrary()->getFormat(),
                'required', true,
                'order', 2
                );

        return array('token' => 'gto_id_token') + parent::getFieldsTranslations();
    }

    /**
     * Get the error message for when no token exists
     *
     * @return string
     */
    public function getNoTokenError(array $row, $key)
    {
        return sprintf(
                $this->_('No token defined in row %s.'),
                implode(" / ", $row)
                );
    }
}
