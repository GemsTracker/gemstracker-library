<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

use Symfony\Component\Mime\Address;

/**
 * Basic class for creating forward loop snippets
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Snippets_ShowTokenLoopAbstract extends \MUtil_Snippets_SnippetAbstract
{
    const CONTINUE_LATER_PARAM = 'continueLater';

    /**
     * @var string 
     */
    protected $action = 'forward';

    /**
     * @var \Gems\Communication\CommunicationRepository
     */
    protected $communicationRepository;
    
    /**
     * General date format
     * @var string
     */
    protected $dateFormat = 'd MMMM yyyy';

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var \MUtil\Request\RequestInfo
     */
    protected $requestInfo;

    /**
     * Switch for showing the duration.
     *
     * @var boolean
     */
    protected $showDuration = true;

    /**
     * @var bool Switch for showing the last name
     */
    protected $showLastName = false;

    /**
     * Switch for showing how long the token is valid.
     *
     * @var boolean
     */
    protected $showUntil = true;

    /**
     * Required, the current token, possibly already answered
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     * Required
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Was this token already answered? Calculated from $token
     *
     * @var boolean
     */
    protected $wasAnswered;

    /**
     * @param \MUtil_Html_HtmlInterface $html
     * @param \Gems_Tracker_Token|null  $token
     */
    public function addContinueLink(\MUtil_Html_HtmlInterface $html, \Gems_Tracker_Token $token = null)
    {
        if (null == $token) {
            $token = $this->token;
        }
        if (! $token->isCompleted()) {
            $mailLoader = $this->loader->getMailLoader();
            /** @var \Gems_Mail_TokenMailer $mailer */
            $mailer     = $mailLoader->getMailer('token', $token->getTokenId());
            $orgEmail   = $token->getOrganization()->getFrom();
            $respEmail  = $token->getEmail();

            // If there is no template, or no email for sender / receiver we show no link
            if ($mailer->setTemplateByCode('continue') && (!empty($orgEmail)) && $token->isMailable()) {
                $html->pInfo($this->_('or'));
                $menuItem = $this->menu->find(array('controller' => 'ask', 'action' => $this->action));
                $href     = $menuItem->toHRefAttribute($this->request);
                $href->add([self::CONTINUE_LATER_PARAM => 1, 'id' => $token->getTokenId()]);
                $html->actionLink($href, $this->_('Send me an email to continue later'));
            }
        }
    }

    /**
     * @return boolean Was the continue link clicked
     */
    public function checkContinueLinkClicked()
    {
        return $this->request->getParam(self::CONTINUE_LATER_PARAM, false);
    }

    /**
     * Handle the situation when the continue later link was clicked
     *
     * @return \MUtil_Html_HtmlInterface
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

        $mailLoader = $this->loader->getMailLoader();
        /** @var \Gems_Mail_TokenMailer $mail */
        $mail       = $mailLoader->getMailer('token', $token->getTokenId());
        $mail->setFrom($token->getOrganization()->getFrom());
        
        if ($templateId) {

            $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
            $mailFields = $this->communicationRepository->getTokenMailFields($token, $language);
            $mailer = $this->communicationRepository->getMailer($token->getOrganization()->getEmail());
            $email->subject($mailTexts['subject'], $mailFields);
            $email->htmlTemplate($template, $mailTexts['body'], $mailFields);

            $lastMailedDate = \MUtil_Date::ifDate($token->getMailSentDate(), 'yyyy-MM-dd');

            // Do not send multiple mails a day
            if (! is_null($lastMailedDate) && $lastMailedDate->isToday()) {
                $html->pInfo($this->_('An email with information to continue later was already sent to your registered email address today.'));
            } else {
                $mailer->send($email);
                $html->pInfo($this->_('An email with information to continue later was sent to your registered email address.'));
            }

            $html->pInfo($this->_('Delivery can take a while. If you do not receive an email please check your spam-box.'));
        }

        if ($sig = $org->getSignature()) {
            $html->pInfo()->bbcode($sig);
        }

        return $html;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if(! $this->tracker) {
            $this->tracker = $this->loader->getTracker();
        }
        if ($this->token instanceof \Gems_Tracker_Token) {

            $this->wasAnswered = $this->token->isCompleted();

            return ($this->requestInfo instanceof \MUtil\Request\RequestInfo) &&
                    ($this->view instanceof \Zend_View) &&
                    parent::checkRegistryRequestsAnswers();
        } else {
            return false;
        }
    }

    /**
     * Formats an completion date for this display
     *
     * @param \MUtil_Date $dateTime
     * @return string
     */
    public function formatCompletion(\MUtil_Date $dateTime)
    {
        $days = abs($dateTime->diffDays());

        switch ($days) {
            case 0:
                return $this->_('We have received your answers today. Thank you!');

            case 1:
                return $this->_('We have received your answers yesterday. Thank you!');

            case 2:
                return $this->_('We have received your answers 2 days ago. Thank you.');

            default:
                if ($days <= 14) {
                    return sprintf($this->_('We have received your answers %d days ago. Thank you.'), $days);
                }
                return sprintf($this->_('We have received your answers on %s. '), $dateTime->toString($this->dateFormat));
        }
    }

    /**
     * Returns the duration if it should be displayed.
     *
     * @param string $duration
     * @return string
     */
    public function formatDuration($duration)
    {
        if ($duration && $this->showDuration) {
            return sprintf($this->_('Takes about %s to answer.'),  $duration) . ' ';
        }
    }

    /**
     * Return a no further questions statement (or nothing) 
     *
     * @return \MUtil_Html_HtmlElement
     */
    public function formatNoFurtherQuestions()
    {
        return \MUtil_Html::create('pInfo', $this->_('At the moment we have no further surveys for you to take.'));    
    }

    /**
     * Return a thanks greeting depending on showlastName switch
     * 
     * @return \MUtil_Html_HtmlElement
     */
    public function formatThanks() 
    {
        $output = \MUtil_Html::create('pInfo'); 
        if ($this->showLastName) {
            // getRespondentName returns the relation name when the token has a relation
            $output->sprintf($this->_('Thank you %s,'), $this->token->getRespondentName());
        } else {
            $output->append($this->_('Thank you for your answers,'));
        }
        return $output;
    }

    /**
     * Formats an until date for this display
     *
     * @param \MUtil_Date $dateTime
     * @return string
     */
    public function formatUntil(\MUtil_Date $dateTime = null)
    {
        if (false === $this->showUntil) { return; }
        
        if (null === $dateTime) {
            return $this->_('Survey has no time limit.');
        }

        $days = $dateTime->diffDays();

        switch ($days) {
            case 0:
                return [
                    \MUtil_Html::create('strong', $this->_('Warning!!!')),
                    ' ',
                    $this->_('This survey must be answered today!')
                    ];

            case 1:
                return [
                    \MUtil_Html::create('strong', $this->_('Warning!!')),
                    ' ',
                    $this->_('This survey can only be answered until tomorrow!')
                    ];

            case 2:
                return $this->_('Warning! This survey can only be answered for another 2 days!');

            default:
                if ($days <= 14) {
                    return sprintf($this->_('Please answer this survey within %d days.'), $days);
                }

                if ($days <= 0) {
                    return $this->_('This survey can no longer be answered.');
                }

                return sprintf($this->_('Please answer this survey before %s.'), $dateTime->toString($this->dateFormat));
        }
    }

    /**
     * Return a welcome greeting depending on showlastName switch
     *
     * @return \MUtil_Html_HtmlElement
     */
    public function formatWelcome()
    {
        $output = \MUtil_Html::create('pInfo');
        if ($this->showLastName) {
            // getRespondentName returns the relation name when the token has a relation
            $output->sprintf($this->_('Welcome %s,'), $this->token->getRespondentName());
        } else {
            $output->append($this->_('Welcome,'));
        }
        if ($this->token->hasRelation() && $this->showLastName) {
            return [
                $output,
                \MUtil_Html::create('pInfo', sprintf(
                    $this->_('We kindly ask you to answer a survey about %s.'), 
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
        return $this->_('Token');
    }

    /**
     * Get the href for a token
     *
     * @param \Gems_Tracker_Token $token
     * @return \MUtil_Html_HrefArrayAttribute
     */
    protected function getTokenHref(\Gems_Tracker_Token $token)
    {
        /***************
         * Get the url *
         ***************/
        $params = array(
            'action' => 'to-survey',
            \MUtil_Model::REQUEST_ID        => $token->getTokenId(),
            'RouteReset'                   => false,
            );

        return new \MUtil_Html_HrefArrayAttribute($params);
    }

    /**
     * The last token was answered, there are no more tokens to answer
     *
     * @return \MUtil_Html_HtmlInterface
     */
    public function lastCompleted()
    {
        $html = $this->getHtmlSequence();
        $org  = $this->token->getOrganization();

        $html->h3($this->getHeaderLabel());

        $html->append($this->formatThanks());

        $html->append($this->formatNoFurtherQuestions());

        if ($sig = $org->getSignature()) {
            $html->pInfo()->bbcode($sig);
        }

        return $html;
    }
}
