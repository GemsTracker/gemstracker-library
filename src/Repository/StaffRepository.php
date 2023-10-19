<?php

namespace Gems\Repository;

use Gems\Api\Model\Transformer\ValidateFieldsTransformer;
use Gems\Db\CachedResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\StaffModel;
use Gems\User\UserLoader;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Loader\ProjectOverloader;

class StaffRepository
{
    protected array $cacheTags = ['staff'];

    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected TranslatorInterface $translator,
        protected Model $modelLoader,
        protected ProjectOverloader $overloader,
        protected CurrentUserRepository $currentUserRepository,
    )
    {}

    public function getStaffModel(): StaffModel
    {
        return $this->modelLoader->getStaffModel();
    }

    public function createStaff(
        string $username,
        int $organizationId,
        int $groupId,
        string $lastName,
        string $firstName = null,
        string $surnamePrefix = null,
        string $email = null,
        string $phoneNumber = null,
        string $jobTitle = null,
        string $gender = 'U',
    )
    {
        $newUserValues = [
            'gsf_login' => $username,
            'gsf_id_organization' => $organizationId,
            'gsf_last_name' => $lastName,
            'gsf_first_name' => $firstName,
            'gsf_surname_prefix' => $surnamePrefix,
            'gul_can_login' => 1,
            'gsf_id_primary_group' => $groupId,
            'gsf_email' => $email,
            'gsf_gender' => $gender,
            'gsf_phone_1' => $phoneNumber,
            'gsf_job_title' => $jobTitle,
        ];

        $staffModel = $this->getStaffModel();

        $staffModel->addTransformer(new ValidateFieldsTransformer($staffModel, $this->overloader, $this->currentUserRepository->getCurrentUserId()));
        $result = $staffModel->save($newUserValues);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * Return key/value pairs of all active staff members
     *
     * @return array
     */
    public function getActiveStaff()
    {
        $sql = "SELECT gsf_id_user,
                    CONCAT(
                        COALESCE(gsf_last_name, '-'),
                        ', ',
                        COALESCE(gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                        ) AS name
                FROM gems__staff
                WHERE gsf_active = 1
                ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix";

        return $this->cachedResultFetcher->fetchPairs(__FUNCTION__, $sql, null, $this->cacheTags);
    }

    /**
     * Return key/value pairs of all staff members, currently active or not
     *
     * @return array
     */
    public function getStaff()
    {
        $sql = "SELECT gsf_id_user,
                        CONCAT(
                            COALESCE(gsf_last_name, '-'),
                            ', ',
                            COALESCE(gsf_first_name, ''),
                            COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                            )
                    FROM gems__staff
                    ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix";

        $staticStaff = $this->getStaticStaff();

        return $this->cachedResultFetcher->fetchPairs(__FUNCTION__, $sql, null, $this->cacheTags) + $staticStaff;

    }

    public function getStaticStaff()
    {
        return [
            UserLoader::SYSTEM_USER_ID => Html::raw($this->translator->_('&laquo;system&raquo;')),
        ];
    }
}