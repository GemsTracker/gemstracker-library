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

use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\Model\TokenModel;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Adds basic token editing snippet parameter processing and checking.
 *
 * This class supplies the model and adds some display knowledge.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class EditTokenSnippetAbstract extends ModelFormSnippetAbstract
{
    /**
     * Optional: $request or $tokenData must be set
     *
     * The token shown
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

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected MetaModelLoader $metaModelLoader,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
    }

    /**
     * Creates the model
     *
     * @return FullDataInterface
     */
    protected function createModel(): FullDataInterface
    {
        if (TokenModel::$useTokenModel) {
            $model = $this->metaModelLoader->createModel(TokenModel::class);
            $model->applyDetailedFormatting();

        } else {
            $model = $this->token->getModel();
        }

        if ($model instanceof StandardTokenModel) {
            if ($this->createData) {
                $model->applyInsertionFormatting();
            }
        }

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return HtmlInterface|null Something that can be rendered
     */
    public function getHtmlOutput()
    {

        if ($this->tokenId) {
            if ($this->token->exists) {
                return parent::getHtmlOutput();
            } else {
                $this->addMessage(sprintf($this->_('Token %s not found.'), $this->tokenId));
            }

        } else {
            $this->addMessage($this->_('No token specified.'));
        }
        return null;
    }


    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }

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
        return parent::hasHtmlOutput();
    }
}
