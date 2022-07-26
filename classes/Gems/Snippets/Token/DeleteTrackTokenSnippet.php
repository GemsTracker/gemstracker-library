<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
     * @var \Gems\Tracker\Token
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
     * @return \MUtil\Model\ModelAbstract
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
     * @return \Gems\Menu\MenuList
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

    protected function loadForm()
    {
        parent::loadForm();

        if ($this->fixedReceptionCode == 'redo' && $this->token->isExpired()) {
            $messenger = $this->getMessenger();
            $messenger->addMessage($this->_("Watch out! Token is currently expired and you won't be able to answer it unless you change the valid from date."), 'danger');
        }
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
                $newComment = sprintf($this->_('Redo of token %s.'), $this->token->getTokenId());
                if ($this->formData['gto_comment']) {
                    $newComment .= "\n\n";
                    $newComment .= $this->_('Old comment:');
                    $newComment .= "\n";
                    $newComment .= $this->formData['gto_comment'];
                }

                // Fixing #582: autoextend the date
                $now = new \MUtil\Date();
                if ($this->token->getValidUntil() && $this->token->getValidUntil()->isEarlier($now)) {
                    $otherValues = [
                        'gto_valid_until' => $now->addDay(7),
                        'gto_valid_until_manual' => 1,
                        ];
                } else {
                    $otherValues = [];
                }

                $this->_replacementTokenId = $this->token->createReplacement($newComment, $userId, $otherValues);

                // Create a link for the old token
                $oldToken = strtoupper($this->token->getTokenId());
                $menuItem = $this->menu->findAllowedController('track', 'show');
                if ($menuItem) {
                    $paramSource['gto_id_token']       = $this->token->getTokenId();
                    $paramSource[\Gems\Model::ID_TYPE] = 'token';

                    $href = $menuItem->toHRefAttribute($paramSource);
                    if ($href) {
                        // \MUtil\EchoOut\EchoOut::track($oldToken);
                        $link = \MUtil\Html::create('a', $href, $oldToken);

                        $oldToken = $link->setView($this->view);
                    }
                }

                // Tell what the user what happened
                $this->addMessage(new \MUtil\Html\Raw(sprintf(
                        $this->_('Created this token %s as replacement for token %s.'),
                        strtoupper($this->_replacementTokenId),
                        $oldToken
                        )));

                // Lookup token
                $newToken = $this->loader->getTracker()->getToken($this->_replacementTokenId);

                // Make sure the Next token is set right
                $this->token->setNextToken($newToken);

                // Copy answers when requested.
                /* Copy moved to \Gems_Track_Token->getUrl()
                if ($code->hasRedoCopyCode()) {
                    $newToken->setRawAnswers($this->token->getRawAnswers());
                }
                */
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
                \MUtil\Model::REQUEST_ID       => $tokenId,
                );
        }

        return $this;
    }
}
