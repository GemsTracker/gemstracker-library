<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Upgrade
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
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
class UpgradeCompatibilitySnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     * When true there is a namespace error in the application code
     *
     * @var boolean
     */
    protected $appNamespaceError = false;

    /**
     * @var array
     */
    protected $config;

    /**
     * The current version of the code
     *
     * @var int
     */
    protected $codeVersion;

    /**
     *
     * @var \MUtil\Html\Sequence
     */
    protected $html;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * The snippets that have moved
     *
     * @var array
     */
    protected $movedSnippets = array(
        'AddTracksSnippet'                        => 'Tracker\\AddTracksSnippet',
        'DeleteInSourceTrackSnippet'              => 'Tracker\\DeleteTrackSnippet',
        'DeleteTrackTokenSnippet'                 => 'Tracker\\DeleteTrackTokenSnippet',
        'EditRoundSnippet'                        => 'Tracker\\Rounds\\EditRoundStepSnippet',
        'EditRoundStepSnippet'                    => 'Tracker\\Rounds\\EditRoundStepSnippet',
        'EditTrackEngineSnippet'                  => 'Tracker\\EditTrackEngineSnippet',
        'EditTrackSnippet'                        => 'Tracker\\EditTrackSnippet',
        'EditTrackTokenSnippet'                   => 'Token\\EditTrackTokenSnippet',
        'Export_SurveyAutosearchFormSnippet'      => 'Export\\SurveyExportSearchFormSnippet',
        'Export\\ExportSnippet'                   => null,
        'Export\\ExportSurveysFormSnippet'        => 'Export\\MultiSurveysSearchFormSnippet',
        'Organization_ChooseOrganizationSnippet'  => 'Organization\\ChooseOrganizationSnippet',
        'Organization_OrganizationEditSnippet'    => 'Organization\\OrganizationEditSnippet',
        'Organization_OrganizationTableSnippet'   => 'Organization\\OrganizationTableSnippet',
        'Respondent_RoundTokenSnippet'            => 'Token\\RoundTokenSnippet',
        'RespondentDetailsSnippet'                => 'Respondent\\RespondentDetailsSnippet',
        'RespondentDetailsWithAssignmentsSnippet' => 'Respondent\\DetailsWithAssignmentsSnippet',
        'RespondentFormSnippet'                   => 'Respondent\\RespondentFormSnippet',
        'RespondentSearchSnippet'                 => 'Respondent\\RespondentSearchSnippet',
        'RespondentTokenSnippet'                  => 'Token\\RespondentTokenSnippet',
        'RespondentTokenTabsSnippet'              => 'Token\\TokenTabsSnippet',
        'SelectedTokensTitleSnippet'              => null,
        'ShowRoundSnippet'                        => 'Tracker\\Rounds\\ShowRoundStepSnippet',
        'ShowRoundStepSnippet'                    => 'Tracker\\Rounds\\ShowRoundStepSnippet',
        'ShowTrackTokenSnippet'                   => 'Token\\ShowTrackTokenSnippet',
        'ShowTrackUsageSnippet'                   => 'Tracker\\ShowTrackUsageSnippet',
        'SurveyQuestionsSnippet'                  => 'Survey\\SurveyQuestionsSnippet',
        'TokenDateSelectorSnippet'                => 'Token\\TokenDateSelectorSnippet',
        'TokenNotFoundSnippet'                    => 'Token\\TokenNotFoundSnippet',
        'TrackSurveyOverviewSnippet'              => 'Tracker\\TrackSurveyOverviewSnippet',
        'TrackTokenOverviewSnippet'               => 'Tracker\\TrackTokenOverviewSnippet',
        'TrackUsageTextDetailsSnippet'            => 'Tracker\\TrackUsageTextDetailsSnippet',
        'Track_Token_RedirectUntilGoodbyeSnippet' => 'Ask\\RedirectUntilGoodbyeSnippet',
        'Track_Token_ShowAllOpenSnippet'          => 'Ask\\ShowAllOpenSnippet',
        'Track_Token_ShowFirstOpenSnippet'        => 'Ask\\ShowFirstOpenSnippet',
        );

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * The prefix / classnames where the \Gems Loaders should look
     *
     * @var array prefix => path
     */
    protected $projectDirs;

    /**
     * Renamed variables
     *
     * @var array Nested [filenamePart => [oldName => newName]]
     */
    protected $variablesChanged = [
        'Model_Translator' => [
            'dateFormat'     => 'dateFormats',
            'datetimeFormat' => 'datetimeFormats',
            'timeFormat'     => 'timeFormats',
            ],
        'Tracker\\Survey' => [
            '_gemsSurvey' => '_data',
            '_surveyId' => '_id',
            ],
        ];

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
            '\\ArrayIterator',
            '\\ArrayObject',
            'Closure',
            'Countable',
            'DirectoryIterator',
            'Exception',
            'FilesystemIterator',
            'FilterIterator',
            'Generator',
            '\\Iterator', // also checks for 'IteratorAggregate',
            'OuterIterator',
            'RecursiveDirectoryIterator',
            'RecursiveIterator', // also checks for 'RecursiveIteratorIterator',
            'SeekableIterator',
            'Serializable',
            'SplFileInfo',
            'SplFileObject',
            'Throwable',
            '\\Traversable',
            );

        foreach ($phpObjects as $className) {
            if (preg_match("/[^_\"'\\\\a-z]" . $className . "[^_\"'\\\\a-zA-Z]/", $content)) {
                $this->appNamespaceError = true;
                $messages[] = "The code in this file contains a not namespace proof reference to '$className', prefix a \\.";
            }
        }

        $gtObjects = array(
            'Gems_' => 'Gems',
            'MUtil_' => 'MUtil',
        ) + $this->projectDirs;
        // Remove the class statements
        $noClass = $this->_filterContent($content);

        foreach ($gtObjects as $search => $className) {
            if (preg_match("/[^_\"'\\\\a-z]$search/", $noClass)) {
                $this->appNamespaceError = true;
                $messages[] = "The code in this file contains a not namespace proof reference to '$className', prefix a \\.";
            }
        }

        foreach ($this->movedSnippets as $old => $new) {
            if (preg_match('/[\'"]' . $old . '[\'"]/', $content)) {
                if ($new) {
                    $messages[] = "This file appears to use the '$old' snippet, that was changed to the '$new' snippet.";
                } else {
                    $messages[] = "This file appears to use the '$old' snippet, that is no longer in use.";
                }
            }
        }

        $obsFunctions = array(
            'Gems\\Agenda\\Filter\\FieldLikeAppointmentFilter' => [
                'getAppointmentFieldVale'   => 'getAppointmentFieldValue',
                ],
            'Gems\Tracker_Source' => [
                'getFullQuestionList'       => 'getQuestionList',
                ],
            '\\Gems\\Mail\\MailLoader' => [
                'getAvailableMailTemplates' => 'CommTemplateUtil->getCommTemplatesForTarget',
                ],
            '\\Gems\\Project\\ProjectSettings' => [
                'getAllowedHosts' => 'SiteUtil->isRequestFromAllowedHost',
                'getConsoleUrl' => 'Gems\User\Organization->getPreferredSiteUrl',
                'hasAnySupportUrl' => null,
                'hasBugsUrl' => null,
            ],
            '\\Gems\\User\\User' => [
                // 'getGroup'                  => 'getGroupId', // REMOVE IN 1.8.3
                'hasAllowedRole'            => 'inAllowedGroup',
                'refreshAllowedStaffGroups' => null,
                ],
            '\\Gems\\User\\UserLoader' => [
                'getOrganizationIdByUrl' => null,
                'getOrganizationUrls'    => 'SiteUrl->getSiteForCurrentUrl', 
            ],
            'Gems_User_LoginStatusTracker' => [
                'getUsedOrganisationId'     => 'getUsedOrganizationId',
                ],
            '\\Gems\\Util\\DbLookup' => [
                'getFilterForMailJob' => 'MailJobsUtil->getJobFilter',
                'getSurveys' => 'Gems\Util\TrackData->getSurveysFor',
                ],
            '\\Gems\\Util\\Translated' => [
                'formatDateTime'                 => 'describeDateFromNow',
                'getBulkMailProcessOptions'      => 'MailJobsUtil->getBulkProcessOptions',
                'getBulkMailProcessOptionsShort' => 'MailJobsUtil->getBulkProcessOptionsShort',
                'getBulkMailTargetOptions'       => 'MailJobsUtil->getBulkTargetOptions',
                ],
            '\\Gems\\User\\Form\\LayeredLoginForm' => [
                'getChildOrganisations'     => 'getChildOrganizations',
                'getTopOrganisations'       => 'getTopOrganizations',
                ],
        );
        foreach ($obsFunctions as $className => $functions) {
            foreach ($functions as $funcName => $replacement) {
                if (preg_match(sprintf('/->%s\(/', preg_quote($funcName)), $content)) {
                    if ($replacement) {
                        $messages[] = "The code in this file seems to use the obsolete class $className function '$funcName()'. Use the '$replacement()' function instead.";
                    } else {
                        $messages[] = "The code in this file seems to use the obsolete class $className function '$funcName()'.";
                    }
                }
            }
        }

        $obsVariables = array(
            '$this->respondentData' => '$this->respondent->getArrayCopy()',
            '$this->session' => '$this->currentUser',
            '$this->_organizationFromUrl' => null,
        );
        foreach ($obsVariables as $varName => $replacement) {
            if (preg_match(sprintf('/%s/', preg_quote($varName)), $content)) {
                if ($replacement) {
                    $messages[] = "The code in this file uses the obsolete class variable '$varName'. Use '$replacement' instead.";
                } else {
                    $messages[] = "The code in this file uses the obsolete class variable '$varName'.";
                }
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

            case 'CommJobController.php':
                if (preg_match('/\\sfunction\\s+getBulkMailFilterOptions\\s*\\(/', $content)) {
                    $messages[] = "Your CommJob controller has a getBulkMailFilterOptions() function. "
                            . "This function was moved to \Gems\\Util\\MailJobsUtil->getBulkFilterOptions(). "
                            . "Remove the function and implement a project\\Util\\MailJobsUtil.php";
                }
                if (preg_match('/\\sfunction\\s+getBulkMailFromOptions\\s*\\(/', $content)) {
                    $messages[] = "Your CommJob controller has a getBulkMailFromOptions() function. "
                            . "This function was moved to \Gems\\Util\\MailJobsUtil->getBulkFromOptions(). "
                            . "Remove the function and implement a project\\Util\\MailJobsUtil.php";
                }
                return;

            case 'ExportController.php':
                $messages[] = "This controller was renamed to ExportSurveyController.";
                return;

            case 'ExportSurveysController.php':
                $messages[] = "This controller was renamed to ExportMultiSurveysController.";
                $messages[] = "The new controller is a child of \Gems\Controller\ModelSnippetActionAbstract. Check your code for changes.";
                return;

            case 'RespondentController.php':
                if (preg_match(
                        '/class\\s+RespondentController\\s+extends\\s+\\\\?Gems_Default_RespondentAction/', $content)
                        ) {
                    $messages[] = array(
                        "Your respondent controller seems to inherit from Gems_Default_RespondentAction.",
                        ' ',
                        \MUtil\Html::create('strong', "This class may be obsolete in 1.7.2!"),
                        ' ',
                        "Use \Gems\Actions\RespondentNewAction instead.",
                        );
                }
                break;

            case 'TrackController.php':
                if (preg_match('/\\sfunction\\s+correctAction\\s*\\(/', $content) &&
                        (! preg_match('/\\sparent::correctAction\\s*\\(/', $content))) {
                    $messages[] = "Your track controller has a correctAction() function. "
                            . "This function is now part of the GemsTracker core. "
                            . "Remove the function or make sure it calls parent::correctAction().";
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
                    'RespondentPlanHandler'        => 58,
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
                        $messages[] = \MUtil\Html::create('strong', "Check all code in the controller!");
                        break;
                    }
                }
        }
        if (preg_match('/\\s(protected|private) function\\s+getRespondent\\s*\\(/', $content)) {
            $messages[] = "This controller should change it's getRespondent method to be public.";
        }

        if (preg_match('/\\sextends\\s+\\\\?Gems\Controller\BrowseEditAction\\s/', $content)) {
            $messages[] = "This controller extends from the deprecated \Gems\Controller\BrowseEditAction.";
            $messages[] = \MUtil\Html::create('strong', "Rewrite the controller!");
        }
    }

    /**
     *
     * @param \SplFileInfo $fileinfo
     * @param string $content
     * @param array $messages
     */
    protected function _checkDepreciations(\SplFileInfo $fileinfo, $content, array &$messages)
    {
        $matches = [];
        if (preg_match('/\\s@deprecated\\s+([^\\n]+)/', $content, $matches)) {
            $gemsVersion = $this->loader->getVersions()->getGemsVersion();

            array_shift($matches);
            foreach($matches as $match) {
                $version = trim(\MUtil\StringUtil\StringUtil::stripStringLeft($match, 'since version'));

                if (\MUtil\StringUtil\StringUtil::contains($version, ' ')) {
                    list($version, $comment) = explode(' ', $version, 2);
                } else {
                    $comment = '';
                }
                if (version_compare($gemsVersion, $version, '>')) {
                    $messages[] = "This file has a deprecated statement: " . $version . ' ' . $comment;
                }
            }
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
            if (\MUtil\StringUtil\StringUtil::endsWith($filePathName, str_replace('\\', DIRECTORY_SEPARATOR, 'Snippets\\' . $oldSnippet) . '.php') &&
                    (! \MUtil\StringUtil\StringUtil::endsWith($filePathName, str_replace('\\', DIRECTORY_SEPARATOR, 'Snippets\\' . $newSnippet) . '.php'))) {

                if ($newSnippet) {
                    $messages[] = "This snippet is moved to $newSnippet.";
                } else {
                    $messages[] = "This snippet is no longer in use.";
                }
                break;
            }
        }

        $deletedSnippets = array(
            'Respondent\\MailLogSnippet',
            );
        foreach ($deletedSnippets as $oldSnippet) {
            if (\MUtil\StringUtil\StringUtil::endsWith($filePathName, $oldSnippet . '.php')) {
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
            'gr2o_email'            => 'grs_email',
            'gr2t_track_info'       => 'calc_track_info',
            'gto_round_description' => 'calc_round_description',
            'gsf_password',
            'gsf_failed_logins',
            'gsf_last_failed',
            'gsf_reset_key',
            'gsf_reset_req',
            'gtr_track_type',
            'gtr_track_name'        => 'calc_track_name',
            );

        foreach ($obsoleteFields as $replacement => $old) {
            if (\MUtil\StringUtil\StringUtil::contains($content, $old)) {
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
            if (\MUtil\StringUtil\StringUtil::contains($content, $table)) {
                $messages[] = "Contains a reference to the obsolete '$table' database table.";
            }
            foreach ($fields as $field) {
                if (\MUtil\StringUtil\StringUtil::contains($content, $field)) {
                    $messages[] = "Contains a reference to the obsolete '$field' field in the '$table' database table.";
                }
            }
        }
    }

    /**
     *
     * @param \SplFileInfo $fileinfo
     * @param string $content
     * @param array $messages
     */
    protected function _checkVariablesChanged(\SplFileInfo $fileinfo, $content, array &$messages)
    {
        $filePathName = $fileinfo->getPathname();

        foreach ($this->variablesChanged as $pathPart => $replacements) {
            if (\MUtil\StringUtil\StringUtil::contains($filePathName, str_replace('_', DIRECTORY_SEPARATOR, $pathPart))) {
                foreach ($replacements as $old => $new) {
                    if (preg_match("/(\\\$|->)$old\\W/", $content)) {
                        $messages[] = "This file uses the \$$old variable that is renamed to \$$new.";
                    }
                }
            }
        }
    }

    /**
     * Return the filtered content to reduce false positives
     *
     * @param string $content
     * @return string
     */
    protected function _filterContent($content)
    {
        return preg_replace('/(class|use)\\s+([^\\s]+)/', '', $content);
    }

    /**
     * Return the filenames that need to be checked
     *
     * @return \SplFileinfo[]
     */
    protected function _getFilenames()
    {
        foreach ($this->getRecursiveDirectoryIterator($this->config['rootDir']) as $filename) {
            $files[] = $filename;
        }

        return $files;
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

        if (\MUtil\StringUtil\StringUtil::endsWith($fileinfo->getPath(), 'controllers')) {
            $this->_checkControllersChanged($fileinfo, $content, $messages);
        } else {
            $this->_checkSnippetsChanged($fileinfo, $content, $messages);
        }

        $this->_checkTablesChanged($fileinfo, $content, $messages);
        $this->_checkVariablesChanged($fileinfo, $content, $messages);
        $this->_checkDepreciations($fileinfo, $content, $messages);

        if (! $messages) {
            return false;
        }

        $this->html->h3(sprintf('Report on file %s', substr($fileinfo->getPathname(), strlen($this->config['rootDir']) + 1)));
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
        $filenames = $this->_getFilenames();
        foreach ($filenames as  $filename) {
            $output = $this->addFileReport($filename) || $output;
        }
        if ($this->appNamespaceError) {

            $applicationPath = $this->config['rootDir'] . '/src';
            $gemsLibraryPath = $this->config['rootDir']. '/vendor/gemstracker/gemstracker';
            $sCode->h3('Code change issues found');
            $sCode->pInfo('The application code has code change issues. You can try to fix them by running this phing script:');
            $sCode->pre(
                    'cd ' . $applicationPath . "\n" .
                    'phing -f ' . $gemsLibraryPath . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'namespacer.xml'
                    );
            $p = $sCode->pInfo('To use this script you have to install ');
            $p->a('https://www.phing.info/', 'Phing');
            $p->append('. Then run the script and check again for issues not fixed by the script.');

        } elseif (! $output) {
            $this->html->h3('Code change report');
            $this->html->pInfo('No compatibility issues found in the code for this project.');
        }
    }

    /**
     * A reports on the project ini
     */
    protected function addProjectIniReport()
    {
        $h3     = $this->html->h3();
        $issues = false;
        if (! $this->project->offsetExists('headers')) {
            $this->html->pInfo('No headers section found.');
            $issues = true;
        } else {
            $headers = $this->project->getResponseHeaders();
            if (isset($headers['Content-Security-Policy'])) {
                // Split by -src(-xxx)
                preg_match_all("/(\w+-src(-\w+)?) ([^;]+);/", $headers['Content-Security-Policy'], $r);
                $csp = array_combine($r[1], $r[3]);
                // \MUtil\EchoOut\EchoOut::track($csp);

                /*
                if (! \MUtil\StringUtil\StringUtil::contains($csp['script-src'], " 'nonce-\$scriptNonce' ")) {
                    $this->html->pInfo('Content-Security-Policy script-src \'nonce-\$scriptNonce\' setting missing. This is unsafe!');
                    $issues = true;
                } // */
                if (! \MUtil\StringUtil\StringUtil::contains($csp['img-src'], ' data: ')) {
                    if (\MUtil\StringUtil\StringUtil::contains($csp['img-src'], ' data ')) {
                        $this->html->pInfo('Content-Security-Policy uses data instead of data:!');
                    }
                    $this->html->pInfo('Content-Security-Policy img-src data: setting missing, 2FA QR codes will not work!');
                    $issues = true;
                }
                if (! isset($csp['object-src'])) {
                    $this->html->pInfo("Content-Security-Policy object-src missing, add: object-src 'none';");
                    $issues = true;
                }
            } else {
                $this->html->pInfo('No headers.Content-Security-Policy set!');
                $issues = true;
            }
        }
        if (!$this->project->offsetExists('meta')) {
            $this->html->pInfo('No meta headers section found.');
            $issues = true;
        } else {
            if (isset($this->project['meta']['Content-Security-Policy'])) {
                $this->html->pInfo('meta.Content-Security-Policy should be moved to headers section');
                $issues = true;
            }
            if (isset($this->project['meta']['Strict-Transport-Security'])) {
                $this->html->pInfo('meta.Strict-Transport-Security should be moved to headers section');
                $issues = true;
            }
        }

        /*
        if (isset($this->project['headers']['Content-Security-Policy']) &&
                preg_match('/img-src\s.*?data:.*?;/', $this->project['headers']['Content-Security-Policy']) !== 1) {
            $this->html->pInfo('The headers.Content-Security-Policy setting img-src should have data: for Two Factor Authentication.');
            $issues = true;
        } // */
        if ($this->project->offsetExists('jquerycss')) {
            $this->html->pInfo('Separate JQuery CSS no longer in use. Remove jquerycss setting.');
            $issues = true;
        }

        if (! isset($this->project['security'], $this->project['security']['methods'])) {
            $this->html->pInfo('No OpenSSL cipher methods defined in security.methods.');
            $issues = true;
        }
        if ($this->project->offsetExists('allowedSourceHosts')) {
            $this->html->pInfo('allowedSourceHosts are replaced by Setup->Access->Sites. You can remove allowedSourceHosts after the update has been executed.');
            $issues = true;
        }
        if (isset($this->project['console']['url'])) {
            $this->html->pInfo('console.url is replaced by Setup->Access->Sites. You can remove console.url after the update has been executed.');
            $issues = true;
        }


        if ($issues) {
            $h3->append('Project.ini issues found');
            $this->html->pInfo()->strong('See project.example.ini for examples of fixes.');
        } else {
            $h3->append('Project.ini report');
            $this->html->pInfo('No compatibility issues found in project.ini.');
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
        /*foreach ($this->escort->getLoaderDirs() as $prefix => $dir) {
            $this->projectDirs[str_replace('\\', '\\\\', $prefix) . '_'] = $prefix;
        }*/
    }

    /**
     * \Iterator for looping thorugh all files in a directory and i's sub directories
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
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $this->html = $this->getHtmlSequence();

        $versions = $this->loader->getVersions();
        $this->html->h2(sprintf(
                'Upgrade compatibility report for GemsTracker %s, build %d',
                $versions->getGemsVersion(),
                $versions->getBuild()
                ));

        $this->addProjectIniReport();

        $this->addFileReports();

        return $this->html;
    }
}