<?php

namespace Gems\Agenda\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;

class LocationRepository
{
    public const ORGANIZATION_SEPARATOR = '|';
    public array $locationsCacheTags = ['location', 'locations'];



    protected int $currentUserId;

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        readonly CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUserId = $this->currentUserRepository->getCurrentUserId();
    }

    public function changeLocationOrganization(int $oldLocationId, int $newOrganizationId): int|null
    {
        $locationName = $this->getLocationName($oldLocationId);
        return $this->matchLocation($locationName, $newOrganizationId, true);
    }

    /**
     * Create a new location
     *
     * @param string $name Location name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createLocation(string $name, int $organizationId): int|null
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $table = new TableGateway('gems__locations', $this->cachedResultFetcher->getAdapter());
        $result = $table->insert([
            'glo_name' => $name,
            'glo_organizations' => static::ORGANIZATION_SEPARATOR . $organizationId . static::ORGANIZATION_SEPARATOR,
            'glo_match_to' => $name,
            'glo_changed' => new Expression('NOW()'),
            'glo_changed_by' => $this->currentUserId,
            'glo_created' => new Expression('NOW()'),
            'glo_created_by' => $this->currentUserId,
        ]);

        $this->cachedResultFetcher->getCache()->invalidateTags($this->locationsCacheTags);

        if ($result) {
            return $table->getLastInsertValue();
        }
        return null;
    }

    public function getLocationData(int $locationId): array|null
    {
        $allLocations = $this->getAllLocationData();
        foreach($allLocations as $location) {
            if ($location['glo_id_location'] === $locationId) {
                return $location;
            }
        }

        return null;
    }

    public function getLocationName(int $locationId): string|null
    {
        $location = $this->getLocationData($locationId);
        if ($location) {
            return $location['glo_name'];
        }
        return null;
    }

    public function getActiveLocationsData(int|null $organizationId = null): array
    {
        $allLocations = $this->getAllLocationData();

        return array_filter($allLocations, function($location) use ($organizationId) {
            if ($organizationId) {
                return ($location['glo_active']) && in_array($organizationId, $location['glo_organizations']);
            }
            return (bool)$location['glo_active'];
        });
    }

    public function getAllLocationData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__locations');
        $select->columns([
            'glo_id_location',
            'glo_name',
            'glo_organizations',
            'glo_match_to',
            'glo_code',
            'glo_url',
            'glo_url_route',
            'glo_address_1',
            'glo_address_2',
            'glo_zipcode',
            'glo_city',
            'glo_iso_country',
            'glo_phone_1',
            'glo_active',
            'glo_filter',
        ])->order('glo_name');
        $result = $this->cachedResultFetcher->fetchAll('locations', $select, null, $this->locationsCacheTags);
        if ($result === null) {
            return [];
        }

        foreach($result as $key=>$row) {
            $result[$key]['glo_organizations'] = array_filter(explode(static::ORGANIZATION_SEPARATOR, $row['glo_organizations']));
        }
        return $result;
    }

    public function getLocationOptions(int|null $organizationId = null): array
    {
        $activeLocations = $this->getActiveLocationsData($organizationId);

        $options = array_column($activeLocations, 'glo_name', 'glo_id_location');
        asort($options);
        return $options;
    }

    protected function getMatchList(): array
    {
        $activeLocations = $this->getActiveLocationsData();

        $sortedActivities = [];
        foreach ($activeLocations as $location) {
            if ($location['glo_match_to'] !== null) {
                foreach (explode('|', $location['glo_match_to']) as $match) {
                    foreach($location['glo_organizations'] as $organizationId) {
                        $sortedActivities[$match][$organizationId] = $location['glo_id_location'];
                    }
                }
            }
        }

        return $sortedActivities;
    }

    /**
     * Match a location to one in the database
     *
     * @param $name string Location name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null Location ID that was matched or null
     */
    public function matchLocation(string $name, int $organizationId, bool $create = true): int|null
    {
        $locations = $this->getMatchList();

        if (isset($locations[$name], $locations[$name][$organizationId])) {
            return (int)$locations[$name][$organizationId];
        }

        if ($create) {
            return $this->createLocation($name, $organizationId);
        }

        return null;
    }
}