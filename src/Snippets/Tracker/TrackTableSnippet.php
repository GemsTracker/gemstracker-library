<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Tracker\Respondent;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 18-Apr-2018 18:40:08
 */
class TrackTableSnippet extends ModelTableSnippetAbstract
{
    /**
     *
     * @var DataReaderInterface
     */
    protected $model;

    /**
     *
     * @var Respondent
     */
    protected $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected OrganizationRepository $organizationRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        return $this->model;
    }

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter =  parent::getFilter($metaModel);

        if (isset($filter['gr2o_id_organization'])) {
            $otherOrgs = $this->organizationRepository->getAllowedOrganizationsFor((int)$filter['gr2o_id_organization']);
            if (is_array($otherOrgs)) {
                // If more than one org, do not use patient number but resp id
                if (isset($filter['gr2o_patient_nr'])) {
                    $filter['gr2o_id_user'] = $this->respondent->getId();
                    unset($filter['gr2o_patient_nr']);
                }

                $filter['gr2o_id_organization'] = $otherOrgs;

                // Second filter, should be changed as well
                if (isset($this->extraFilter['gr2t_id_organization'])) {
                    $this->extraFilter['gr2t_id_organization'] = $otherOrgs;
                }
            }
        }

        return $filter;
    }
}
