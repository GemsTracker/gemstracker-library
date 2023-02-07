<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Log;

use Gems\MenuNew\RouteHelper;
use Gems\Model;
use Gems\Model\LogModel;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\ModelDetailTableSnippetAbstract;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 23-apr-2015 11:10:02
 */
class LogShowSnippet extends ModelDetailTableSnippetAbstract
{
    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = BridgeAbstract::MODE_SINGLE_ROW;

    protected ?LogModel $model = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected Model $modelLoader,
        protected RespondentRepository $respondentRepository,
        protected RouteHelper $routeHelper,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof LogModel) {
            $this->model = $this->modelLoader->createLogModel();
            $this->model->applyDetailSettings();
        }
        return $this->model;
    }
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $model)
    {
        $row = $bridge->getRow();

        parent::setShowTableFooter($bridge, $model);

        $footer = $bridge->tfrow();
        if (isset($row['gla_respondent_id'], $row['gla_organization'])) {
            $patientNr = $this->respondentRepository->getPatientNr($row['gla_respondent_id'], $row['gla_organization']);

            $params = [
                \MUtil\Model::REQUEST_ID1 => $patientNr,
                \MUtil\Model::REQUEST_ID2 => $row['gla_organization'],
            ];

            $url = $this->routeHelper->getRouteUrl('respondent.show', $params);
            $footer->actionLink($url, $this->_('Respondent'));
            $footer[] = ' ';
        }
        if (isset($row['gsf_id_user'])) {
            $params = [
                \MUtil\Model::REQUEST_ID => $row['gsf_id_user'],
            ];

            $url = $this->routeHelper->getRouteUrl('setup.access.staff.show', $params);
            $footer->actionLink($url, $this->_('Staff'));
        }
    }
}
