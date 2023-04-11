<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail\Log;

use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Repository\TokenRepository;
use Gems\Snippets\ModelTableSnippet;
use Gems\User\Mask\MaskRepository;
use Gems\User\User;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class MailLogBrowseSnippet extends ModelTableSnippet
{
    protected User $currentUser;
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected MaskRepository $maskRepository,
        protected TokenRepository $tokenRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        if ($dataModel->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        $keys = $this->getRouteMaps($dataModel);
        if ($this->showMenu) {
            $showMenuItems = $this->getShowUrls($bridge, $keys);

            foreach ($showMenuItems as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Show');
                }
                $bridge->addItemLink(\Gems\Html::actionLink($linkParts['url'], $linkParts['label']));
            }
        }

        // Newline placeholder
        $br = Html::create('br');
        $by = Html::raw($this->_(' / '));
        $sp = Html::raw('&nbsp;');

        if ($this->maskRepository->areAllFieldsMaskedWhole('respondent_name', 'grs_surname_prefix', 'grco_address')) {
            $bridge->addMultiSort('grco_created',  $br, 'gr2o_patient_nr', $br, 'gtr_track_name');
        } else {
            $bridge->addMultiSort('grco_created',  $br, 'gr2o_patient_nr', $sp, 'respondent_name', $br, 'grco_address', $br, 'gtr_track_name');
        }
        $bridge->addMultiSort('grco_id_token', $br, 'assigned_by',     $br, 'grco_sender',     $br, 'gsu_survey_name');
        $bridge->addMultiSort('status',        $by, 'filler',          $br, 'grco_topic');

        if ($this->showMenu) {
            $params = [
                Model::REQUEST_ID1 => $bridge->getLate('gr2o_patient_nr'),
                Model::REQUEST_ID2 => $bridge->getLate('gr2o_id_organization'),
                Model::REQUEST_ID => $bridge->getLate('gto_id_token'),
            ];
            $url = $this->menuHelper->getLateRouteUrl('respondent.tracks.show', $params);

            $label = Html::create('strong', $this->_('+'));
            $bridge->addItemLink(\Gems\Html::actionLink($url['url'], $label));
        }
        $bridge->getTable()->appendAttrib('class', 'compliance');

        $tbody = $bridge->tbody();
        $td = $tbody[0][0];
        /*$td->appendAttrib(
                'class',
                \Zalt\Late\Late::method($this->tokenRepository, 'getStatusClass', $bridge->getLazy('status'))
                );*/
    }
}
