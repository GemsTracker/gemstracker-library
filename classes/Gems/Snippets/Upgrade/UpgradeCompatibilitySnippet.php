<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets\Upgrade
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: UpgradeCompatibilitySnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Upgrade;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Upgrade
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 11-mei-2015 19:27:25
 */
class UpgradeCompatibilitySnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \GemsEscort
     */
    protected $escort;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \MUtil_Html_Sequence
     */
    protected $html;

    /**
     * A specific report on the escort class
     */
    protected function addEscortReport()
    {
        $this->html->h2('Project and escort class report');

        $escortClass   = get_class($this->escort);
        $foundNone     = true;
        $projectName   = $this->project->getName();

        $oldInterfaces = array(
            'Gems_Project_Tracks_FixedTracksInterface',
            'Gems_Project_Tracks_StandAloneSurveysInterface',
            'Gems_Project_Tracks_TracksOnlyInterface',
            );
        foreach ($oldInterfaces as $interface) {
            if ($this->escort instanceof $interface) {
                $foundNone = false;
                $this->html->pInfo(sprintf(
                        '%s implements the deprecated %s interface. Remove this interface.',
                        $escortClass,
                        $interface
                        ));
            }
        }

        $snippetsDir = APPLICATION_PATH . '\snippets';
        if (file_exists($snippetsDir)) {
            $foundNone = false;
            $this->html->pInfo(sprintf(
                    '%s still uses the deprecated %s directory for snippets. This directory is deprecated and will be removed in 1.7.2.',
                    $projectName,
                    $snippetsDir
                    ));
        }
        if ($foundNone) {
            $this->html->pInfo(sprintf('%s and %s are up to date.', $projectName, $escortClass));
        }
    }

    /**
     * A specific report on a code file
     *
     * @param \SplFileInfo $filename
     * @return boolean
     */
    protected function addFileReport(\SplFileInfo $fileinfo)
    {
        // $extension = strtolower($fileinfo->getExtension());
        $extension = strtolower(pathinfo($fileinfo, PATHINFO_EXTENSION));
        if (('php' !== $extension) && ('phtml' !== $extension)) {
            return false;
        }

        $content  = file_get_contents($fileinfo);
        $messages = array();

        if (preg_match('/Single.*Survey/', $fileinfo->getFilename())) {
            $messages[] = "This seems to be a file for (obsolete) SingleSurveys. This file can probably be removed.";
        }

        if (\MUtil_String::endsWith($fileinfo->getPath(), 'controllers')) {
            switch ($fileinfo->getFilename()) {
                case 'SurveyController.php':
                    $messages[] = "You can delete this file. This controller is no longer in use.";
                    break;
                case 'RespondentController.php':
                    if (preg_match(
                            '/class\\s+RespondentController\\s+extends\\s+\\\\?Gems_Default_RespondentAction/', $content)
                            ) {
                        $messages[] = array(
                            "Your respondent controller seems to inherit from Gems_Default_RespondentAction.",
                            ' ',
                            \MUtil_Html::create('strong', "This class may be obsolete in 1.7.2!"),
                            ' ',
                            "Use Gems_Default_RespondentNewAction instead.",
                            );
                    }
                    break;

                default:
                    $changedControllers = array(
                        'ConsentController',
                        'DatabaseController',
                        'LogController',
                        'LogMaintenanceController',
                        'ProjectSurveysController',
                        'ProjectTracksController',
                        'ReceptionController',
                        'RoleController',
                        'SurveyMaintenanceController',
                        'TrackController',
                        'TrackRoundsController',
                        );
                    foreach ($changedControllers as $controller) {
                        if ($controller . '.php' == $fileinfo->getFilename()) {
                            $messages[] = "The parent class changed from BrowseEditAction to ModelSnippetActionAbstract.";
                            $messages[] = \MUtil_Html::create('strong', "Check all code in the controller!");
                            break;
                        }
                    }
            }
        } else {
            $movedSnippets = array(
                'AddTracksSnippet'             => 'Tracker\\AddTracksSnippet',
                'EditRoundStepSnippet'         => 'Tracker\\Rounds\\EditRoundStepSnippet',
                'ShowRoundStepSnippet'         => 'Tracker\\Rounds\\ShowRoundStepSnippet',
                'DeleteInSourceTrackSnippet'   => 'Tracker\\DeleteTrackSnippet',
                'DeleteTrackTokenSnippet'      => 'Tracker\\DeleteTrackTokenSnippet',
                'EditTrackEngineSnippet'       => 'Tracker\\EditTrackEngineSnippet',
                'EditTrackSnippet'             => 'Tracker\\EditTrackSnippet',
                'EditTrackTokenSnippet'        => 'Token\\EditTrackTokenSnippet',
                'ShowTrackTokenSnippet'        => 'Token\\ShowTrackTokenSnippet',
                'SurveyQuestionsSnippet'       => 'Survey\\SurveyQuestionsSnippet',
                'TrackSurveyOverviewSnippet'   => 'Tracker\\TrackSurveyOverviewSnippet',
                'TrackTokenOverviewSnippet'    => 'Tracker\\TrackTokenOverviewSnippet',
                'TrackUsageTextDetailsSnippet' => 'Tracker\\TrackUsageTextDetailsSnippet',
                );

            foreach ($movedSnippets as $oldSnippet => $newSnippet) {
                if (($oldSnippet . '.php' == $fileinfo->getFilename()) &&
                        (! \MUtil_String::endsWith($fileinfo->getPathname(), $newSnippet . '.php'))) {

                    $messages[] = "This snippet is moved to $newSnippet.";
                }
            }
        }

        $obsoleteFields = array(
            'gtr_track_type',
            'gtr_track_name'        => 'calc_track_name',
            'gr2t_track_info'       => 'calc_track_info',
            'gto_round_description' => 'calc_round_description',
            );

        foreach ($obsoleteFields as $replacement => $old) {
            if (\MUtil_String::contains($content, $old)) {
                if (is_integer($replacement)) {
                    $messages[] = "Contains a reference to the obsolete '$old' field/variable.";
                } else {
                    $messages[] = "Contains a reference to the '$old' field/variable, replace it with '$replacement'.";
                }
            }
        }

        if (! $messages) {
            return false;
        }

        $this->html->h2(sprintf('Report on file %s', substr($fileinfo->getPathname(), strlen(GEMS_ROOT_DIR) + 1)));
        foreach ($messages as $message) {
            $this->html->pInfo($message);
        }

        return true;
    }

    /**
     * A reports on code files
     */
    protected function addFileReports()
    {
        $output = false;
        foreach ($this->getRecursiveDirectoryIterator(APPLICATION_PATH) as  $filename) {
            $output = $this->addFileReport($filename) || $output;
        }
        if (! $output) {
            $this->html->pInfo('No compatibility issues found in the code for this project.');
        }
    }

    protected function getRecursiveDirectoryIterator($dir)
    {
        return new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::CURRENT_AS_FILEINFO),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
                );
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
        $this->html = $this->getHtmlSequence();

        $this->html->h1('Upgrade compatibility report');

        $this->addEscortReport();

        $this->addFileReports();

        return $this->html;
    }
}
