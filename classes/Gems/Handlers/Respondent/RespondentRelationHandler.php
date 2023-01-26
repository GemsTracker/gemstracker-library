<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class RespondentRelationHandler extends ModelSnippetLegacyHandlerAbstract
{

    public Respondent|null $_respondent = null;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Respondent\\Relation\\TableSnippet'];

    protected array $createEditSnippets = ['Respondent\\Relation\\ModelFormSnippet'];

    protected array $deleteSnippets = ['Respondent\\Relation\\YesNoDeleteSnippet'];

    protected array $indexStopSnippets = ['Generic\\CurrentSiblingsButtonRowSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected Model $modelLoader,
        protected RespondentRepository $respondentRepository,
    )
    {
        parent::__construct($responder, $translate);
    }

    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        $respondent = $this->getRespondent();

        $relationModel = $this->modelLoader->getRespondentRelationModel();
        /* @var $relationModel \Gems\Model\RespondentRelationModel */

        $respondentId = $respondent->getId();
        $relationModel->set('grr_id_respondent', 'default', $respondentId);
        $relationModel->set('gr2o_patient_nr', 'default', $respondent->getPatientId());
        $relationModel->set('gr2o_id_organization', 'default', $respondent->getOrganizationId());

        if ($detailed) {
            $relationModel->applyDetailSettings();
        } else {
            $relationModel->applyBrowseSettings();
        }

        return $relationModel;
    }

    public function getRespondent()
    {
        if (is_null($this->_respondent)) {
            $model = $this->modelLoader->getRespondentModel(true);
            $model->applyParameters($this->request->getQueryParams() + $this->request->getParsedBody());
            $respondent = $model->loadFirst();
            $respondent = $this->respondentRepository->getRespondent($respondent['gr2o_patient_nr'], $respondent['gr2o_id_organization']);

            $this->_respondent = $respondent;
        }
        return $this->_respondent;
    }

    public function getTopic(int $count = 1): string
    {
        $respondentName = $this->getRespondent()->getName();

        return sprintf($this->plural('relation for %s', 'relations for %s', $count), $respondentName);
    }

    public function deleteAction()
    {
        $this->deleteParameters['resetRoute'] = true;
        $this->deleteParameters['deleteAction'] = 'delete'; // Trick to not get aftersaveroute
        $this->deleteParameters['abortAction'] = 'index';
        $this->deleteParameters['afterSaveRouteUrl'] = [
            'action' => 'index',
            'controller' => 'respondent-relation',
            \MUtil\Model::REQUEST_ID1 => $this->request->getAttribute(\MUtil\Model::REQUEST_ID1),
            \MUtil\Model::REQUEST_ID2 => $this->request->getAttribute(\MUtil\Model::REQUEST_ID2),
        ];

        parent::deleteAction();
    }

}