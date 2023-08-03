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

use Carbon\CarbonImmutable;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Model\TokenModel;
use Gems\Tracker\Token;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessageStatus;
use Zalt\Message\MessengerInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
    protected ?string $_replacementTokenId;

    /**
     * @var Token|null
     */
    protected ?Token $_replacementToken = null;

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected array $editItems = ['gto_comment'];

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected array $exhibitItems = [
        'gto_id_token', 'gr2o_patient_nr', 'respondent_name', 'gtr_track_name', 'gr2t_track_info', 'gsu_survey_name',
        'gto_round_description', 'ggp_name', 'gto_valid_from', 'gto_valid_until', 'gto_completion_time',
        ];

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected array $hiddenItems = ['gto_id_organization', 'gto_id_respondent', 'gto_id_respondent_track'];

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected string $receptionCodeItem = 'gto_reception_code';

    /**
     * The token shown
     *
     * @var Token
     */
    protected Token $token;

    /**
     * Optional right to check for undeleting
     *
     * @var string
     */
    protected ?string $unDeleteRight = 'pr.token.undelete';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        CurrentUserRepository $currentUserRepository,
        protected MetaModelLoader $metaModelLoader,
        protected ReceptionCodeRepository $receptionCodeRepository,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper, $currentUserRepository);
    }

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
    protected function createModel(): FullDataInterface
    {
        if (TokenModel::$useTokenModel) {
            $model = $this->metaModelLoader->createModel(TokenModel::class);
        } else {
            $model = $this->token->getModel();
        }

        $model->set('gto_reception_code', [
            'label' => $model->get('grc_description', 'label'),
            'required' => true,
        ]);

        return $model;
    }

    /**
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList(): array
    {
        $items = [
            [
                'route' => 'respondent.show',
                'label' => $this->_('Show patient'),
            ],
            [
                'route' => 'respondent.tracks.index',
                'label' => $this->_('Show tracks'),
            ],
            [
                'route' => 'respondent.tracks.show-track',
                'label' => $this->_('Show track'),
                'parameters' => [
                    Model::RESPONDENT_TRACK => $this->token->getRespondentTrackId(),
                ],
            ],
            [
                'route' => 'respondent.tracks.show',
                'label' => $this->_('Show token'),
            ],
        ];

        $links = [];
        foreach($items as $item) {
            $params = $this->requestInfo->getRequestMatchedParams();
            if (isset($item['parameters'])) {
                $params += $item['parameters'];
            }
            $url = $this->menuHelper->getRouteUrl($item['route'], $params);
            if ($url) {
                $links[$item['route']] = Html::actionLink($url, $item['label']);
            }
        }

        return $links;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array
     */
    public function getReceptionCodes()
    {
        if ($this->unDelete) {
            return $this->receptionCodeRepository->getTokenRestoreCodes();
        }
        if ($this->token->isCompleted()) {
            return $this->receptionCodeRepository->getCompletedTokenDeletionCodes();
        }
        return $this->receptionCodeRepository->getUnansweredTokenDeletionCodes();
    }

    protected function loadForm()
    {
        parent::loadForm();

        if ($this->fixedReceptionCode == 'redo' && $this->token->isExpired()) {
            /**
             * @var $messenger StatusMessengerInterface
             */
            $messenger = $this->getMessenger();
            $messenger->addMessage($this->_("Watch out! Token is currently expired and you won't be able to answer it unless you change the valid from date."), MessageStatus::Danger, true);
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
     * Set what to do when the form is 'finished'.
     *
     * @return DeleteTrackTokenSnippet (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if (! $this->afterSaveRouteUrl) {
            if ($this->_replacementToken) {
                $urlParams = $this->_replacementToken->getMenuUrlParameters();
            } else {
                $urlParams = $this->token->getMenuUrlParameters();
            }

            $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl('respondent.tracks.show', $urlParams);
        }

        parent::setAfterSaveRoute();
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
        $code    = $this->receptionCodeRepository->getReceptionCode($newCode);

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
                if ($this->token->getValidUntil() && $this->token->getValidUntil()->getTimestamp() < time()) {
                    $now = new CarbonImmutable();
                    $otherValues = [
                        'gto_valid_until' => $now->addDays(7),
                        'gto_valid_until_manual' => 1,
                        ];
                } else {
                    $otherValues = [];
                }

                $this->_replacementTokenId = $this->token->createReplacement($newComment, $userId, $otherValues);

                // Create a link for the old token
                $oldToken = strtoupper($this->token->getTokenId());

                $url = $this->menuHelper->getRouteUrl('respondent.tracks.show', $this->token->getMenuUrlParameters());
                if ($url) {
                    // \MUtil\EchoOut\EchoOut::track($oldToken);
                    $link = Html::create('a', $url, $oldToken);
                }

                // Tell what the user what happened
                $this->addMessage(sprintf(
                        $this->_('Created this token %s as replacement for token %s.'),
                        strtoupper($this->_replacementTokenId),
                        strtoupper($this->token->getTokenId())
                        ));

                // Lookup token
                $this->_replacementToken = $this->tracker->getToken($this->_replacementTokenId);

                // Make sure the Next token is set right
                $this->token->setNextToken($this->_replacementToken);
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
}
