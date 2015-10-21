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
     * When true there is a namespace error in the application code
     *
     * @var boolean
     */
    protected $appNamespaceError = false;

    /**
     * The current version of the code
     *
     * @var int
     */
    protected $codeVersion;

    /**
     *
     * @var \GemsEscort
     */
    protected $escort;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The snippets that have moved
     * @var array
     */
    protected $movedSnippets = array(
        'AddTracksSnippet'                        => 'Tracker\\AddTracksSnippet',
        'EditRoundStepSnippet'                    => 'Tracker\\Rounds\\EditRoundStepSnippet',
        'ShowRoundStepSnippet'                    => 'Tracker\\Rounds\\ShowRoundStepSnippet',
        'DeleteInSourceTrackSnippet'              => 'Tracker\\DeleteTrackSnippet',
        'DeleteTrackTokenSnippet'                 => 'Tracker\\DeleteTrackTokenSnippet',
        'EditTrackEngineSnippet'                  => 'Tracker\\EditTrackEngineSnippet',
        'EditTrackSnippet'                        => 'Tracker\\EditTrackSnippet',
        'EditTrackTokenSnippet'                   => 'Token\\EditTrackTokenSnippet',
        'Organization_ChooseOrganizationSnippet'  => 'Organization\\ChooseOrganizationSnippet',
        'Organization_OrganizationEditSnippet'    => 'Organization\\OrganizationEditSnippet',
        'Organization_OrganizationTableSnippet'   => 'Organization\\OrganizationTableSnippet',
        'ShowTrackTokenSnippet'                   => 'Token\\ShowTrackTokenSnippet',
        'SurveyQuestionsSnippet'                  => 'Survey\\SurveyQuestionsSnippet',
        'TokenDateSelectorSnippet'                => 'Token\\TokenDateSelectorSnippet',
        'TrackSurveyOverviewSnippet'              => 'Tracker\\TrackSurveyOverviewSnippet',
        'TrackTokenOverviewSnippet'               => 'Tracker\\TrackTokenOverviewSnippet',
        'TrackUsageTextDetailsSnippet'            => 'Tracker\\TrackUsageTextDetailsSnippet',
        'Track_Token_RedirectUntilGoodbyeSnippet' => 'Ask\\RedirectUntilGoodbyeSnippet',
        'Track_Token_ShowAllOpenSnippet'          => 'Ask\\ShowAllOpenSnippet',
        'Track_Token_ShowFirstOpenSnippet'        => 'Ask\\ShowFirstOpenSnippet',
        );

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * The prefix / classnames where the Gems Loaders should look
     *
     * @var array prefix => path
     */
    protected $projectDirs;

    /**
     *
     * @var \MUtil_Html_Sequence
     */
    protected $html;

    /**
     *
     * @param \SplFileInfo $fileinfo
     * @param string $content
     * @param array $messages
     */
    protected function _checkCodingChanged(\SplFileInfo $fileinfo, $content, array &$messages)
    {
        $phpObjects = array(
            'ArrayAccess',
            'ArrayIterator',
            'ArrayObject',
            'Closure',
            'Countable',
            'DirectoryIterator',
            'Exception',
            'FilesystemIterator',
            'FilterIterator',
            'GemsEscort',
            'Generator',
            'Iterator', // also checks for 'IteratorAggregate',
            'OuterIterator',
            'RecursiveDirectoryIterator',
            'RecursiveIterator', // also checks for 'RecursiveIteratorIterator',
            'SeekableIterator',
            'Serializable',
            'SplFileInfo',
            'SplFileObject',
            'Throwable',
            'Traversable',
            );

        foreach ($phpObjects as $className) {
            if (preg_match("/[^_\"'\\\\a-z]$className/", $content)) {
                $this->appNamespaceError = true;
                $messages[] = "The code in this file contains a not namespace proof reference to '$className', prefix a \\.";
            }
        }

        $gtObjects = array(
            'Gems_' => 'Gems',
            'MUtil_' => 'MUtil',
        ) + $this->projectDirs;
        // Remove the class statements
        $noClass = preg_replace('/class\\s+([^\\s]+)/', '', $content);

        foreach ($gtObjects as $search => $className) {
            if (preg_match("/[^_\"'\\\\a-z]$search/", $noClass)) {
                $this->appNamespaceError = true;
                $messages[] = "The code in this file contains a not namespace proof reference to '$className', prefix a \\.";
            }
        }

        foreach ($this->movedSnippets as $old => $new) {
            if (preg_match('/[\'"]' . $old . '[\'"]/', $content)) {
                $messages[] = "This controller appears to use the '$old' snippet, that was changed to the '$new' snippet.";
            }
        }
    }

    /**
     *
     * @param \SplFileInfo $fileinfo
     * @param string $content
     * @param array $messages
     */
    protected function _checkControllersChanged(\SplFileInfo $fileinfo, $content, array &$messages)
    {
        $fileName = $fileinfo->getFilename();
        switch ($fileName) {
            case 'MailJobController.php':
            case 'MailTemplateController':
            case 'SurveyController.php':
                $messages[] = "You can delete this file. This controller is no longer in use.";
                return;

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
                    'ConsentController'           => 57,
                    'DatabaseController'          => 57,
                    'LogController'               => 57,
                    'LogMaintenanceController'    => 57,
                    'OptionController'            => 58,
                    'OverviewPlanController'      => 58,
                    'ProjectSurveysController'    => 57,
                    'ProjectTracksController'     => 57,
                    'ReceptionController'         => 57,
                    'RespondentPlanAction'        => 58,
                    'RoleController'              => 57,
                    'StaffController'             => 58,
                    'SurveyMaintenanceController' => 57,
                    'TokenPlanController'         => 58,
                    'TrackController'             => 57,
                    'TrackMaintenanceController'  => 58,
                    'TrackRoundsController'       => 57,
                    );
                foreach ($changedControllers as $controller => $version) {
                    if (($version == $this->codeVersion) &&
                            ($controller . '.php' == $fileName)) {
                        $messages[] = "The parent class changed from BrowseEditAction to ModelSnippetActionAbstract.";
                        $messages[] = \MUtil_Html::create('strong', "Check all code in the controller!");
                        break;
                    }
                }
        }
        if (preg_match('/\\sextends\\s+\\\\?Gems_Controller_BrowseEditAction\\s/', $content)) {
            $messages[] = "This controller extends from the deprecated Gems_Controller_BrowseEditAction.";
            $messages[] = \MUtil_Html::create('strong', "Rewrite the controller!");
        }
    }

    /**
     *
     * @param \SplFileInfo $fileinfo
     * @param string $content
     * @param array $messages
     */
    protected function _checkSnippetsChanged(\SplFileInfo $fileinfo, $content, array &$messages)
    {
        $filePathName = $fileinfo->getPathname();

        foreach ($this->movedSnippets as $oldSnippet => $newSnippet) {
            if (\MUtil_String::endsWith($filePathName, $oldSnippet . '.php') &&
                    (! \MUtil_String::endsWith($filePathName, $newSnippet . '.php'))) {

                $messages[] = "This snippet is moved to $newSnippet.";
                break;
            }
        }

        $deletedSnippets = array(
            'Respondent\\MailLogSnippet',
            );
        foreach ($deletedSnippets as $oldSnippet) {
            if (\MUtil_String::endsWith($filePathName, $oldSnippet . '.php')) {
                $messages[] = "This snippet is no longer in use.";
                break;
            }
        }
    }

    /**
     *
     * @param \SplFileInfo $fileinfo
     * @param string $content
     * @param array $messages
     */
    protected function _checkTablesChanged(\SplFileInfo $fileinfo, $content, array &$messages)
    {
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

        $obsoleteTables = array(
            'gems__log_actions' => array(
                'glac_id_action',
                'glac_name',
                'glac_change',
                'glac_log',
                'glac_created',
                ),
            'gems__log_useractions' => array(
                'glua_id_action',
                'glua_to',
                'glua_by',
                'glua_organization',
                'glua_action',
                'glua_message',
                'glua_role',
                'glua_remote_ip',
                'glua_created',
                ),
            'gems__mail_jobs' => array(
                'gmj_id_job',
                'gmj_id_message',
                'gmj_id_user_as',
                'gmj_active',
                'gmj_from_method',
                'gmj_from_fixed',
                'gmj_process_method',
                'gmj_filter_mode',
                'gmj_filter_days_between',
                'gmj_filter_max_reminders',
                'gmj_id_organization',
                'gmj_id_track',
                'gmj_id_survey',
                'gmj_changed',
                'gmj_changed_by',
                'gmj_created',
                'gmj_created_by',
                ),
            'gems__mail_templates' => array(
                'gmt_id_message',
                'gmt_subject',
                'gmt_body',
                'gmt_organizations',
                'gmt_changed',
                'gmt_changed_by',
                'gmt_created',
                'gmt_created_by',
                ),
            );

        foreach ($obsoleteTables as $table => $fields) {
            if (\MUtil_String::contains($content, $table)) {
                $messages[] = "Contains a reference to the obsolete '$table' database table.";
            }
            foreach ($fields as $field) {
                if (\MUtil_String::contains($content, $field)) {
                    $messages[] = "Contains a reference to the obsolete '$field' field in the '$table' database table.";
                }
            }
        }
    }

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
            'Gems_Project_Log_LogRespondentAccessInterface',
            'Gems_Project_Organization_MultiOrganizationInterface',
            'Gems_Project_Organization_SingleOrganizationInterface',
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

        $this->_checkCodingChanged($fileinfo, $content, $messages);

        if (\MUtil_String::endsWith($fileinfo->getPath(), 'controllers')) {
            $this->_checkControllersChanged($fileinfo, $content, $messages);
        } else {
            $this->_checkSnippetsChanged($fileinfo, $content, $messages);
        }

        $this->_checkTablesChanged($fileinfo, $content, $messages);

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
        $sCode = $this->html->sequence();

        $output = false;
        foreach ($this->getRecursiveDirectoryIterator(APPLICATION_PATH) as  $filename) {
            $output = $this->addFileReport($filename) || $output;
        }
        if ($this->appNamespaceError) {
            $sCode->pInfo('The application code has code change issues. You can try to fix them by running this phing script:');
            $sCode->pre(
                    'cd ' . APPLICATION_PATH . "\n" .
                    'phing -f ' . GEMS_LIBRARY_DIR . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'namespacer.xml'
                    );
            $p = $sCode->pInfo('To use this script you have to install ');
            $p->a('https://www.phing.info/', 'Phing');
            $p->append('. Then run the script and check again for issues not fixed by the script.');

        } elseif (! $output) {
            $this->html->pInfo('No compatibility issues found in the code for this project.');
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->codeVersion = $this->loader->getVersions()->getBuild();
        foreach ($this->escort->getLoaderDirs() as $prefix => $dir) {
            $this->projectDirs[$prefix . '_'] = $prefix;
        }
    }

    /**
     * Iterator for looping thorugh all files in a directory and i's sub directories
     *
     * @param string $dir
     * @return \RecursiveIteratorIterator
     */
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

        $versions = $this->loader->getVersions();
        $this->html->h1(sprintf(
                'Upgrade compatibility report for GemsTracker %s, build %d',
                $versions->getGemsVersion(),
                $versions->getBuild()
                ));

        $this->addEscortReport();

        $this->addFileReports();

        return $this->html;
    }
}
