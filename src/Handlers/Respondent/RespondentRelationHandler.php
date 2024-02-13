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
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\Transform\RespondentIdTransformer;
use Gems\Repository\RespondentRepository;
use MUtil\Model\ModelAbstract;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
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
    use GetRespondentTrait;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected Model $modelLoader,
        protected RespondentRepository $respondentRepository,
    )
    {
        parent::__construct($responder, $translate, $cache);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        $respondent = $this->getRespondent();

        /* @var RespondentRelationModel $relationModel */
        $relationModel = $this->modelLoader->getRespondentRelationModel();

        $relationModel->set('gr2o_patient_nr', 'default', $respondent->getPatientNumber());
        $relationModel->set('gr2o_id_organization', 'default', $respondent->getOrganizationId());
        $relationModel->addTransformer(new RespondentIdTransformer($respondent->getId(), 'grr_id_respondent'));

        if ($detailed) {
            $relationModel->applyDetailSettings();
        } else {
            $relationModel->applyBrowseSettings();
        }

        return $relationModel;
    }

    public function getTopic(int $count = 1): string
    {
        $respondentName = $this->getRespondent()->getName();

        return sprintf($this->plural('relation for %s', 'relations for %s', $count), $respondentName);
    }

    public function deleteAction()
    {
        $patientNr = $this->request->getAttribute(Model::REQUEST_ID1);
        $organizationId = $this->request->getAttribute(Model::REQUEST_ID2);
        $this->currentUserRepository->assertAccessToOrganizationId($organizationId);

        $this->deleteParameters['resetRoute'] = true;
        $this->deleteParameters['deleteAction'] = 'delete'; // Trick to not get aftersaveroute
        $this->deleteParameters['abortAction'] = 'index';
        $this->deleteParameters['afterSaveRouteUrl'] = [
            'action' => 'index',
            'controller' => 'respondent-relation',
            Model::REQUEST_ID1 => $patientNr,
            Model::REQUEST_ID2 => $organizationId,
        ];

        parent::deleteAction();
    }

    public function indexAction()
    {
        $patientNr = $this->request->getAttribute(Model::REQUEST_ID1);
        $organizationId = $this->request->getAttribute(Model::REQUEST_ID2);
        $this->currentUserRepository->assertAccessToOrganizationId($organizationId);

        $this->autofilterParameters['extraFilter'] = [
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
        ];
        parent::indexAction();
    }

}