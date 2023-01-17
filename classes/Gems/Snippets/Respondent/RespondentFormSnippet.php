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
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Snippets\ModelFormSnippet;
use Gems\User\UserLoader;
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
        protected ResultFetcher $resultFetcher,
        protected UserLoader $userLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();

        if ($this->createData && ($this->requestInfo->isPost() && (! isset($this->formData[$this->saveButtonId])))) {
            if ((! $this->_saveButton) || (! $this->_saveButton->isChecked())) {
                if (isset($this->formData['grs_ssn']) && $this->formData['grs_ssn'])  {
                    $filter = [
                        'grs_ssn' => $this->formData['grs_ssn'],
                        'gr2o_id_organization' => true, // Make sure all organizations are checked in RespModel
                    ];

                    if ($this->formData['gr2o_id_organization']) {
                        $orgId = (int)$this->formData['gr2o_id_organization'];
                    } else {
                        $orgId = (int)$this->model->get('gr2o_id_organization', 'default');
                    }
                    $order = ["CASE WHEN gr2o_id_organization = ? THEN 1 ELSE 2 END" => SORT_ASC];

                    $data = $this->model->loadFirst($filter, $order);

                    // Fallback for when just a respondent row was saved but no resp2org exists
                    if (! $data) {
                        $data = $this->resultFetcher->fetchRow(
                                "SELECT * FROM gems__respondents WHERE grs_ssn = ?",
                                [$this->formData['grs_ssn']]
                                );

                        if ($data) {
                            $data['gr2o_id_organization'] = false;
                        }
                    }

                    if ($data) {
                        // \MUtil\EchoOut\EchoOut::track($this->formData);
                        // \MUtil\EchoOut\EchoOut::track($data);
                        // Do not use this value
                        unset($data['grs_ssn']);

                        if ($data['gr2o_id_organization'] == $orgId) {
                            // gr2o_patient_nr
                            // gr2o_id_organization

                            $this->addMessage($this->_('Known respondent.'));

                            //*
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
                            } // */
                        } else {
                            if ($data['gr2o_id_organization']) {
                                $org = $this->userLoader->getOrganization($data['gr2o_id_organization']);
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
                                $this->formData['gr2o_id_user'] = $data['grs_id_user'];
                            }
                        }

                    }
                }
            }
        }
        return $this->formData;
    }
}
