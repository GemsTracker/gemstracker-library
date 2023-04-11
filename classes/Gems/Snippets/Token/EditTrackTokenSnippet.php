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

use DateTimeImmutable;
use DateTimeInterface;

use Gems\Date\Period;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model;
use Gems\Tracker;
use Gems\Tracker\Snippets\EditTokenSnippetAbstract;
use MUtil\Model\Type\ChangeTracker;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditTrackTokenSnippet extends EditTokenSnippetAbstract
{
    protected int $currentUserId;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        Tracker $tracker,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper, $tracker);
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param FormBridgeInterface $bridge
     * @param FullDataInterface $dataModel
     */
    protected function addBridgeElements(FormBridgeInterface $bridge, FullDataInterface $dataModel)
    {
        $dataModel->set('reset_mail', [
            'label'        => $this->_('Reset sent mail'),
            'description'  => $this->_('Set to zero mails sent'),
            'elementClass' => 'Checkbox',                
        ]);
        
        $onOffFields = array('gr2t_track_info', 'gto_round_description', 'grc_description');
        foreach ($onOffFields as $field) {
            if (! (isset($this->formData[$field]) && $this->formData[$field])) {
                $dataModel->set($field, 'elementClass', 'None');
            }
        }

        $metaModel = $this->getModel()->getMetaModel();
        $this->initItems($metaModel);

        //And any remaining item
        $this->addItems($bridge, $this->_items);
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
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems(MetaModelInterface $metaModel)
    {
        if (is_null($this->_items)) {
            $this->_items = array_merge(
                    array(
                        'gto_id_respondent',
                        'gr2o_patient_nr',
                        'respondent_name',
                        'gto_id_organization',
                        'gtr_track_name',
                        'gr2t_track_info',
                        'gto_round_description',
                        'gsu_survey_name',
                        'ggp_name',
                        'gro_valid_for_unit',
                        'gto_valid_from_manual',
                        'gto_valid_from',
                        'gto_valid_until_manual',
                        'gto_valid_until',
                        'gto_comment',
                        'gto_mail_sent_date',
                        'gto_mail_sent_num',
                        'reset_mail',
                        'gto_completion_time',
                        'grc_description',
                        'gto_changed',
                        'assigned_by',
                        ),
                    $metaModel->getMeta(ChangeTracker::HIDDEN_FIELDS, array())
                    );
            if (! $this->createData) {
                array_unshift($this->_items, 'gto_id_token');
            }
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    public function saveData(): int
    {
        $model = $this->getModel();

        // \MUtil\EchoOut\EchoOut::track($this->formData);
        if ($this->formData['gto_valid_until'] && Period::isDateType($this->formData['gro_valid_for_unit'])) {
            // Make sure date based units are valid until the end of the day.
            $date = $this->formData['gto_valid_until'];
            if (!$date instanceof DateTimeInterface) {
                $date = DateTimeImmutable::createFromFormat(
                    $model->get('gto_valid_until', 'dateFormat'),
                    $this->formData['gto_valid_until']
                );
            }
            $this->formData['gto_valid_until'] = $date->setTime(23, 59, 59);
        }
        
        if (isset($this->formData['reset_mail']) && $this->formData['reset_mail']) {
            $this->formData['gto_mail_sent_date'] = null;
            $this->formData['gto_mail_sent_num']  = 0;
        } else {
            // This value is not editable so it should not be saved.
            unset($this->formData['gto_mail_sent_date'], $this->formData['gto_mail_sent_num']);
        }
        unset($this->formData['reset_mail']);

        // Save the token using the model
        parent::saveData();
        // $this->token->setValidFrom($this->formData['gto_valid_from'], $this->formData['gto_valid_until'], $this->loader->getCurrentUser()->getUserId());

        // \MUtil\EchoOut\EchoOut::track($this->formData);

        // Refresh (NOT UPDATE!) token with current form data
        $updateData['gto_valid_from']         = $this->formData['gto_valid_from'];
        $updateData['gto_valid_from_manual']  = $this->formData['gto_valid_from_manual'];
        $updateData['gto_valid_until']        = $this->formData['gto_valid_until'];
        $updateData['gto_valid_until_manual'] = $this->formData['gto_valid_until_manual'];
        $updateData['gto_comment']            = $this->formData['gto_comment'];

        $this->token->refresh($updateData);

        $respTrack = $this->token->getRespondentTrack();
        $userId    = $this->currentUserId;
        $changed   = $respTrack->checkTrackTokens($userId, $this->token);

        if ($changed) {
            $this->addMessage(sprintf($this->plural(
                    '%d token changed by recalculation.',
                    '%d tokens changed by recalculation.',
                    $changed
                    ), $changed));
        }
        
        return $changed;
    }
}
