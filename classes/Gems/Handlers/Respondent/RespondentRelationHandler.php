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
use Gems\Model\RespondentRelationModel;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\ModelItemYesNoDeleteSnippet;
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

        /* @var $relationModel RespondentRelationModel */
        $relationModel = $this->modelLoader->getRespondentRelationModel();

        $respondentId = $respondent->getId();
        $relationModel->set('grr_id_respondent', 'default', $respondentId);
        $relationModel->set('gr2o_patient_nr', 'default', $respondent->getPatientNumber());
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
            $patientNr = $this->request->getAttribute(\MUtil\Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID2);
            $respondent = $this->respondentRepository->getRespondent($patientNr, $organizationId);

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