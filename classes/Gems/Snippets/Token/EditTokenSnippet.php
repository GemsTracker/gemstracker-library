<?php


namespace Gems\Snippets\Token;


class EditTokenSnippet extends \Gems\Snippets\ModelFormSnippetGeneric
{
    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil\Snippets\ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => 'track',
                $this->request->getActionKey() => $this->routeAction,
            );

            if ($this->afterSaveRouteKeys) {
                // Set the key identifiers for the route.
                //
                // Mind you the values may have changed, either because of an edit or
                // because a new item was created.
                foreach ($this->getModel()->getKeys() as $id => $key) {
                    if (isset($this->formData[$key])) {
                        $this->afterSaveRouteUrl[$id] = $this->formData[$key];
                    }
                }
            }
        }

        if (is_array($this->afterSaveRouteUrl)) {
            // Make sure controller is set
            if (!array_key_exists('controller', $this->afterSaveRouteUrl)) {
                $this->afterSaveRouteUrl['controller'] = $this->request->getControllerName();
            }

            // Search array for menu item
            $find['controller'] = $this->afterSaveRouteUrl['controller'];
            $find['action'] = $this->afterSaveRouteUrl['action'];

            // If not allowed, redirect to index
            if (null == $this->menu->find($find)) {
                $this->afterSaveRouteUrl['action'] = 'index';
                $this->resetRoute = true;
            }
        }

        return $this;
    }
}