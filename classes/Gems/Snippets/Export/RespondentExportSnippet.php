<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentExportSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Export;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 6-mei-2015 11:09:12
 */
class RespondentExportSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Export_RespondentExport
     */
    protected $export;

    /**
     * Caption
     *
     * @var string
     */
    protected $formTitle;

    /**
     *
     * @var boolean When true the group box is not shown
     */
    protected $hideGroup = false;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * The respondent
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

    /**
     *
     * @var array
     */
    protected $respondentIds = array();

    /**
     * Optional, required when editing or $respondentTrackId should be set
     *
     * @var \Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     *
     * @param string $patientNr
     * @param int $organizationId
     * @return \Gems\Snippets\Export\RespondentExportSnippet
     */
    protected function addRespondent($patientNr, $organizationId)
    {
        $this->respondentIds[] = array(
            'gr2o_patient_nr'      => $patientNr,
            'gr2o_id_organization' => $organizationId,
        );

        return $this;
    }
    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->loadExport();
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->request->isPost()) {
            $this->export->render(
                    $this->getRespondentIds(),
                    $this->request->getParam('group'),
                    $this->request->getParam('format')
                    );
        } else {
            $seq = new \MUtil_Html_Sequence();

            if ($this->formTitle) {
                $seq->h2($this->formTitle);
            }

            $form = $this->export->getForm($this->hideGroup);
            $div  = $seq->div(array('id' => 'mainform'), $form);
            $table = new \MUtil_Html_TableElement(array('class' => 'formTable'));
            $table->setAsFormLayout($form);

            $form->populate($this->request->getParams());

            return $seq;
        }
    }

    /**
     *
     * @return array
     */
    protected function getRespondentIds()
    {
        return $this->respondentIds;
    }

    protected function loadExport()
    {
        $this->export = $this->loader->getRespondentExport();

        if ($this->token instanceof \Gems_Tracker_Token) {
            $this->addRespondent(
                    $this->token->getPatientNumber(),
                    $this->token->getOrganizationId()
                    );
            $this->export->addRespondentTrackFilter($this->token->getRespondentTrackId());
            $this->export->addTokenFilter($this->token->getTokenId());

        } elseif ($this->respondentTrack instanceof \Gems_Tracker_RespondentTrack) {
            $this->addRespondent(
                    $this->respondentTrack->getPatientNumber(),
                    $this->respondentTrack->getOrganizationId()
                    );
            $this->export->addRespondentTrackFilter($this->respondentTrack->getRespondentTrackId());

        } elseif ($this->respondent instanceof \Gems_Tracker_Respondent) {
            $this->addRespondent(
                    $this->respondent->getPatientNumber(),
                    $this->respondent->getOrganizationId()
                    );
        }
    }

    /**
     *
     * @param array $respondents
     * @return \Gems\Snippets\Export\RespondentExportSnippet
     */
    protected function setRespondentIds(array $respondents)
    {
        $this->respondentIds = $respondents;

        return $this;
    }
}
