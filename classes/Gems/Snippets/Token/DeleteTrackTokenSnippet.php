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
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;

/**
 * Snippet for editing reception code of token.
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class DeleteTrackTokenSnippet extends ChangeReceptionCodeSnippetAbstract
{
    /**
     * Replacement token after a redo delete
     *
     * @var string
     */
    protected $_replacementTokenId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $editItems = array('gto_comment');

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $exhibitItems = array(
        'gto_id_token', 'gr2o_patient_nr', 'respondent_name', 'gtr_track_name', 'gr2t_track_info', 'gsu_survey_name',
        'gto_round_description', 'ggp_name', 'gto_valid_from', 'gto_valid_until', 'gto_completion_time',
        );

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected $hiddenItems = array('gto_id_organization', 'gto_id_respondent', 'gto_id_respondent_track');

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected $receptionCodeItem = 'gto_reception_code';

    /**
     * The token shown
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * Optional right to check for undeleting
     *
     * @var string
     */
    protected $unDeleteRight = 'pr.token.undelete';

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // Do nothing, performed in setReceptionCode()
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->token->getModel();

        $model->set('gto_reception_code',
            'label',        $model->get('grc_description', 'label'),
            'required',     true);

        return $model;
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $controller = $this->request->getControllerName();

        $links->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'show', $this->_('Show token'));

        return $links;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array
     */
    public function getReceptionCodes()
    {
        $rcLib = $this->util->getReceptionCodeLibrary();

        if ($this->unDelete) {
            return $rcLib->getTokenRestoreCodes();
        }
        if ($this->token->isCompleted()) {
            return $rcLib->getCompletedTokenDeletionCodes();
        }
        return $rcLib->getUnansweredTokenDeletionCodes();
    }

    /**
     * Called after loadFormData() in loadForm() before the form is created
     *
     * @return boolean Are we undeleting or deleting?
     */
    public function isUndeleting()
    {
        return ! $this->token->getReceptionCode()->isSuccess();
    }

    /**
     * Hook performing actual save
     *
     * @param string $newCode
     * @param int $userId
     * @return $changed
     */
    public function setReceptionCode($newCode, $userId)
    {
        // Get the code object
        $code    = $this->util->getReceptionCode($newCode);

        // Use the token function as that cascades the consent code
        $changed = $this->token->setReceptionCode($code, $this->formData['gto_comment'], $userId);

        if ($code->isSuccess()) {
            $this->addMessage(sprintf($this->_('Token %s restored.'), $this->token->getTokenId()));
        } else {
            $this->addMessage(sprintf($this->_('Token %s deleted.'), $this->token->getTokenId()));

            if ($code->hasRedoCode()) {
                $newComment = sprintf($this->_('Redo of token %s.'), $this->tokenId);
                if ($this->formData['gto_comment']) {
                    $newComment .= "\n\n";
                    $newComment .= $this->_('Old comment:');
                    $newComment .= "\n";
                    $newComment .= $this->formData['gto_comment'];
                }

                $this->_replacementTokenId = $this->token->createReplacement($newComment, $userId);

                // Create a link for the old token
                $menuItem = $this->menu->findAllowedController($this->request->getControllerName(), 'show');
                if ($menuItem) {
                    $paramSource['gto_id_token']      = $this->tokenId;
                    $paramSource[\Gems_Model::ID_TYPE] = 'token';

                    $link = $menuItem->toActionLink($paramSource, strtoupper($this->tokenId), true);
                    $link->class = '';

                    $oldTokenUrl = $link->render($this->view);
                } else {
                    $oldTokenUrl = strtoupper($this->tokenId);
                }

                // Tell what the user what happened
                $this->addMessage(new \MUtil_Html_Raw(sprintf(
                        $this->_('Created replacement token %2$s for token %1$s.'),
                        $oldTokenUrl,
                        strtoupper($this->_replacementTokenId)
                        )));

                // Lookup token
                $newToken = $this->loader->getTracker()->getToken($this->_replacementTokenId);

                // Make sure the Next token is set right
                $this->token->setNextToken($newToken);

                // Copy answers when requested.
                if ($code->hasRedoCopyCode()) {
                    $newToken->setRawAnswers($this->token->getRawAnswers());
                }
            }
        }

        $respTrack = $this->token->getRespondentTrack();
        if ($nextToken = $this->token->getNextToken()) {
            if ($recalc = $respTrack->checkTrackTokens($userId, $nextToken)) {
                $this->addMessage(sprintf($this->plural(
                        '%d token changed by recalculation.',
                        '%d tokens changed by recalculation.',
                        $recalc
                        ), $recalc));
            }
        }

        return $changed;
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return DeleteTrackTokenSnippet (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $tokenId = $this->_replacementTokenId ? $this->_replacementTokenId : $this->token->getTokenId();
            $this->afterSaveRouteUrl = array(
                $this->request->getActionKey() => $this->routeAction,
                \MUtil_Model::REQUEST_ID       => $tokenId,
                );
        }

        return $this;
    }
}
