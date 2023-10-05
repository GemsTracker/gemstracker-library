<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\ModelFormSnippet;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class RespondentFormSnippet extends ModelFormSnippet
{
    protected array $ssnOtherOrgCopyFields = ['gr2o_email', 'gr2o_mailable'];

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected OrganizationRepository $organizationRepository,
        protected ResultFetcher $resultFetcher,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
    }

    public function checkSsnData(string $ssn)
    {
        /**
         * @var $model RespondentModel
         */
        $model = $this->getModel();
        $metaModel = $model->getMetaModel();

        if ($this->formData['gr2o_id_organization']) {
            $organizationId = (int) $this->formData['gr2o_id_organization'];
        } else {
            $organizationId = (int) $metaModel->get('gr2o_id_organization', 'default');
        }
        $filter['grs_ssn'] = $model->saveSSN($ssn);
        $order = ["CASE WHEN gr2o_id_organization = $organizationId THEN 1 ELSE 2 END" => SORT_ASC];
        $data  = $this->model->loadFirst($filter, $order);
//        dump($ssn, $data, $filter);

        // Fallback for when just a respondent row was saved but no resp2org exists
        if (! $data) {
            $data = $this->resultFetcher->fetchRow(
                "SELECT * FROM gems__respondents WHERE grs_ssn = ?",
                [$filter['grs_ssn']]
            );

            if ($data) {
                // Used to differentiate between no data or no org
                $data['gr2o_id_organization'] = false;
            }
        }

        if ($data) {
            $this->mergeSsnData($data, $organizationId);
        }
        return (bool) $data;
    }

    /**
     * @return void
     */
    protected function onFakeSubmit()
    {
        // Get filtered version from form
        $ssn = $this->_form->getValue('grs_ssn');
        $current = $this->formData[$this->_form->focusTrackerElementId] ?? 'xx';

        if ($ssn && (('' == $current) || ('grs_ssn' == $current))) {
            $this->checkSsnData($ssn);
            $this->formData[$this->_form->focusTrackerElementId] = 'gr2o_patient_nr';
        }
    }

    protected function mergeSsnData(array $data, int $currentOrganizationId)
    {
        // Do NOT use this value as it is possibly encrypted
        unset($data['grs_ssn']);

        if ($data['gr2o_id_organization'] == $currentOrganizationId) {
            $this->addMessage($this->_('Known respondent.'));

            foreach ($data as $name => $value) {
                if ((substr($name, 0, 4) == 'grs_') || (substr($name, 0, 5) == 'gr2o_')) {
                    if (array_key_exists($name, $this->formData)) {
                        $this->formData[$name] = $value;
                    }
                    $cname = $this->model->getKeyCopyName($name);
                    if (array_key_exists($cname, $this->formData)) {
                        $this->formData[$cname] = $value;
                    }
                }
            }

        } else {
            if ($data['gr2o_id_organization']) {
                $org = $this->organizationRepository->getOrganization($data['gr2o_id_organization']);
                $this->addMessage(sprintf(
                    $this->_('Respondent data retrieved from %s.'),
                    $org->getName()
                ));
            } else {
                $this->addMessage($this->_('Respondent data found.'));
            }

            foreach ($data as $name => $value) {
                if ((substr($name, 0, 4) == 'grs_') && array_key_exists($name, $this->formData)) {
                    $this->formData[$name] = $value;
                }
            }
            foreach ($this->ssnOtherOrgCopyFields as $name) {
                if (array_key_exists($name, $this->formData) && isset($data[$name]) && !$this->formData[$name]) {
                    $this->formData[$name] = $data[$name];
                }
            }
            $this->formData['gr2o_id_user'] = $data['grs_id_user'];
        }
    }
}
