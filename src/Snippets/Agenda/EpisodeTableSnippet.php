<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\Filter\AppointmentFilterInterface;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\EpisodeOfCareModel;
use Gems\Model\Respondent\RespondentModelOptions;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 26-Oct-2018 15:14:48
 */
class EpisodeTableSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems\Agenda\AppointmentFilterInterface
     */
    protected $calSearchFilter;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions, 
        RequestInfo $requestInfo, 
        MenuSnippetHelper $menuHelper, 
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected Model $modelLoader, 
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
        
        $this->currentUser = $currentUserRepository->getCurrentUser();
        
        if (null !== $this->calSearchFilter) {
            $this->caption = $this->_('Example episodes');
            if ($this->calSearchFilter instanceof AppointmentFilterInterface) {
                $this->searchFilter = [
                    \MUtil\Model::SORT_DESC_PARAM => 'gec_startdate',
                    $this->calSearchFilter->getSqlEpisodeWhere(),
                    'limit' => 10,
                ];
                // \MUtil\EchoOut\EchoOut::track($this->calSearchFilter->getSqlEpisodeWhere());

                $this->bridgeMode = BridgeInterface::MODE_ROWS;
            } elseif (false === $this->calSearchFilter) {
                $this->onEmpty = $this->_('Filter is inactive');
                $this->searchFilter = ['1=0'];
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof EpisodeOfCareModel) {
            $this->model = $this->modelLoader->createEpisodeOfCareModel();

            $this->model->applyBrowseSettings();
        }

        if ($this->calSearchFilter instanceof AppointmentFilterInterface) {
            $this->model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'), 'order', 3);

            (new RespondentModelOptions)->addNameToModel($this->model, $this->_('Name'));

            $this->model->set('name', 'order', 6);
        }

        // \MUtil\Model::$verbose = true;
        return $this->model;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     * /
    public function hasHtmlOutput(): bool
    {
        if ($this->currentUser->hasPrivilege('pr.episodes')) {
            return parent::hasHtmlOutput();
        }

        return false;
    } // */ 
}
