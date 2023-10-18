<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Communication\CommunicationRepository;
use Gems\Mail\TokenMailer;
use Gems\Menu\RouteHelper;
use Gems\Tracker\Token;
use MUtil\Model;
use MUtil\Translate\Translator;
use Symfony\Component\Mime\Address;
use Zalt\Base\RequestInfo;
use Zalt\Html\HrefArrayAttribute;
use Zalt\Html\Html;
use Zalt\Html\HtmlElement;
use Zalt\Html\HtmlInterface;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Basic class for creating forward loop snippets
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ShowTokenLoopAbstract extends SnippetAbstract
{
    const CONTINUE_LATER_PARAM = 'continueLater';

    protected string $action = 'forward';

    /**
     * General date format
     */
    protected string $dateFormat = 'j M Y';

    /**
     * Switch for showing the duration.
     *
     * @var boolean
     */
    protected bool $showDuration = true;

    /**
     * @var bool Switch for showing the last name
     */
    protected bool $showLastName = false;

    /**
     * Switch for showing how long the token is valid.
     *
     * @var boolean
     */
    protected bool $showUntil = true;

    /**
     * Required, the current token, possibly already answered
     */
    protected Token $token;

    /**
     * Was this token already answered? Calculated from $token
     *
     * @var boolean
     */
    protected bool $wasAnswered;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected RouteHelper $routeHelper,
        protected CommunicationRepository $communicationRepository,
        protected Translator $translator,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
        $this->wasAnswered = $this->token->isCompleted();
    }

    public function addContinueLink(HtmlInterface $html, ?Token $token = null)
    {
        if (null == $token) {
            $token = $this->token;
        }
        if (! $token->isCompleted()) {
            $orgEmail   = $token->getOrganization()->getFrom();
            $templateId = $this->communicationRepository->getTemplateIdFromCode('continue');

            // If there is no template, or no email for sender / receiver we show no link
            if ($templateId && (!empty($orgEmail)) && $token->isMailable()) {
                $html->pInfo($this->translator->_('or'));
                $url = $this->routeHelper->getRouteUrl("ask.$this->action", [
                    'id' => $token->getTokenId()
                ],
                [
                    self::CONTINUE_LATER_PARAM => 1
                ]);
                $html->actionLink($url, $this->translator->_('Send me an email to continue later'));
            }
        }
    }

    /**
     * @return boolean Was the continue link clicked
     */
    public function checkContinueLinkClicked()
    {
        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[self::CONTINUE_LATER_PARAM]) && $queryParams[self::CONTINUE_LATER_PARAM] == 1) {
            return true;
        }
        return false;
    }

    /**
     * Handle the situation when the continue later link was clicked
     *
     * @return HtmlInterface
     */
    public function continueClicked()
    {
        if ($this->token->isCompleted()) {
            $token = $this->token->getNextUnansweredToken();
        } else {
            $token = $this->token;
        }

        $html = $this->getHtmlSequence();
        $org  = $token->getOrganization();

        $html->h3($this->getHeaderLabel());

        $email = $this->communicationRepository->getNewEmail();
        $email->addTo(new Address($token->getEmail(), $token->getRespondentName()));
        $email->addFrom(new Address($token->getOrganization()->getEmail()));

        $template = $this->communicationRepository->getTemplate($token->getOrganization());
        $language = $this->communicationRepository->getCommunicationLanguage($token->getRespondentLanguage());
        $templateId = $this->communicationRepository->getTemplateIdFromCode('continue');
        
        if ($templateId) {

            $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
            $mailFields = $this->communicationRepository->getTokenMailFields($token, $language);
            $mailer = $this->communicationRepository->getMailer();
            $email->from($token->getOrganization()->getFrom());
            $email->subject($mailTexts['subject'], $mailFields);
            $email->htmlTemplate($template, $mailTexts['body'], $mailFields);

            $mailSentDate = $token->getMailSentDate();
            if ($mailSentDate) {
                $lastMailedDate = new \DateTimeImmutable($mailSentDate);
            }

            // Do not send multiple mails a day

            if ($lastMailedDate instanceof DateTimeInterface && CarbonImmutable::create($lastMailedDate)->isToday()) {
                $html->pInfo($this->translator->_('An email with information to continue later was already sent to your registered email address today.'));
            } else {
                $mailer->send($email);
                $html->pInfo($this->translator->_('An email with information to continue later was sent to your registered email address.'));
            }

            $html->pInfo($this->translator->_('Delivery can take a while. If you do not receive an email please check your spam-box.'));
        }

        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw($sig);
        }

        return $html;
    }

    /**
     * Formats an completion date for this display
     *
     * @param DateTimeInterface $dateTime
     * @return string
     */
    public function formatCompletion(DateTimeInterface $dateTime)
    {
        $days = abs($dateTime->diff(new DateTimeImmutable())->days);

        switch ($days) {
            case 0:
                return $this->translator->_('We have received your answers today. Thank you!');

            case 1:
                return $this->translator->_('We have received your answers yesterday. Thank you!');

            case 2:
                return $this->translator->_('We have received your answers 2 days ago. Thank you.');

            default:
                if ($days <= 14) {
                    return sprintf($this->translator->_('We have received your answers %d days ago. Thank you.'), $days);
                }
                return sprintf($this->translator->_('We have received your answers on %s. '), $dateTime->format($this->dateFormat));
        }
    }

    /**
     * Returns the duration if it should be displayed.
     *
     * @param string $duration
     * @return string|null
     */
    public function formatDuration($duration)
    {
        if ($duration && $this->showDuration) {
            return sprintf($this->translator->_('Takes about %s to answer.'),  $duration) . ' ';
        }
        return null;
    }

    /**
     * Return a no further questions statement (or nothing)
     *
     * @return HtmlElement
     */
    public function formatNoFurtherQuestions()
    {
        return Html::create('pInfo', $this->translator->_('At the moment we have no further surveys for you to take.'));
    }

    /**
     * Return a thanks greeting depending on showlastName switch
     *
     * @return HtmlElement
     */
    public function formatThanks()
    {
        $output = Html::create('pInfo');
        if ($this->showLastName) {
            // getRespondentName returns the relation name when the token has a relation
            $output->sprintf($this->translator->_('Thank you %s,'), $this->token->getRespondentName());
        } else {
            $output->append($this->translator->_('Thank you for your answers,'));
        }
        return $output;
    }

    /**
     * Formats an until date for this display
     *
     * @param DateTimeInterface $dateTime
     * @return mixed
     */
    public function formatUntil(DateTimeInterface $dateTime = null)
    {
        if (false === $this->showUntil) { return; }

        if (null === $dateTime) {
            return $this->translator->_('Survey has no time limit.');
        }

        $days = $dateTime->diff(new DateTimeImmutable())->days;

        switch ($days) {
            case 0:
                return [
                    Html::create('strong', $this->translator->_('Warning!!!')),
                    ' ',
                    $this->translator->_('This survey must be answered today!')
                ];

            case 1:
                return [
                    Html::create('strong', $this->translator->_('Warning!!')),
                    ' ',
                    $this->translator->_('This survey can only be answered until tomorrow!')
                ];

            case 2:
                return $this->translator->_('Warning! This survey can only be answered for another 2 days!');

            default:
                if ($days <= 14) {
                    return sprintf($this->translator->_('Please answer this survey within %d days.'), $days);
                }

                if ($days <= 0) {
                    return $this->translator->_('This survey can no longer be answered.');
                }

                return sprintf($this->translator->_('Please answer this survey before %s.'), $dateTime->format($this->dateFormat));
        }
    }

    /**
     * Return a welcome greeting depending on showlastName switch
     *
     * @return mixed
     */
    public function formatWelcome()
    {
        $output = Html::create('pInfo');
        if ($this->showLastName) {
            // getRespondentName returns the relation name when the token has a relation
            $output->sprintf($this->translator->_('Welcome %s,'), $this->token->getRespondentName());
        } else {
            $output->append($this->translator->_('Welcome,'));
        }
        if ($this->token->hasRelation() && $this->showLastName) {
            return [
                $output,
                Html::create('pInfo', sprintf(
                    $this->translator->_('We kindly ask you to answer a survey about %s.'),
                    $this->token->getRespondent()->getName()
                ))];
        }
        return $output;
    }

    /**
     * @return string Return the header for the screen
     */
    protected function getHeaderLabel()
    {
        return $this->translator->_('Token');
    }

    /**
     * Get the href for a token
     *
     * @param Token $token
     * @return HrefArrayAttribute
     */
    protected function getTokenHref(Token $token)
    {
        /***************
         * Get the url *
         ***************/
        $params = [
            'action' => 'to-survey',
            Model::REQUEST_ID => $token->getTokenId(),
            'RouteReset' => false,
        ];

        return new HrefArrayAttribute($params);
    }

    protected function getTokenUrl(Token $token): string
    {
        return $this->routeHelper->getRouteUrl('ask.to-survey', [
            Model::REQUEST_ID => $token->getTokenId(),
        ]);
    }

    /**
     * The last token was answered, there are no more tokens to answer
     *
     * @return HtmlInterface
     */
    public function lastCompleted()
    {
        $html = $this->getHtmlSequence();
        $org  = $this->token->getOrganization();

        $html->h3($this->getHeaderLabel());

        $html->append($this->formatThanks());

        $html->append($this->formatNoFurtherQuestions());

        if ($sig = $org->getSignature()) {
            $html->pInfo()->raw($sig);
        }

        return $html;
    }
}
