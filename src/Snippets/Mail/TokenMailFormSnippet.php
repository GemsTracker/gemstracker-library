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

use Gems\Util\Translated;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;

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
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addFormElements(FormBridgeInterface $bridge, FullDataInterface $model)
    {
        $bridge->addHtml('to', 'label', $this->_('To'));

        $bridge->addExhibitor('track', array('label' => $this->_('Track')));
        $bridge->addExhibitor('round', array('label' => $this->_('Round')));
        $bridge->addExhibitor('survey', array('label' => $this->_('Survey')));
        $bridge->addExhibitor('last_contact', array('label' => $this->_('Last contact'), 'formatFunction' => $this->translatedUtil->formatDateNever));

        parent::addFormElements($bridge,$model);
    }
}