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

use Mezzio\Session\SessionInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditTrackSnippet extends \Gems\Tracker\Snippets\EditTrackSnippetAbstract
{
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
        /*$links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('track', 'show-track', $this->_('Show track'));*/

        $links = [];

        $routes = [
            'respondent.show',
            'respondent.tracks.index',
            'respondent.tracks.show',
        ];
        $currentParams = $this->requestInfo->getRequestMatchedParams();

        foreach($routes as $routeName) {
            $route = $this->routeHelper->getRoute($routeName);
            $routeParams = $this->routeHelper->getRouteParamsFromKnownParams($route, $currentParams);
            $links[$this->_('Show respondent')] = $this->routeHelper->getRouteUrl($routeName, $routeParams);
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
    protected function saveData()
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
            $this->respondentTrack   = $this->loader->getTracker()->getRespondentTrack($this->formData);

            // Explicitly save the fields as the transformer in the model only handles
            // before save event (like default values) for existing respondenttracks
            $this->respondentTrack->setFieldData($this->formData);

            // Create the actual tokens!!!!
            $this->trackEngine->checkRoundsFor($this->respondentTrack, $this->session, $this->userId);
            $refresh = true;

        } else {
            // Check if the input has changed, i.e. one of the dates may have changed
            $refresh = (boolean) $changed;
        }

        if ($refresh) {
            // Perform a refresh from the database, to avoid date trouble
            $this->respondentTrack->refresh();
            $this->respondentTrack->checkTrackTokens($this->userId);
        }


        // Communicate with the user
        $this->afterSave($changed);
    }
}
