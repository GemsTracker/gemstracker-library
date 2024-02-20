<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Html;
use Gems\Model;
use Gems\Model\Bridge\ThreeColumnTableBridge;
use Gems\Model\MetaModelLoader;
use Gems\Tracker;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\Model\TokenModel;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelDetailTableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Generic extension for displaying tokens
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class ShowTokenSnippetAbstract extends ModelDetailTableSnippetAbstract
{
    protected string $bridgeClass = ThreeColumnTableBridge::class;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer table table-sm compliance';

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var \Gems\Tracker\Token
     */
    protected $token;

    /**
     * Optional: id of the selected token to show
     *
     * Can be derived from $request or $token
     *
     * @var string
     */
    protected $tokenId;

    /**
     * Show the token in an mini form for cut & paste.
     *
     * But only when the token is not answered.
     *
     * @var boolean
     */
    protected $useFakeForm = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected MetaModelLoader $metaModelLoader,
        protected MaskRepository $maskRepository,
        protected Tracker $tracker,
        protected StatusMessengerInterface $messenger,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (TokenModel::$useTokenModel) {
            $model = $this->metaModelLoader->createModel(TokenModel::class);
            $model->applyDetailedFormatting();

        } else {
            $model = $this->token->getModel();
        }

        if ($model instanceof StandardTokenModel) {
            $model->applyFormatting();
        }
        $metaModel = $model->getMetaModel();
        if ($this->useFakeForm && $this->token->getReceptionCode()->isSuccess() && (! $this->token->isCompleted())) {
            $metaModel->set('gto_id_token', 'formatFunction', array(__CLASS__, 'makeFakeForm'));
        } else {
            $metaModel->set('gto_id_token', 'formatFunction', 'strtoupper');
        }

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        if ($this->tokenId) {
            if ($this->token->exists) {
                $htmlDiv   = Html::div();

                $htmlDiv->h3($this->getTitle());

                $table = parent::getHtmlOutput();
                $this->applyHtmlAttributes($table);
                $htmlDiv[] = $table;

                return $htmlDiv;

            } else {
                $this->messenger->addMessage(sprintf($this->_('Token %s not found.'), $this->tokenId));
            }

        } else {
            $this->messenger->addMessage($this->_('No token specified.'));
        }
        return null;
    }

    /**
     *
     * @return string The header title to display
     */
    abstract protected function getTitle();

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if (! $this->tokenId) {
            if ($this->token) {
                $this->tokenId = $this->token->getTokenId();
            } else {
                $this->tokenId = $this->requestInfo->getParam(Model::REQUEST_ID);
            }
        }

        if ($this->tokenId && (! $this->token)) {
            $this->token = $this->tracker->getToken($this->tokenId);
        }

        // Output always true, returns an error message as html when anything is wrong
        return true;
    }

    /**
     * Creates a fake form so that it is (slightly) easier to
     * copy and paste a token.
     *
     * @param string $value \Gems token value
     * @return \Gems\Form
     */
    public static function makeFakeForm($value)
    {
        $form = new \Gems\Form();
        $form->removeDecorator('HtmlTag');

        $element = new \Zend_Form_Element_Text('gto_id_token');
        $element->class = 'token_copy copy-to-clipboard-after';
        $element->setDecorators(array('ViewHelper', array('HtmlTag', 'Div')));

        $form->addElement($element);
        $form->isValid(array('gto_id_token' => Late::call('strtoupper', $value)));

        return $form;
    }
}
