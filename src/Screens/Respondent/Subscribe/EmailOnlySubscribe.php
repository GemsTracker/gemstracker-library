<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Subscribe;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Screens\SubscribeScreenInterface;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 11:38:50
 */
class EmailOnlySubscribe implements SubscribeScreenInterface
{
    public const SUBSCRIBED_PATIENT_NR_PREFIX = 'subscr';
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly ResultFetcher $resultFetcher,
    )
    {}

    /**
     * @return string
     */
    public function generatePatientNumber(): string
    {
        $org    = $this->currentUserRepository->getCurrentOrganization();
        $orgId  = $org->getId();
        $prefix = static::SUBSCRIBED_PATIENT_NR_PREFIX;

        if ($org->getCode()) {
            $codes = explode(' ' , $org->getCode());
            $code  = reset($codes); // Start code with space not to use this option
            if ($code) {
                $prefix = $code;
            }
        }

        $sql  = "SELECT gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?";
        do {
            $number = $prefix . random_int(1000000, 9999999);
            // \MUtil\EchoOut\EchoOut::track($number);
        } while ($this->resultFetcher->fetchOne($sql, [$number, $orgId]));

        return $number;
    }

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Subscribe using e-mail address only');
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getSubscribeParameters(): array
    {
        return [
            'formTitle' => sprintf(
                    $this->translator->_('Subscribe to surveys for %s'),
                    $this->currentUserRepository->getCurrentOrganization()->getName()
                    ),
            'patientNrGenerator' => [$this, 'generatePatientNumber'],
            'routeAction' => 'participate.subscribe-thanks',
            'saveLabel' => $this->translator->_('Subscribe'),
        ];
    }

    /**
     *
     * @return array Of snippets
     */
    public function getSubscribeSnippets(): array
    {
        return ['Subscribe\\EmailSubscribeSnippet'];
    }
}
