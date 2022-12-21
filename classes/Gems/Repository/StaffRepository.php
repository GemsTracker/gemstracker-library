<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\User\UserLoader;
use MUtil\Translate\Translator;
use Zalt\Html\Html;

class StaffRepository
{
    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected Translator $translator,
    )
    {}

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

        return $this->cachedResultFetcher->fetchPairs(__FUNCTION__, $sql, null, ['staff']);
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

        return $this->cachedResultFetcher->fetchPairs(__FUNCTION__, $sql, null, ['staff']) + $staticStaff;

    }

    public function getStaticStaff()
    {
        return [
            UserLoader::SYSTEM_USER_ID => Html::raw($this->translator->_('&laquo;system&raquo;')),
        ];
    }
}