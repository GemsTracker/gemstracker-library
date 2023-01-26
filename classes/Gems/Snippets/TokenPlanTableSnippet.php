<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\MenuNew\MenuSnippetHelper;
use Gems\Repository\TokenRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Gems\Html;
use Zalt\Html\TableElement;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Displays a table for TokenModel
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
  */
class TokenPlanTableSnippet extends ModelTableSnippet
{
    public $filter = [];
    
    public $showActionLinks = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected TokenRepository $tokenRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $model
     * @return void
     */
    public function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        $HTML  = Html::create();
        
        if ($this->showActionLinks) {
            $rowClass = TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd');
        } else {
            $rowClass = 'odd';
        }
        $bridge->setDefaultRowClass($rowClass);
        
        if ($this->showActionLinks) {
            $bridge->addColumn($this->getTokenLinks($bridge), ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans            
        } else {
            $bridge->tr(['onlyWhenChanged' => true, 'class' => 'even']);
            $bridge->addColumn(' ');
        }
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addMultiSort('gr2o_patient_nr', $HTML->raw('; '), 'respondent_name');
        if ($this->showActionLinks) {
            //$bridge->addMultiSort('ggp_name', [$this->getActionLinks($bridge)]);
        } else {
            $bridge->addSortable('ggp_name');
        }

        $bridge->tr();
        if (!$this->showActionLinks) {
            $bridge->addColumn($this->getTokenLinks($bridge), ' ');
        }
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');

        $model->set('gr2t_track_info', 'tableDisplay', [Html::class, 'smallData']);
        $model->set('gto_round_description', 'tableDisplay', [Html::class, 'smallData']);
        $bridge->addMultiSort(
            'gtr_track_name', 'gr2t_track_info',
            $bridge->gtr_track_name->if($HTML->raw(' &raquo; ')),
            'gsu_survey_name', 'gto_round_description');

        $bridge->addSortable('assigned_by');
    }

    public function getActionLinks(\MUtil\Model\Bridge\TableBridge $bridge)
    {
        $buttons = [];
        // Get the other token buttons
//        if ($menuItems = $this->menu->findAll(array('controller' => 'track', 'action' => array('email', 'answer'), 'allowed' => true))) {
//            $buttons = $menuItems->toActionLink($this->request, $bridge);
//            $buttons->appendAttrib('class', 'rightFloat');
//        } else {
//            $buttons = null;
//        }
        // Add the ask button
//        if ($menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
//            $askLink = $menuItem->toActionLink($this->request, $bridge);
//            $askLink->appendAttrib('class', 'rightFloat');
//
//            if ($buttons) {
//                // Show previous link if show, otherwise show ask link
//                $buttons = array($buttons, $askLink);
//            } else {
//                $buttons = $askLink;
//            }
//        }

        return $buttons;
    }

    public function getTokenLinks(TableBridge $bridge)
    {
        return null; //$this->tokenRepository->getTokenStatusLinkForBridge($bridge, $this->menuHelper);
    }

    public function setSnippetOptions(SnippetOptions $snippetOptions): self
    {
        $options = $snippetOptions->getOptions();

        foreach($options as $name => $value) {
            if (str_starts_with($name, 'token')) {
                $name = lcfirst(substr($name, 5));
            }
            if (property_exists($this, $name)) {
                $this->setSnippetOption($name, $value);
            }
        }

        return $this;
    }
}