<?php

/**
 *
 * @package    Gems
 * @subpackage Pulse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail\Log;

use Gems\Snippets\AutosearchInRespondentSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Pulse
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class RespondentMailLogSearchSnippet extends AutosearchInRespondentSnippet
{
    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * Creates the form itself
     *
     * @param array $options
     * @return \Gems\Form
     */
    protected function createForm($options = null)
    {
        $form = parent::createForm($options);

        $form->activateJQuery();

        return $form;
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        // Search text
        $elements = parent::getAutoSearchElements($data);

        $this->_addPeriodSelectors($elements, array('grco_created' => $this->_('Date sent')));

        $br  = \MUtil\Html::create()->br();

        $elements[] = null;

        $dbLookup = $this->util->getDbLookup();

        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $this->getRespondentTrackNames(),
                $this->_('(select a track)')
                );

        $elements[] = $this->_createSelectElement('gto_id_survey',
                $this->getRespondentSurveyNames(),
                $this->_('(select a survey)'));

        $elements[] = $this->_createSelectElement('status',
                $this->util->getTokenData()->getEveryStatus(),
                $this->_('(select a status)'));

        return $elements;
    }

    protected function getRespondentSurveyNames()
    {
        $surveysSql = 'SELECT gsu_id_survey, gsu_survey_name FROM gems__respondent2track
                        JOIN gems__respondent2org ON gr2t_id_user = gr2o_id_user AND gr2o_patient_nr = ? AND gr2o_id_organization = ?
                        LEFT JOIN gems__rounds ON gro_id_track = gr2t_id_track
                        LEFT JOIN gems__surveys ON gro_id_survey = gsu_id_survey';

        $surveyNames = $this->db->fetchPairs($surveysSql,
                array(
                    $this->request->getParam(\MUtil\Model::REQUEST_ID1),
                    $this->request->getParam(\MUtil\Model::REQUEST_ID2)
                )
        );
        // \MUtil\EchoOut\EchoOut::track($surveyNames);
        return $surveyNames;
    }

    protected function getRespondentTrackNames()
    {
        $tracksSql = 'SELECT gtr_id_track, gtr_track_name FROM gems__respondent2track
                        JOIN gems__respondent2org ON gr2t_id_user = gr2o_id_user AND gr2o_patient_nr = ? AND gr2o_id_organization = ?
                        LEFT JOIN gems__tracks ON gtr_id_track = gr2t_id_track';

        $trackNames = $this->db->fetchPairs($tracksSql,
                array(
                    $this->request->getParam(\MUtil\Model::REQUEST_ID1),
                    $this->request->getParam(\MUtil\Model::REQUEST_ID2)
                )
        );
        // \MUtil\EchoOut\EchoOut::track($trackNames);
        return $trackNames;
    }
}