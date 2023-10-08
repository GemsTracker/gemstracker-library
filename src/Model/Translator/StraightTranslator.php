<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Translator;

use Gems\Db\ResultFetcher;
use Gems\Repository\OrganizationRepository;
use Zalt\Base\TranslatorInterface;

/**
 * Make sure a \Gems\Form is used for validation
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class StraightTranslator extends \Zalt\Model\Translator\StraightTranslator
{
    /**
     * The name of the field to store the organization id in
     *
     * @var string
     */
    protected string $orgIdField = 'gr2o_id_organization';

    /**
     * Extra values the origanization id field accepts
     * @var array Value indentifying org => org id
     */
    protected array $organizationTranslations;

    public function __construct(
        TranslatorInterface $translator,
        protected OrganizationRepository $organizationRepository,
        protected ResultFetcher $resultFetcher,
    )
    {
        parent::__construct($translator);

        $this->organizationTranslations = $this->organizationRepository->getOrganizationsImportTranslations();
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }

        // Get the real organization from the provider_id or code if it exists
        if (isset($row[$this->orgIdField], $this->organizationTranslations[$row[$this->orgIdField]])) {
            $row[$this->orgIdField] = $this->organizationTranslations[$row[$this->orgIdField]];
        }

        return $row;
    }
}
