<?php

declare(strict_types=1);

namespace Gems\Model;

use Gems\Agenda\Repository\LocationRepository;
use Gems\Util;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Filter\Dutch\PostcodeFilter;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Model\Type\ConcatenatedType;

class LocationModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
        protected readonly Util $util,
    ) {
        parent::__construct('gems__locations', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'glo');

        $this->applySettings();
    }

    private function applySettings(): void
    {
        $yesNo = $this->translatedUtil->getYesNo();

        $this->metaModel->set('glo_name', [
            'label' => $this->_('Location'),
            'required' => true
        ]);

        $this->metaModel->set('glo_organizations', [
            'label' => $this->_('Organizations'),
            'description' => $this->_('Checked organizations see this organizations respondents.'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $this->util->getDbLookup()->getOrganizations(),
            'noSort' => true
        ]);
        (new ConcatenatedType(LocationRepository::ORGANIZATION_SEPARATOR, ', '))
            ->apply($this->metaModel, 'glo_organizations');

        $this->metaModel->setIfExists('glo_match_to', [
            'label' => $this->_('Import matches'),
            'description' => $this->_("Split multiple import matches using '|'.")
        ]);

        $this->metaModel->setIfExists('glo_code', [
            'label' => $this->_('Location code'),
            'size' => 10,
            'description' => $this->_('Optional code name to link the location to program code.')
        ]);

        $this->metaModel->setIfExists('glo_url', [
            'label' => $this->_('Location url'),
            'description' => $this->_('Complete url for location: http://www.domain.etc'),
            'validator' => 'Uri'
        ]);
        $this->metaModel->setIfExists('glo_url_route', [
            'label' => $this->_('Location route url'),
            'description' => $this->_('Complete url for route to location: http://www.domain.etc'),
            'validator' => 'Uri'
        ]);

        $this->metaModel->setIfExists('glo_address_1', ['label' => $this->_('Street')]);
        $this->metaModel->setIfExists('glo_address_2', ['label' => ' ']);

        $this->metaModel->setIfExists('glo_zipcode', [
            'label' => $this->_('Zipcode'),
            'size' => 7,
            'description' => $this->_('E.g.: 0000 AA'),
            'filter' => PostcodeFilter::class
        ]);

        $this->metaModel->setIfExists('glo_city', ['label' => $this->_('City')]);
        $this->metaModel->setIfExists('glo_region', ['label' => $this->_('Region')]);
        $this->metaModel->setIfExists('glo_iso_country', [
            'label' => $this->_('Country'),
            'multiOptions' => $this->util->getLocalized()->getCountries()
        ]);

        $this->metaModel->setIfExists('glo_phone_1', ['label' => $this->_('Phone')]);
        $this->metaModel->setIfExists('glo_phone_2', ['label' => $this->_('Phone 2')]);
        $this->metaModel->setIfExists('glo_phone_3', ['label' => $this->_('Phone 3')]);
        $this->metaModel->setIfExists('glo_phone_4', ['label' => $this->_('Phone 4')]);

        $this->metaModel->setIfExists('glo_active', [
            'label' => $this->_('Active'),
            'description' => $this->_('Inactive means assignable only through automatich processes.'),
            'type' => new ActivatingYesNoType($yesNo, 'row_class'),
        ]);
        $this->metaModel->setIfExists('glo_filter', [
            'label' => $this->_('Filter'),
            'description' => $this->_('When checked appointments with these locations are not imported.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo
        ]);
    }
}