<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Menu\MenuSnippetHelper;
use Gems\Model\Translator\StraightTranslator;
use Gems\Repository\ImportRepository;
use Mezzio\Session\SessionInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlElement;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Translator\ModelTranslatorInterface;
use Zalt\Snippets\Zend\ZendFormSnippetTrait;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 3:52:53 PM
 */
class ModelImportSnippet extends \Zalt\Snippets\ModelImportSnippetAbstract
{
    use ZendFormSnippetTrait;

    /**
     *
     * @var \Gems\Audit\AuditLog
     */
    protected $accesslog;

    protected string $afterSaveRoutePart = 'index';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MetaModelLoader $metaModelLoader,
        SessionInterface $session,
        protected readonly ImportRepository $importRepository,
        protected readonly MenuSnippetHelper $menuHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $metaModelLoader, $session);

        $this->failureDirectory = $this->importRepository->getImportFailureDir();
        $this->successDirectory = $this->importRepository->getImportSuccessDir();
        $this->tempDirectory    = $this->importRepository->getImportTempDir();

        if (isset($this->translatorNames[StraightTranslator::class])) {
            $this->translatorNames[StraightTranslator::class] = $this->_('Straight');
        }
    }

    /**
     * @inheritdoc
     */
    public function afterImport(HtmlElement $element)
    {
        $text = parent::afterImport($element);

        $data = $this->formData;

        // Remove unuseful data
        unset($data['button_spacer'], $data['current_step'], $data[$this->csrfName]);

        // Add useful data
        $data['localfile']        = basename($this->session->get('localfile'));
        $data['extension']        = $this->session->get('extension');

//        $data['failureDirectory'] = '...' . substr($this->importer->getFailureDirectory(), -30);
//        $data['longtermFilename'] = basename($this->importer->getLongtermFilename());
//        $data['successDirectory'] = '...' . substr($this->importer->getSuccessDirectory(), -30);
        $data['tempDirectory']    = '...' . substr($this->tempDirectory, -30);

//        $data['importTranslator'] = get_class($this->importer->getCurrentImportTranslator());
        $data['sourceModelClass'] = get_class($this->sourceModel);
        $data['targetModelClass'] = get_class($this->targetModel);

        ksort($data);

        // $this->accesslog->logChange($this->request, null, array_filter($data));
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $form = new \Gems\Form($options);

        return $form;
    }

    protected function init(): void
    {
        parent::init();

        $this->stepsHeader = $this->_('Data import. Step %d of %d.');
    }

    /**
     * If menu item does not exist or is not allowed, redirect to index
     */
    protected function setAfterSaveRoute()
    {
        if (! $this->afterSaveRouteUrl) {
            $route  = $this->menuHelper->getRelatedRoute($this->afterSaveRoutePart);
            $keys   = $this->getModel()->getMetaModel()->getKeys();
            $params = $this->requestInfo->getRequestMatchedParams();
            foreach ($keys as $key => $field) {
                if (isset($this->formData[$field]) && (strlen((string) $this->formData[$field]) > 0)) {
                    $params[$key] = $this->formData[$field];
                } elseif (! isset($params[$key])) {
                    $params[$key] = null;
                }
            }

            $this->afterSaveRouteUrl = $this->menuHelper->routeHelper->getRouteUrl($route, $params);
        }
        parent::setAfterSaveRoute();
    }
}
