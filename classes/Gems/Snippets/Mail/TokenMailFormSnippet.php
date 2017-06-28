<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Mail_TokenMailFormSnippet extends \Gems_Snippets_Mail_MailFormSnippet
{
    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $bridge->addHtml('to', 'label', $this->_('To'));

        $bridge->addExhibitor('track', array('label' => $this->_('Track')));
        $bridge->addExhibitor('round', array('label' => $this->_('Round')));
        $bridge->addExhibitor('survey', array('label' => $this->_('Survey')));
        $bridge->addExhibitor('last_contact', array('label' => $this->_('Last contact'), 'formatFunction' => $this->util->getTranslated()->formatDateNever));

        parent::addFormElements($bridge,$model);
    }
}