<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Ask;

use Gems\Audit\AuditLog;
use Gems\Form;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Project\ProjectSettings;
use Gems\Snippets\FormSnippetAbstract;
use Gems\Tracker;
use Gems\User\User;
use MUtil\Bootstrap\Form\Element\Text;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @since      Class available since version 1.0
 */
class AskTokenInputSnippet extends FormSnippetAbstract
{
    protected string $clientIp;

    protected User|null $currentUser = null;

    /**
     * The field name for the token element.
     *
     * @var string
     */
    protected string $tokenFieldName = MetaModelInterface::REQUEST_ID;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        CurrentUserRepository $currentUserRepository,
        protected readonly ProjectSettings $project,
        protected readonly Tracker $tracker,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->saveLabel = $this->_('OK');
    }

    /**
     * @inheritDoc
     */
    protected function addFormElements(mixed $form)
    {
        /**
         * Form $form
         */
        $form->setAttrib('class', 'ask-token-form');
        $this->addTokenElement($form);
    }

    public function addTokenElement(Form $form)
    {
        $element = $form->getElement($this->tokenFieldName);

        if (! $element) {
            $tokenLib   = $this->tracker->getTokenLibrary();
            $max_length = $tokenLib->getLength();

            // Veld token
            $element = $form->createElement('Text', $this->tokenFieldName);
            $element->setLabel($this->_('Token'))
                ->setDescription(sprintf($this->_('Enter tokens as %s.'), $tokenLib->getFormat()))
                ->setAttrib('size', $max_length + 2)
                ->setAttrib('maxlength', $max_length)
                ->setRequired(true)
                // @phpstan-ignore-next-line
                ->addFilter($this->tracker->getTokenFilter())
                // @phpstan-ignore-next-line
                ->addValidator($this->tracker->getTokenValidator($this->clientIp));

            $form->addElement($element);
        }

        return $element;
    }

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();
        $html[] = parent::getHtmlOutput();
        $html[] = $this->getTokenInfo();

        return $html;
    }

    protected function getTitle()
    {
        return sprintf($this->_('Enter your %s token'), $this->project->getName());
    }

    protected function getTokenInfo(): HtmlInterface
    {
        $pClass['class'] = 'info';
        $div = Html::div();
        $div->p(
            $this->_('Tokens identify a survey that was assigned to you personally.'), ' ',
            $this->_('Entering the token and pressing OK will open that survey.'), ' ', $pClass
        );

        if ($this->currentUser !== null && $this->currentUser->isActive()) {
            if ($this->currentUser->isLogoutOnSurvey()) {
                $div->p($this->_('After answering the survey you will be logged off automatically.'), ' ', $pClass);
            }
        }

        $div->p(
            $this->_('A token consists of two groups of four letters and numbers, separated by an optional hyphen. Tokens are case insensitive.'), ' ',
            $this->_('The number zero and the letter O are treated as the same; the same goes for the number one and the letter L.') , $pClass
        );

        return $div;
    }

    protected function setAfterSaveRoute()
    {
        $route = $this->menuHelper->getRelatedRoute('forward');
        $this->redirectRoute = $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl($route, $this->formData);
    }
}