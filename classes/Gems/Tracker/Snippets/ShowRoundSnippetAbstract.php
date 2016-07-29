<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Short description for class
 *
 * Long description for class (if any)...
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Snippets_ShowRoundSnippetAbstract extends \MUtil_Snippets_ModelVerticalTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     *
     * @var int Gems round id
     */
    protected $roundId;

    /**
     * Show menu buttons below data
     *
     * @var boolean
     */
    protected $showMenu = true;

    /**
     * Show title above data
     *
     * @var boolean
     */
    protected $showTitle = true;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->trackEngine->getRoundModel(true, 'show');
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->roundId) {
            $htmlDiv   = \MUtil_Html::div();

            if ($this->showTitle) {
                $htmlDiv->h3(sprintf($this->_('%s round'), $this->trackEngine->getName()));
            }

            $table = parent::getHtmlOutput($view);
            $this->applyHtmlAttributes($table);

            // Make sure deactivated rounds are show as deleted
            foreach ($table->tbody() as $tr) {
                $skip = true;
                foreach ($tr as $td) {
                    if ($skip) {
                        $skip = false;
                    } else {
                        $td->appendAttrib('class', $table->getRepeater()->row_class);
                    }
                }
            }

            if ($this->showMenu) {
                $table->tfrow($this->getMenuList(), array('class' => 'centerAlign'));
            }

            $htmlDiv[] = $table;

            return $htmlDiv;

        } else {
            $this->addMessage($this->_('No round specified.'));
        }
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $source = new \Gems_Menu_ParameterSource(array(
            'gro_id_track' => $this->trackId,
            'gro_id_round' => $this->trackEngine->getPreviousRoundId($this->roundId),
            ));

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil_Html::raw($this->_('&lt; Previous')), $source));
        $links->addCurrentParent($this->_('Cancel'));
        $links->addCurrentChildren();
        $links->addCurrentSiblings();

        $source->offsetSet('gro_id_round', $this->trackEngine->getNextRoundId($this->roundId));
        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil_Html::raw($this->_('Next &gt;')), $source));

        return $links;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->trackEngine && (! $this->trackId)) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        if ($this->trackId) {
            // Try to get $this->trackEngine filled
            if (! $this->trackEngine) {
                // Set the engine used
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

        } else {
            return false;
        }

        if (! $this->roundId) {
            $this->roundId = $this->request->getParam(\Gems_Model::ROUND_ID);
        }

        return $this->roundId && parent::hasHtmlOutput();
    }
}
