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

use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\MetaModelLoader;
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
    CurrentUserRepository                   $currentUserRepository,
        MaskRepository                      $maskRepository,
        MetaModelLoader                     $metaModelLoader,
        Tracker                             $tracker,
        TokenRepository                     $tokenRepository,
        protected \Zend_Db_Adapter_Abstract $db
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $currentUserRepository, $maskRepository, $metaModelLoader, $tracker, $tokenRepository);

        $this->caption = $this->_("Tokens set by this appointment");
        $this->onEmpty = $this->_("No tokens are set by this appointment");
    }

    public function getFilter(MetaModelInterface $metaModel) : array
    {
        $filter = parent::getFilter($metaModel);

        $appId = $this->requestInfo->getParam(Model::APPOINTMENT_ID);

        if ($appId) {
            $appKeyPrefix = $this->db->quote(FieldsDefinition::makeKey(FieldMaintenanceModel::APPOINTMENTS_NAME, null));
            $appSource = $this->db->quote(StepEngineAbstract::APPOINTMENT_TABLE);

            $or[] = $this->db->quoteInto(
                "gro_valid_after_source = $appSource AND
                        (gto_id_respondent_track, gro_valid_after_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT($appKeyPrefix, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = ?)",
                $appId
            );
            $or[] = $this->db->quoteInto(
                "gro_valid_for_source = $appSource AND
                        (gto_id_respondent_track, gro_valid_for_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT($appKeyPrefix, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = ?)",
                $appId
            );

            $filter[] = '(' . implode(') OR (', $or) . ')';
        }

        return $filter;
    }
}
