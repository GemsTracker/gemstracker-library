<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Respondent;

use Zalt\Filter\Dutch\BurgerservicenummerFilter;
use Zalt\Filter\Dutch\PostcodeFilter;
use Zalt\SnippetsActions\Form\EditActionAbstract;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Dutch\BurgerServiceNummer;

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @since      Class available since version 1.0
 */
class RespondentNlModel extends RespondentModel
{
    public function applyAction(SnippetActionInterface $action): void
    {
        parent::applyAction($action);

        if ($action->isEditing() && $action instanceof EditActionAbstract && $action->createData) {
            if ($this->metaModel->has('grs_ssn')) {
                $bsn = new BurgerServiceNummer();

                $num = mt_rand(100000000, 999999999);

                while (! $bsn->isValid($num)) {
                    $num++;
                }

                $this->metaModel->set('grs_ssn', 'description', sprintf($this->_('Random Example BSN: %s'), $num));
            }
        }
    }

    public function applySettings()
    {
        parent::applySettings();

        $this->metaModel->setIfExists('grs_ssn', [
            'description' => $this->_('Empty this field to remove the BSN'),
            'filters[bsn]' => BurgerservicenummerFilter::class,
            'maxlength' => 12,
            'size' => 10,
            'validators[bsn]' => BurgerServiceNummer::class,
        ]);

        $prefixDescr = ['description' => $this->_('de, ibn, van der, \'t, etc...')];
        $this->metaModel->setIfExists('grs_surname_prefix', $prefixDescr);
        $this->metaModel->setIfExists('grs_partner_surname_prefix', $prefixDescr);

        $this->metaModel->setIfExists('grs_iso_lang', ['default' => 'nl']);
        $this->metaModel->setIfExists('gr2o_treatment', ['description' => $this->_('DBC\'s, etc...')]);

        $this->metaModel->setIfExists('grs_zipcode', [
            'size' => 7,
            'description' => $this->_('E.g.: 0000 AA'),
            'filters[postcode]' => PostcodeFilter::class,
        ]);
    }

}