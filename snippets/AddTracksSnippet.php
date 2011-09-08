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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
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
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * Displays a toolbox of drop down UL's om tracks/ surveys toe te voegen.
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class AddTracksSnippet extends MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     * Optional: $request or $tokenData must be set
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var Zend_Session
     */
    public $session;

    protected function _getTracks($trackType, $pageRef)
    {
        switch ($trackType) {
            case 'T':
                $trackTypeDescription = $this->_('Tracks');
                $trackController = 'track';
                break;
            case 'S':
                $trackTypeDescription = $this->_('by Respondents');
                $trackController = 'survey';
                break;
            case 'M':
                $trackTypeDescription = $this->_('by Staff');
                $trackController = 'survey';
                break;
            default:
                throw new exception('Invalid track type requested.');
        }

        $trackTypeTime = $trackType . '_time';

        if (isset($this->session->$trackType, $this->session->$trackTypeTime) && (time() < $this->session->$trackTypeTime)) {
            $tracks = $this->session->$trackType;
        } else {
            $organization_id = $this->escort->getCurrentOrganization();
            switch ($trackType) {
                case 'T':
                    $sql = "SELECT gtr_id_track, gtr_track_name
                        FROM gems__tracks
                        WHERE gtr_date_start < CURRENT_TIMESTAMP AND
                            (gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP) AND
                            gtr_active = 1 AND
                            gtr_track_type = 'T' AND
                            gtr_organisations LIKE '%|$organization_id|%'
                         ORDER BY gtr_track_name";
                    break;
                case 'S':
                    $sql = "SELECT gtr_id_track, gtr_track_name
                        FROM gems__tracks INNER JOIN
                            gems__rounds ON gtr_id_track = gro_id_track INNER JOIN
                            gems__surveys ON gro_id_survey = gsu_id_survey INNER JOIN
                            gems__groups ON gsu_id_primary_group = ggp_id_group
                        WHERE gtr_date_start < CURRENT_TIMESTAMP AND
                            (gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP) AND
                            gtr_active = 1 AND
                            gtr_track_type = 'S' AND
                            ggp_respondent_members = 1 AND
                            gtr_organisations LIKE '%|$organization_id|%'
                         ORDER BY gtr_track_name";
                    break;
                case 'M':
                    $sql = "SELECT gtr_id_track, gtr_track_name
                        FROM gems__tracks INNER JOIN
                            gems__rounds ON gtr_id_track = gro_id_track INNER JOIN
                            gems__surveys ON gro_id_survey = gsu_id_survey INNER JOIN
                            gems__groups ON gsu_id_primary_group = ggp_id_group
                        WHERE gtr_date_start < CURRENT_TIMESTAMP AND
                            (gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP) AND
                            gtr_active = 1 AND
                            gtr_track_type = 'S' AND
                            ggp_respondent_members = 0 AND
                            gtr_organisations LIKE '%|$organization_id|%'
                         ORDER BY gtr_track_name";
                    break;
                // default:
                //    throw new exception('Invalid track type requested.');
            }
            $tracks = $this->db->fetchPairs($sql);

            $this->session->$trackType = $tracks;
            $this->session->$trackTypeTime = time() + 600;
        }

        $div = MUtil_Html::create()->div(array('class' => 'toolbox'));

        if ($tracks) {
            $pageRef['RouteReset'] = true;

            $div->a(array('controller' => $trackController, 'action' => 'index') +  $pageRef,
                $trackTypeDescription,
                array('class' => 'toolanchor'));

            $data = new MUtil_Lazy_RepeatableByKeyValue($tracks);
            $li = $div->ul($data)->li();
            $li->a(array(Gems_Model::TRACK_ID => $data->key, 'controller' => $trackController, 'action' => 'view') + $pageRef, array('class' => 'rightFloat'))
               ->img(array('src' => 'info.png', 'width' => 12, 'height' => 12, 'alt' => $this->_('info')));
            $li->a(array(Gems_Model::TRACK_ID => $data->key, 'controller' => $trackController, 'action' => 'create') + $pageRef, $data->value, array('class' => 'add'));
        } else {
            $div->span($trackTypeDescription, array('class' => 'toolanchor disabled'));

            $div->ul($this->_('None available'), array('class' => 'disabled'));
        }

        return $div;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $pageRef = array(MUtil_Model::REQUEST_ID => $this->request->getParam(MUtil_Model::REQUEST_ID));

        $addToLists = MUtil_Html::create()->div(array('class' => 'tooldock'));
        $addToLists->strong($this->_('Add'));
        $addToLists[] = $this->_getTracks('T', $pageRef);
        $addToLists[] = $this->_getTracks('S', $pageRef);
        $addToLists[] = $this->_getTracks('M', $pageRef);

        return $addToLists;
    }
}
