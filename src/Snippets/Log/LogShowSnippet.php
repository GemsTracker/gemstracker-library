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

use Gems\Menu\RouteHelper;
use Gems\Model;
use Gems\Model\LogModel;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\ModelDetailTableSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
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

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected Model $modelLoader,
        protected RespondentRepository $respondentRepository,
        protected RouteHelper $routeHelper,
        protected LogModel $model,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createModel(): DataReaderInterface
    {
        return $this->model;
    }

    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $dataModel)
    {
        $row = $bridge->getRow();

        parent::setShowTableFooter($bridge, $dataModel);

        $footer = $bridge->tfrow();
        if (isset($row['gla_respondent_id'], $row['gla_organization'])) {
            $patientNr = $this->respondentRepository->getPatientNr($row['gla_respondent_id'], $row['gla_organization']);

            if ($patientNr) {
                $params = [
                    MetaModelInterface::REQUEST_ID1 => $patientNr,
                    MetaModelInterface::REQUEST_ID2 => $row['gla_organization'],
                ];

                $url = $this->routeHelper->getRouteUrl('respondent.show', $params);
                $footer->actionLink($url, $this->_('Respondent'));
                $footer[] = ' ';
            }
        }
        if (isset($row['gsf_id_user'])) {
            $params = [
                MetaModelInterface::REQUEST_ID => $row['gsf_id_user'],
            ];

            $url = $this->routeHelper->getRouteUrl('setup.access.staff.show', $params);
            $footer->actionLink($url, $this->_('Staff'));
        }
    }
}