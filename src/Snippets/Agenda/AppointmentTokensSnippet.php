<?php

/**
 *
 * @package    Gems
 * @subpackage AppointmentTokensSnippet
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Engine\StepEngineAbstract;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentTokensSnippet
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 14, 2016 3:19:57 PM
 */
class AppointmentTokensSnippet extends \Gems\Snippets\Token\RespondentTokenSnippet
{
    public function __construct(
        SnippetOptions                      $snippetOptions,
        RequestInfo                         $requestInfo,
        MenuSnippetHelper                   $menuHelper,
        TranslatorInterface                 $translate,
        CurrentUserRepository               $currentUserRepository,
        MaskRepository                      $maskRepository,
        MetaModelLoader                     $metaModelLoader,
        Tracker                             $tracker,
        TokenRepository                     $tokenRepository,
        OrganizationRepository              $organizationRepository,
        protected ResultFetcher             $resultFetcher,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $currentUserRepository, $maskRepository, $metaModelLoader, $tracker, $tokenRepository, $organizationRepository);

        $this->caption = $this->_("Tokens set by this appointment");
        $this->onEmpty = $this->_("No tokens are set by this appointment");
        if (isset($this->extraFilter['gap_id_appointment'])) {
            unset($this->extraFilter['gap_id_appointment']);
        }
    }

    public function getFilter(MetaModelInterface $metaModel) : array
    {
        $filter = parent::getFilter($metaModel);

        $appId = $this->requestInfo->getParam(Model::APPOINTMENT_ID);

        if ($appId) {
            $platform = $this->resultFetcher->getPlatform();
            $appKeyPrefix = $platform->quoteValue(FieldsDefinition::makeKey(FieldMaintenanceModel::APPOINTMENTS_NAME, null));
            $appSource = $platform->quoteValue(StepEngineAbstract::APPOINTMENT_TABLE);

            $or[] = sprintf(
                "gro_valid_after_source = %s AND
                        (gto_id_respondent_track, gro_valid_after_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT(%s, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = %s)",
                $appSource,
                $appKeyPrefix,
                $appId
            );
            $or[] = sprintf(
                "gro_valid_for_source = %s AND
                        (gto_id_respondent_track, gro_valid_for_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT(%s, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = %s)",
                $appSource,
                $appKeyPrefix,
                $appId
            );

            $filter[] = '(' . implode(') OR (', $or) . ')';
        }

        return $filter;
    }
}
