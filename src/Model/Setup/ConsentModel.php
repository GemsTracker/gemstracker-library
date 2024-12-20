<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Setup;

use Gems\Handlers\Setup\ConsentHandler;
use Gems\Model\MetaModelLoader;
use Gems\Repository\ConsentRepository;
use Laminas\Validator\Regex;
use Zalt\Base\TranslatorInterface;
use Zalt\Filter\UcFirstFilter;
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
        protected readonly  MetaModelLoader $metaModelLoader,
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
        $this->metaModelLoader->addDatabaseTranslations($this->metaModel, $action->isDetailed());
    }

    public function applySettings()
    {
        $regex = new Regex('/^' . ConsentHandler::$parameters['gco_description'] . '$/');
        $regex->setMessage($this->_('Only letters, numbers, underscores (_) and dashes (-) are allowed.'), Regex::NOT_MATCH);

        $this->metaModel->set('gco_description', [
            'label'            => $this->_('Description'),
            'minlength'        => 2,
            'size'             => '10',
            'translate'        => true,
            'filters[ucfirst]' => UcFirstFilter::class,  // Otherwise the label may overwrite the export/edit/delete/create routes
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