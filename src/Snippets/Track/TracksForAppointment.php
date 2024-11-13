<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Track;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Tracker;
use Gems\Util\Translated;
use Laminas\Db\Adapter\Adapter;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 10, 2016 6:17:20 PM
 */
class TracksForAppointment extends TracksSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        Tracker $tracker,
        Translated $translatedUtil,
        protected ResultFetcher $resultFetcher,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $tracker, $translatedUtil);

        $this->caption = $this->_("Tracks using this appointment");
        $this->onEmpty = $this->_("No tracks use this appointment");

        if (isset($this->extraFilter['gap_id_appointment'])) {
            unset($this->extraFilter['gap_id_appointment']);
        }
    }

    public function getFilter(MetaModelInterface $metaModel) : array
    {
        $platform = $this->resultFetcher->getPlatform();
        $filter[] = sprintf(
            "gr2t_id_respondent_track IN (
                    SELECT gr2t2a_id_respondent_track
                    FROM gems__respondent2track2appointment
                    WHERE gr2t2a_id_appointment = %s)",
            $platform->quoteValue($this->requestInfo->getParam(Model::APPOINTMENT_ID))
        );
        return $filter;
    }
}

