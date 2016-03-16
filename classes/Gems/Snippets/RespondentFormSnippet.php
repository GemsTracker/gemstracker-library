<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_RespondentFormSnippet extends \Gems_Snippets_ModelFormSnippetGeneric
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = true;

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        if ($this->request->isPost() && (! isset($this->formData[$this->saveButtonId]))) {
            if ((! $this->_saveButton) || (! $this->_saveButton->isChecked())) {
                if (isset($this->formData['grs_ssn']) && $this->formData['grs_ssn'])  {
                    $filter = array(
                        'grs_ssn' => $this->formData['grs_ssn'],
                        'gr2o_id_organization' => true, // Make sure all organisations are checked in RespModel
                        );

                    if ($this->formData['gr2o_id_organization']) {
                        $orgId = $this->formData['gr2o_id_organization'];
                    } else {
                        $orgId = $this->model->get('gr2o_id_organization', 'default');
                    }
                    $order = array(
                        $this->db->quoteInto(
                                "CASE WHEN gr2o_id_organization = ? THEN 1 ELSE 2 END",
                                $orgId
                                ) => SORT_ASC
                        );

                    $data = $this->model->loadFirst($filter, $order);

                    // Fallback for when just a respondent row was saved but no resp2org exists
                    if (! $data) {
                        $data = $this->db->fetchRow(
                                "SELECT * FROM gems__respondents WHERE grs_ssn = ?",
                                $this->formData['grs_ssn']
                                );

                        if ($data) {
                            $data['gr2o_id_organization'] = false;
                        }
                    }

                    if ($data) {
                        // \MUtil_Echo::track($this->formData);
                        // \MUtil_Echo::track($data);
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
                                $org = $this->loader->getOrganization($data['gr2o_id_organization']);
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
    }
}
