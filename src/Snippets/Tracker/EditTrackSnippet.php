<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Tracker\Snippets\EditTrackSnippetAbstract;
use Mezzio\Session\SessionInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditTrackSnippet extends EditTrackSnippetAbstract
{
    protected string $afterSaveRoutePart = 'respondent.tracks.show';

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList(): array
    {
        $links = [];

        $routes = [
            'respondent.show',
            'respondent.tracks.index',
            'respondent.tracks.view',
        ];

        foreach($routes as $routeName) {

            $routeUrl = $this->menuHelper->getRouteUrl($routeName, $this->requestInfo->getRequestMatchedParams());
            if ($routeUrl) {
                $links[$this->_('Show respondent')] = $routeUrl;
            }
        }

        return $links;
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
        if ($this->trackEngine) {
            // concatenate user input (gtf_field fields)
            // before the data is saved (the fields them
            $this->formData['gr2t_track_info'] = $this->trackEngine->calculateFieldsInfo($this->formData);
        }

        // Perform the save
        $model          = $this->getModel();
        $this->formData = $model->save($this->formData);
        $changed        = $model->getChanged();
        $refresh        = false;

        // Retrieve the key if just created
        if ($this->createData) {
            $this->respondentTrackId = $this->formData['gr2t_id_respondent_track'];
            $this->respondentTrack   = $this->tracker->getRespondentTrack($this->formData);

            // Explicitly save the fields as the transformer in the model only handles
            // before save event (like default values) for existing respondenttracks
            $this->respondentTrack->setFieldData($this->formData);

            // Create the actual tokens!!!!
            $this->trackEngine->checkRoundsFor($this->respondentTrack, $this->session, $this->currentUserId);
            $refresh = true;

        } else {
            // Check if the input has changed, i.e. one of the dates may have changed
            $refresh = (boolean) $changed;
        }

        if ($refresh) {
            // Perform a refresh from the database, to avoid date trouble
            $this->respondentTrack->refresh();
            $this->respondentTrack->checkTrackTokens($this->currentUserId);
        }


        // Communicate with the user
        return $changed;
    }
}
