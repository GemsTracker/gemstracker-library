<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Menu\MenuSnippetHelper;
use Gems\Project\ProjectSettings;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Engine\TrackEngineInterface;
use MUtil\Form\Element\ToggleCheckboxes;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Basic snippet for editing track engines instances
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class EditTrackEngineSnippetGeneric extends ModelFormSnippetAbstract
{
    /**
     *
     * @var string Field for storing the old track class
     */
    protected string $_oldClassName = 'old__gtr_track_class';

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var TrackEngineInterface|null
     */
    protected ?TrackEngineInterface $trackEngine = null;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int TrackId
     */
    protected ?int $trackId = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected Tracker $tracker,
        protected ProjectSettings $project
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param FormBridgeInterface $bridge
     * @param FullDataInterface $dataModel
     */
    protected function addBridgeElements(FormBridgeInterface $bridge, FullDataInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();

        if (! $this->createData) {
            $bridge->addHidden('gtr_id_track');
            $bridge->addHidden('table_keys');
        }
        $bridge->addText('gtr_track_name');
        if ($this->project->translateDatabaseFields()) {
            $bridge->addFormTable('translations_gtr_track_name');
        }
        $bridge->addText('gtr_external_description');
        if ($this->project->translateDatabaseFields()) {
            $bridge->addFormTable('translations_gtr_external_description');
        }


        // gtr_track_class
        if ($this->trackEngine) {
            $options      = $dataModel->get('gtr_track_class', 'multiOptions');
            /*$alternatives = $this->trackEngine->getConversionTargets($options);
            if (count($alternatives) > 1) {
                $options = $alternatives;

                $bridge->addHidden($this->_oldClassName);

                if (! isset($this->formData[$this->_oldClassName])) {
                    $this->formData[$this->_oldClassName] = $this->formData['gtr_track_class'];
                }

                $classEdit = true;
            } else {*/
                $classEdit = false;
            //}
        } else {
            $options = $this->tracker->getTrackEngineList(true, true);
            $classEdit = true;
        }
        $metaModel->set('gtr_track_class', 'multiOptions', $options, 'escape', false);
        if ($classEdit) {
            $bridge->addRadio(    'gtr_track_class');
        } else {
            $bridge->addExhibitor('gtr_track_class');
        }

        $bridge->addDate('gtr_date_start');
        $bridge->addDate('gtr_date_until');
        //if (! $this->createData) {
            $bridge->addCheckbox('gtr_active');
        //}
        if ($metaModel->has('gtr_code')) {
            $bridge->addText('gtr_code');
        }
        if ($metaModel->has('gtr_calculation_event', 'label')) {
            $bridge->add('gtr_calculation_event');
        }
        if ($metaModel->has('gtr_completed_event', 'label')) {
            $bridge->add('gtr_completed_event');
        }
        if ($metaModel->has('gtr_beforefieldupdate_event', 'label')) {
            $bridge->add('gtr_beforefieldupdate_event');
        }
        if ($metaModel->has('gtr_fieldupdate_event', 'label')) {
            $bridge->add('gtr_fieldupdate_event');
        }
        $bridge->add('gtr_organizations');

        $element = new ToggleCheckboxes('toggleOrg', ['selectorName' => 'gtr_organizations']);
        $element->setLabel(sprintf('Toggle %s',$metaModel->get('gtr_organizations', 'label')));
        $bridge->addElement($element);
    }

    /**
     * Creates the model
     *
     * @return FullDataInterface
     */
    protected function createModel(): FullDataInterface
    {
        $model = $this->tracker->getTrackModel();
        $model->applyFormatting(true, true);

        return $model;
    }

    /**
     *
     * @return array
     * /
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    } // */

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle(): string
    {
        if ($this->createData) {
            return $this->_('Add new track');
        } else {
            return parent::getTitle();
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here, but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if ($this->trackEngine && (! $this->trackId)) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        if ($this->trackId) {
            // We are updating
            $this->createData = false;

            // Try to get $this->trackEngine filled
            if (! $this->trackEngine) {
                // Set the engine used
                $this->trackEngine = $this->tracker->getTrackEngine($this->trackId);
            }

        } else {
            // We are inserting
            $this->createData = true;
            $this->saveLabel = $this->_($this->_('Add new track'));
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();

        // feature request #200
        if (isset($this->formData['gtr_organizations']) && (! is_array($this->formData['gtr_organizations']))) {
            $this->formData['gtr_organizations'] = explode('|', trim($this->formData['gtr_organizations'], '|'));
        }
        return $this->formData;
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData(): int
    {
        // feature request #200
        if (isset($this->formData['gtr_organizations']) && is_array($this->formData['gtr_organizations'])) {
            $this->formData['gtr_organizations'] = '|' . implode('|', $this->formData['gtr_organizations']) . '|';
        }
        if ($this->trackEngine) {
            $this->formData['gtr_survey_rounds'] = $this->trackEngine->calculateRoundCount();
        } else {
            $this->formData['gtr_survey_rounds'] = 0;
        }

        $output = parent::saveData();

        // Check for creation
        if ($this->createData) {
            if (isset($this->formData['gtr_id_track'])) {
                $this->trackId = $this->formData['gtr_id_track'];
            }
        } elseif ($this->trackEngine &&
                isset($this->formData[$this->_oldClassName], $this->formData['gtr_track_class']) &&
                $this->formData[$this->_oldClassName] != $this->formData['gtr_track_class']) {

            // Track conversion
            $this->trackEngine->convertTo($this->formData['gtr_track_class']);
        }
        
        return $output;
    }
}
