<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class TokenMailFormSnippet extends \Gems\Snippets\Mail\MailFormSnippet
{
    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addFormElements(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $bridge->addHtml('to', 'label', $this->_('To'));

        $bridge->addExhibitor('track', array('label' => $this->_('Track')));
        $bridge->addExhibitor('round', array('label' => $this->_('Round')));
        $bridge->addExhibitor('survey', array('label' => $this->_('Survey')));
        $bridge->addExhibitor('last_contact', array('label' => $this->_('Last contact'), 'formatFunction' => $this->util->getTranslated()->formatDateNever));

        parent::addFormElements($bridge,$model);
    }
}