<?php

/**
 *
 * @package    Gems
 * @subpackage Handlers\Respondents
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Respondent;

use DateTimeImmutable;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\Translator\AppointmentTranslator;
use Gems\Repository\PeriodSelectRepository;
use Gems\User\User;
use MUtil\Model\ModelAbstract;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\AbstractDateType;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Handlers\Respondents
 * @since      Class available since version 2.0
 */
class CalendarHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     
    protected array $autofilterParameters = array(
        'dateFormat'        => 'getDateFormat',
        'extraSort'         => array(
            'gap_admission_time' => SORT_ASC,
            'gor_name'           => SORT_ASC,
            'glo_name'           => SORT_ASC,
            ),
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Agenda\\CalendarTableSnippet'];

    /**
     *
     * @var \Gems\User\User
     */
    protected User $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     * /
    public $db;

    /**
     * Array of the actions that use the model in form version.
     *
     * This determines the value of forForm().
     *
     * @var array $formActions Array of the actions that use the model with a form.
     */
    public array $formActions = array('create', 'delete', 'edit', 'import', 'simpleApi');

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Agenda\\CalendarSearchSnippet');

    public function __construct(
        SnippetResponderInterface        $responder,
        TranslatorInterface              $translator,
        CacheItemPoolInterface $cache,
        CurrentUserRepository            $currentUserRepository,
        protected Model                  $modelLoader,
        protected PeriodSelectRepository $periodSelectRepository,
    ) {
        parent::__construct($responder, $translator, $cache);
        
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        $model = $this->modelLoader->createAppointmentModel();
        $model->applyBrowseSettings();
        return $model;
    }

    /**
     * Get the date format used for the appointment date
     *
     * @return array
     */
    public function getDateFormat()
    {
        $model = $this->getModel();

        $format = $model->getMetaModel()->get('gap_admission_time', 'dateFormat');
        if (! $format) {
            $dateType = $model->getMetaModel()->getMetaModelLoader()->getDefaultTypeInterface(MetaModelInterface::TYPE_DATE);
            if ($dateType instanceof AbstractDateType) {
                $format = $dateType->dateFormat;
            }
        }

        return $format;
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults(): array
    {
        if (! $this->defaultSearchData) {
            $org = $this->currentUser->getCurrentOrganization();
            $today = new DateTimeImmutable('today');
            $this->defaultSearchData = [
                'gap_id_organization' => $org->canHaveRespondents() ? $org->getId() : null,
                'dateused'            => 'gap_admission_time',
                'datefrom'            => $today->format($this->getDateFormat()),
            ];
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true): array
    {
        $filter = parent::getSearchFilter($useRequest);

        $where = $this->periodSelectRepository->createPeriodFilter($filter, $this->getDateFormat(),'Y-m-d H:i:s');

        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }
    
    public function getIndexTitle(): string
    {
        return $this->_('Calendar');
    }

    public function simpleApiAction()
    {

        $data         = $this->requestInfo->getParams();
        $importLoader = $this->loader->getImportLoader();
        $model        = $this->getModel();
        $modelLoader  = $model->getMetaModel()->getMetaModelLoader();
        $translator   = $modelLoader->createTranslator(AppointmentTranslator::class);
        $translator->setDescription($this->_('Direct import'));

        $translator->setTargetModel($model)
                ->startImport();

        $raw    = $translator->translateRowValues($data, 1);
        if (false === $raw) {
            // No patient found
            echo "Patient does not exist";
            exit(0);
        }
        $row    = $translator->validateRowValues($raw, 1);
        $errors = $translator->getRowErrors(1);

        if ($errors) {
            echo "ERRORS Occured:\n" . implode("\n", $errors);
            exit(count($errors));

        } else {
            $output  = $model->save($row);
            $changed = $model->getChanged();
            // print_r($output);

            $appId = $output['gap_id_appointment'];
            if ($changed) {
                echo "Changes saved to appointment $appId.";
            }  else {
                echo "No changes to appointment $appId.";
            }
            exit(0);
        }
    }
}
