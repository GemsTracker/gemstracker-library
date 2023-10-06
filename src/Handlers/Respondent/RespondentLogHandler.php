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
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use Laminas\Db\Adapter\Adapter;
use MUtil\Model\ModelAbstract;
use Zalt\Base\TranslatorInterface;
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
        Model $modelLoader,
        PeriodSelectRepository $periodSelectRepository,
        protected RespondentRepository $respondentRepository,
    ) {
        parent::__construct($responder, $translate, $modelLoader, $periodSelectRepository);
    }

    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var Model\JoinModel $model
         */
        $model = parent::createModel($detailed, $action);
        $model->addTable('gems__respondent2org', ['gla_respondent_id' => 'gr2o_id_user', 'gla_organization' => 'gr2o_id_organization']);
        $model->setKeys([Model::LOG_ITEM_ID => 'gla_id']);

        $model->addMap(\MUtil\Model::REQUEST_ID1, 'gr2o_patient_nr');
        $model->addMap(\MUtil\Model::REQUEST_ID2, 'gr2o_id_organization');

        return $model;
    }

    /**
     * Get the respondent object
     *
     * @return Respondent
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
