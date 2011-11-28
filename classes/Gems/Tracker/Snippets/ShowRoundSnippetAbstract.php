<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
class Gems_Tracker_Snippets_ShowRoundSnippetAbstract extends MUtil_Snippets_ModelVerticalTableSnippetAbstract
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
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Gems_Menu
     */
    public $menu;

    /**
     *
     * @var int Gems round id
     */
    protected $roundId;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var Gems_Util
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
     * @return MUtil_Model_ModelAbstract
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
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        if ($this->roundId) {
            $htmlDiv   = MUtil_Html::div();


            $htmlDiv->h3(sprintf($this->_('%s round'), $this->trackEngine->getName()));

            $table = parent::getHtmlOutput($view);
            $this->applyHtmlAttributes($table);

            $table->tfrow($this->getMenuList(), array('class' => 'centerAlign'));

            $htmlDiv[] = $table;

            return $htmlDiv;

        } else {
            $this->addMessage($this->_('No round specified.'));
        }
    }

    /**
     * overrule to add your own buttons.
     *
     * @return Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $source = new Gems_Menu_ParameterSource(array(
            'gro_id_track' => $this->trackId,
            'gro_id_round' => $this->trackEngine->getPreviousRoundId($this->roundId),
            'gtr_track_type' => $this->trackEngine->getTrackType()));

        $links->append($this->menu->getCurrent()->toActionLink(true, MUtil_Html::raw($this->_('&lt; Previous')), $source));
        $links->addCurrentParent($this->_('Cancel'));
        $links->addCurrentSiblings();

        $source->offsetSet('gro_id_round', $this->trackEngine->getNextRoundId($this->roundId));
        $links->append($this->menu->getCurrent()->toActionLink(true, MUtil_Html::raw($this->_('Next &gt;')), $source));

        return $links;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
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
            $this->roundId = $this->request->getParam(Gems_Model::ROUND_ID);
        }

        return $this->roundId && parent::hasHtmlOutput();
    }
}
