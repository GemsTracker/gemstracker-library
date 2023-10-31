<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Setup;

use Gems\Model\MetaModelLoader;
use Gems\Repository\ConsentRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\SnippetsActions\ApplyActionInterface;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @since      Class available since version 1.0
 */
class ConsentModel extends \Gems\Model\SqlTableModel implements ApplyActionInterface
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly ConsentRepository $consentRepository,
    )
    {
        parent::__construct('gems__consents', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gco');
        $this->metaModel->setKeys(['gco_description' => 'gco_description']);

        $this->applySettings();
    }

    public function applyAction(SnippetActionInterface $action): void
    {
//        if ($action->isEditing()) {
//            $this->modelLoader->addDatabaseTranslationEditFields($this->metaModel);
//        } else {
//            $this->modelLoader->addDatabaseTranslations($this->metaModel);
//        }
    }

    public function applySettings()
    {
        $this->metaModel->set('gco_description', [
            'label'            => $this->_('Description'),
            'size'             => '10',
            'translate'        => true,
            'validators[uniq]' => ModelUniqueValidator::class
        ]);

        $this->metaModel->set('gco_order', [
            'label'            => $this->_('Order'),
            'description'      => $this->_('Determines order of presentation in interface.'),
            'size'             => '10',
            'validators[dig]'  => 'Digits',
            'validators[uniq]' => ModelUniqueValidator::class
        ]);

        $this->metaModel->set('gco_code', [
            'label'        => $this->_('Consent code'),
            'description'  => $this->_('Internal code, not visible to users, copied with the token information to the source.'),
            'multiOptions' => $this->consentRepository->getConsentTypes(),
        ]);
    }
}