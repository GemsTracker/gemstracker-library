<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Exception;
use Gems\Handlers\LogHandler;
use Gems\Model;
use Gems\Model\LogModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class RespondentLogHandler extends LogHandler
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = ['extraFilter' => 'getRespondentFilter'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        PeriodSelectRepository $periodSelectRepository,
        protected RespondentRepository $respondentRepository,
        LogModel $logModel,
    ) {
        parent::__construct($responder, $translate, $cache, $periodSelectRepository, $logModel);
    }

    protected function createModel(bool $detailed, string $action): FullDataInterface
    {
        parent::createModel($detailed, $action);

        $this->logModel->addTable('gems__respondent2org', ['gla_respondent_id' => 'gr2o_id_user', 'gla_organization' => 'gr2o_id_organization']);
        $this->logModel->getMetaModel()->setKeys([Model::LOG_ITEM_ID => 'gla_id']);

        $this->logModel->getMetaModel()->addMap(\MUtil\Model::REQUEST_ID1, 'gr2o_patient_nr');
        $this->logModel->getMetaModel()->addMap(\MUtil\Model::REQUEST_ID2, 'gr2o_id_organization');

        return $this->logModel;
    }

    /**
     * Get the respondent object
     */
    public function getRespondent(): Respondent
    {
        static $respondent;

        if (! $respondent) {
            $patientNumber  = $this->request->getAttribute(\MUtil\Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID2);

            $respondent = $this->respondentRepository->getRespondent($patientNumber, $organizationId);

            if ((! $respondent->exists) && $patientNumber && $organizationId) {
                throw new Exception(sprintf($this->_('Unknown respondent %s.'), $patientNumber));
            }
        }

        return $respondent;
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getRespondentFilter(): array
    {
        return ['gla_respondent_id' => $this->getRespondentId()];
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId(): int
    {
        return $this->getRespondent()->getId();
    }
}
