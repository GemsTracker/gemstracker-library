<?php

namespace Gems\Snippets;

class ActiveToggleSnippet extends ModelConfirmDataChangeSnippet
{
    protected string $deactivateConfirmQuestion;
    protected string $deactivateDisplayTitle;
    protected string $deactivateFormTitle;
    protected array $deactivateSaveData;

    protected string $reactivateConfirmQuestion;
    protected string $reactivateDisplayTitle;
    protected string $reactivateFormTitle;

    protected array $reactivateSaveData;

    protected function checkToggleSettings(): void
    {
        $this->displayTitle = $this->reactivateDisplayTitle;
        $this->confirmQuestion = $this->reactivateConfirmQuestion;
        $this->formTitle = $this->reactivateFormTitle;
        if ($this->isActive()) {
            $this->displayTitle = $this->deactivateDisplayTitle;
            $this->confirmQuestion = $this->deactivateConfirmQuestion;
            $this->formTitle = $this->deactivateFormTitle;
        }
    }

    protected function setAfterDeleteRoute()
    {
        $this->afterSaveRouteUrl = $this->menuSnippetHelper->getRelatedRouteUrl($this->confirmAction);
    }

    public function hasHtmlOutput(): bool
    {
        $this->checkToggleSettings();
        return parent::hasHtmlOutput();
    }

    public function isActive(): bool
    {
        $model = $this->getModel();

        $currentState = $model->loadFirst();
        $active = false;

        foreach($this->reactivateSaveData as $field => $value) {
            if (isset($currentState[$field]) && $currentState[$field] == $value) {
                $active = true;
            } else {
                $active = false;
            }
        }

        $this->saveData = $this->reactivateSaveData;
        if ($active) {
            $this->saveData = $this->deactivateSaveData;
        }

        $keys = $model->getKeys();
        foreach($keys as $keyField) {
            if (isset($currentState[$keyField])) {
                $this->saveData[$keyField] = $currentState[$keyField];
            }
        }

        return $active;
    }
}