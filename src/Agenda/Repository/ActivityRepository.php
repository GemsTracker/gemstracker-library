<?php

namespace Gems\Agenda\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;

class ActivityRepository
{
    public array $activitiesCacheTags = ['activity', 'activities'];

    protected int $currentUserId;

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        readonly CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUserId = $this->currentUserRepository->getCurrentUserId();
    }

    public function changeActivityOrganization(int $oldActivityId, int $newOrganizationId): int|null
    {
        $activityName = $this->getActivityName($oldActivityId);
        return $this->matchActivity($activityName, $newOrganizationId, true);
    }

    /**
     * Create a new activity
     *
     * @param string $name Activity name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createActivity(string $name, int $organizationId): int|null
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $table = new TableGateway('gems__agenda_activities', $this->cachedResultFetcher->getAdapter());
        $result = $table->insert([
            'gaa_name' => $name,
            'gaa_id_organization' => $organizationId,
            'gaa_match_to' => $name,
            'gaa_changed' => new Expression('NOW()'),
            'gaa_changed_by' => $this->currentUserId,
            'gaa_created' => new Expression('NOW()'),
            'gaa_created_by' => $this->currentUserId,
        ]);

        $this->cachedResultFetcher->getCache()->invalidateTags($this->activitiesCacheTags);

        if ($result) {
            return $table->getLastInsertValue();
        }
        return null;
    }

    public function getActivityData(int $activityId): array|null
    {
        $allActivities = $this->getAllActivitiesData();
        foreach($allActivities as $activity) {
            if ($activity['gaa_id_activity'] === $activityId) {
                return $activity;
            }
        }

        return null;
    }

    public function getActivityName(int $activityId): string|null
    {
        $activity = $this->getActivityData($activityId);
        if ($activity) {
            return $activity['gaa_name'];
        }
        return null;
    }

    public function getActiveActivitiesData(int|null $organizationId = null): array
    {
        $allActivities = $this->getAllActivitiesData();

        return array_filter($allActivities, function($activity) use ($organizationId) {
            if ($organizationId) {
                return ($activity['gaa_active']) && $activity['gaa_id_organization'] == $organizationId;
            }
            return (bool)$activity['gaa_active'];
        });
    }

    public function getAllActivitiesData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__agenda_activities');
        $select->columns([
            'gaa_id_activity',
            'gaa_name',
            'gaa_id_organization',
            'gaa_name_for_resp',
            'gaa_match_to',
            'gaa_code',
            'gaa_active',
            'gaa_filter',
        ]);
        return $this->cachedResultFetcher->fetchAll('agendaActivities', $select, null, $this->activitiesCacheTags) ?? [];
    }

    public function getActivityOptions(int|null $organizationId = null): array
    {
        $activeActivities = $this->getActiveActivitiesData($organizationId);

        $options = array_column($activeActivities, 'gaa_name', 'gaa_id_activity');
        asort($options);
        return $options;
    }

    protected function getMatchList(): array
    {
        $activeActivities = $this->getActiveActivitiesData();

        $sortedActivities = [];
        foreach ($activeActivities as $activity) {
            if ($activity['gaa_match_to'] !== null) {
                foreach (explode('|', $activity['gaa_match_to']) as $match) {
                    $sortedActivities[$match][$activity['gaa_id_organization']] = $activity['gaa_id_activity'];
                }
            }
        }

        return $sortedActivities;
    }

    /**
     * Match an activity to one in the database
     *
     * @param $name string Activity name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null activity ID that was matched or null
     */
    public function matchActivity(string $name, int $organizationId, bool $create = true): int|null
    {
        $activities = $this->getMatchList();

        if (isset($activities[$name], $activities[$name][$organizationId])) {
            return (int)$activities[$name][$organizationId];
        }

        if ($create) {
            return $this->createActivity($name, $organizationId);
        }

        return null;
    }
}