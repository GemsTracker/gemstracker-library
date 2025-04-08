<?php

namespace Gems\Auth\Acl;

use Gems\Event\Application\SupplementaryPrivilegesBuildItemsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class SupplementaryPrivileges
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function getItems(): array
    {
        $items = $this->buildItems();

        $event  = new SupplementaryPrivilegesBuildItemsEvent($items);
        $this->eventDispatcher->dispatch($event);

        return $event->getItems();
    }

    private function buildItems(): array
    {
        return [
            'pr.organization-switch' => $this->translator->trans('Grant access to all organization.'),
            'pr.plan.mail-as-application' => $this->translator->trans('Grant right to impersonate the site when mailing.'),
            'pr.respondent.multiorg' => $this->translator->trans('Display multiple organizations in respondent overview.'),
            'pr.episodes.rawdata' => $this->translator->trans('Display raw data in Episodes of Care.'),
            'pr.respondent.result' => $this->translator->trans('Display results in token overviews.'),
            'pr.respondent.select-on-track' => $this->translator->trans('Grant checkboxes to select respondents on track status in respondent overview.'),
            'pr.respondent.show-deleted' => $this->translator->trans('Grant checkbox to view deleted respondents in respondent overview.'),
            'pr.respondent.who' => $this->translator->trans('Display staff member name in token overviews.'),
            'pr.staff.edit.all' => $this->translator->trans('Grant right to edit staff members from all organizations.'),
            'pr.export.add-resp-nr' => $this->translator->trans('Grant right to export respondent numbers with survey answers.'),
            'pr.export.gender-age' => $this->translator->trans('Grant right to export gender and age information with survey answers.'),
            'pr.staff.see.all' => $this->translator->trans('Display all organizations in staff overview.'),
            'pr.group.switch' => $this->translator->trans('Grant right to switch groups.'),
            'pr.project.questions' => $this->translator->trans('Show questions with token.'),
            'pr.token.mail.freetext' => $this->translator->trans('Grant right to send free text (i.e. non-template) email messages.'),
            'pr.systemuser.seepwd' => $this->translator->trans('Grant right to see password of system users (without editing right).'),
            'pr.embed.login' => $this->translator->trans('Grant right for access to embedded login page.'),
            'pr.survey-maintenance.answer-groups' => $this->translator->trans('Grant right to set answer access to surveys.')
        ];
    }
}
